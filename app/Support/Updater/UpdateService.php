<?php

namespace App\Support\Updater;

use App\Http\Models\CPanel\CPanelUpdate;
use App\Repositories\CPanelUpdateRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Orchestrates a core update, end to end and reversibly.
 *
 * Flow: preflight → maintenance down → download + verify (sha256 + signature)
 * → backup → extract + validate → atomic apply → deps → migrate → rebuild
 * caches → record version → maintenance up. Any failure after apply rolls back
 * from the file snapshot. Every step is a small, separately tested collaborator;
 * this class only sequences them and owns the rollback + audit log.
 *
 * Testability: file steps run against $opts['target_root'] (default base_path),
 * and the system steps (maintenance/migrate/caches/composer) are skipped when
 * $opts['run_system'] is false, so the whole file flow — including rollback —
 * is exercised on a temp tree without touching the live app.
 */
class UpdateService
{
    public function __construct(
        private Environment $environment,
        private ReleaseFeed $feed,
        private Downloader $downloader,
        private BackupManager $backup,
        private Extractor $extractor,
        private Applier $applier,
        private Verifier $verifier,
        private CPanelUpdateRepository $repository,
    ) {}

    /** Cache key holding the last known available release (or null). */
    public const CACHE_KEY = 'cms.update.available';

    /**
     * The release newer than the installed version, or null when up to date.
     * Hits the feed live.
     *
     * @return array<string, mixed>|null
     */
    public function checkForUpdate(): ?array
    {
        return $this->feed->available(cms_version());
    }

    /**
     * The last known available release from the background check, without
     * hitting the network. Used by the shared prop + the admin screen.
     *
     * @return array<string, mixed>|null
     */
    public function cachedAvailable(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        return is_array($cached) ? $cached : null;
    }

    /**
     * Refresh availability from the feed and cache the result. Called by the
     * scheduled check and the "Check for updates" button.
     *
     * @return array<string, mixed>|null
     */
    public function refreshAvailability(): ?array
    {
        $release = $this->checkForUpdate();
        Cache::put(self::CACHE_KEY, $release, now()->addDay());

        return $release;
    }

    /**
     * Recent update attempts, newest first (audit history for the admin screen).
     *
     * @return Collection<int, CPanelUpdate>
     */
    public function history(int $limit = 10): Collection
    {
        return $this->repository->recent($limit);
    }

    /**
     * Apply an update. Pass an explicit release or let it pull the latest from
     * the feed. Returns the audit row; throws UpdateException on failure (after
     * rolling back).
     *
     * @param  array<string, mixed>|null  $release
     * @param  array<string, mixed>  $opts
     */
    public function update(?array $release = null, array $opts = []): CPanelUpdate
    {
        $release ??= $this->checkForUpdate();

        if ($release === null) {
            throw new UpdateException('No update is available.');
        }

        $targetRoot = rtrim($opts['target_root'] ?? base_path(), '/');
        $runSystem = $opts['run_system'] ?? true;

        $this->preflight($release, $targetRoot);

        $audit = $this->repository->start(cms_version(), (string) ($release['version'] ?? ''));

        $workDir = storage_path('app/updates/'.($release['version'] ?? 'x').'-'.bin2hex(random_bytes(4)));
        @mkdir($workDir, 0775, true);
        $backupDir = $workDir.'/backup';
        $applied = false;

        try {
            $archive = $this->downloader->download(
                (string) $release['url'],
                (string) $release['sha256'],
                $workDir.'/release.tar.gz'
            );

            $this->verifySignature($release, $archive);

            $extracted = $this->extractor->extract($archive, $workDir);

            if (! $this->extractor->validateManifest($extracted)) {
                throw new UpdateException('Release failed manifest validation (corrupt or tampered).');
            }

            $this->maintenanceDown($runSystem);

            $files = $this->coreFilesFrom($extracted);
            $this->backup->backupFiles($files, $targetRoot, $backupDir);
            if ($runSystem) {
                $this->backup->dumpDatabase($backupDir);
            }

            // From here the install tree is being written, so any later failure
            // must restore the snapshot.
            $applied = true;
            $this->applier->apply($extracted, $targetRoot);

            $this->installDependencies($runSystem);
            $this->migrate($runSystem);
            $this->rebuildCaches($runSystem);
            $this->recordVersion($extracted, $targetRoot);

            $this->maintenanceUp($runSystem);

            return $this->repository->finish($audit, 'success', 'Updated to '.($release['version'] ?? ''));
        } catch (Throwable $e) {
            // Roll back the files if we had started overwriting them.
            if ($applied) {
                $this->backup->restoreFiles($backupDir, $targetRoot);
            }
            $this->maintenanceUp($runSystem);

            $status = $applied ? 'rolled_back' : 'failed';
            $this->repository->finish($audit, $status, $e->getMessage());

            throw new UpdateException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param  array<string, mixed>  $release
     */
    private function preflight(array $release, string $targetRoot): void
    {
        if (isset($release['min_php']) && version_compare(PHP_VERSION, (string) $release['min_php'], '<')) {
            throw new UpdateException("This release needs PHP {$release['min_php']} (running ".PHP_VERSION.').');
        }

        if (isset($release['min_from_version'])
            && version_compare(cms_version(), (string) $release['min_from_version'], '<')) {
            throw new UpdateException("Update too old to apply directly; upgrade to {$release['min_from_version']} first.");
        }

        if (! $this->environment->canWrite($targetRoot)) {
            throw new UpdateException("The install directory is not writable: {$targetRoot}");
        }
    }

    /**
     * Enforce the signature policy: mandatory in production, checked whenever a
     * signature + public key are both present elsewhere.
     *
     * @param  array<string, mixed>  $release
     */
    private function verifySignature(array $release, string $archive): void
    {
        $signature = isset($release['signature']) ? (string) $release['signature'] : '';
        $publicKey = (string) config('cms.update.public_key', '');
        $isProd = app()->environment('production');

        if ($isProd) {
            if ($signature === '' || $publicKey === '' || ! $this->verifier->verify($archive, $signature, $publicKey)) {
                throw new UpdateException('A valid release signature is required in production.');
            }

            return;
        }

        // Outside production, verify when both are present; ignore when absent.
        if ($signature !== '' && $publicKey !== '' && ! $this->verifier->verify($archive, $signature, $publicKey)) {
            throw new UpdateException('The release signature did not verify.');
        }
    }

    /**
     * Core-owned files listed in the extracted manifest, for the backup step.
     *
     * @return list<string>
     */
    private function coreFilesFrom(string $extracted): array
    {
        $manifest = $this->extractor->readManifest($extracted);
        $files = [];

        foreach ($manifest['files'] ?? [] as $file) {
            if (is_array($file) && isset($file['path']) && is_string($file['path'])) {
                $files[] = $file['path'];
            }
        }

        return $files;
    }

    private function recordVersion(string $extracted, string $targetRoot): void
    {
        // The new version ships in config/cms.php inside the release, which the
        // apply step already wrote — nothing extra to persist. cms_version()
        // reflects it after the config cache is rebuilt.
    }

    private function maintenanceDown(bool $run): void
    {
        if ($run) {
            Artisan::call('down');
        }
    }

    private function maintenanceUp(bool $run): void
    {
        if ($run) {
            try {
                Artisan::call('up');
            } catch (Throwable) {
                // Never let bringing the site back up mask the real error.
            }
        }
    }

    private function installDependencies(bool $run): void
    {
        if (! $run) {
            return;
        }

        // Default: trust the vendor/ shipped in the release. Only run composer
        // when a host opts in and composer is actually reachable.
        if (! config('cms.update.install_composer')) {
            return;
        }

        if (! $this->environment->hasComposer()) {
            return;
        }

        $process = new Process(['composer', 'install', '--no-dev', '--no-interaction', '--optimize-autoloader'], base_path());
        $process->setTimeout(300);
        $process->run();
    }

    private function migrate(bool $run): void
    {
        if ($run) {
            Artisan::call('migrate', ['--force' => true]);
        }
    }

    private function rebuildCaches(bool $run): void
    {
        if (! $run) {
            return;
        }

        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('optimize');
    }
}
