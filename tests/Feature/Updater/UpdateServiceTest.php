<?php

namespace Tests\Feature\Updater;

use App\Repositories\CPanelUpdateRepository;
use App\Support\Updater\Applier;
use App\Support\Updater\BackupManager;
use App\Support\Updater\Downloader;
use App\Support\Updater\Environment;
use App\Support\Updater\Extractor;
use App\Support\Updater\PathManifest;
use App\Support\Updater\ReleaseBuilder;
use App\Support\Updater\ReleaseFeed;
use App\Support\Updater\Signer;
use App\Support\Updater\UpdateException;
use App\Support\Updater\UpdateService;
use App\Support\Updater\Verifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * End-to-end updater: a signed, checksummed release is downloaded, verified,
 * backed up, applied atomically, and audited — and a failure after apply rolls
 * the files back. The system steps (maintenance/migrate/caches) are skipped via
 * run_system=false so the file flow runs on a temp tree, not the live app.
 */
class UpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    private array $dirs = [];

    protected function tearDown(): void
    {
        foreach ($this->dirs as $dir) {
            $this->rrmdir($dir);
        }
        parent::tearDown();
    }

    private function tmp(string $prefix): string
    {
        $dir = sys_get_temp_dir().'/'.$prefix.'-'.bin2hex(random_bytes(4));
        mkdir($dir, 0777, true);
        $this->dirs[] = $dir;

        return $dir;
    }

    private function writeFile(string $root, string $rel, string $contents): void
    {
        $full = $root.'/'.$rel;
        @mkdir(dirname($full), 0777, true);
        file_put_contents($full, $contents);
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }

    private function manifest(): PathManifest
    {
        return new PathManifest(config('cms.paths'));
    }

    /**
     * Build a release archive, fake the feed download, and return a release
     * descriptor (as a feed entry) plus the archive path.
     *
     * @return array{release: array<string,mixed>, archive: string}
     */
    private function buildAndFakeRelease(string $coreContents, ?array $keys = null): array
    {
        $src = $this->tmp('src');
        $this->writeFile($src, 'config/app.php', $coreContents);

        $out = $this->tmp('out');
        $result = (new ReleaseBuilder($this->manifest()))->build($src, $out, '2.0.0');

        $bytes = file_get_contents($result['archive']);
        $url = 'https://releases.test/release-2.0.0.tar.gz';
        Http::fake([$url => Http::response($bytes)]);

        $release = [
            'version' => '2.0.0',
            'url' => $url,
            'sha256' => $result['sha256'],
        ];

        if ($keys !== null) {
            $sigPath = (new Signer)->sign($result['archive'], $keys['secret']);
            $release['signature'] = file_get_contents($sigPath);
        }

        return ['release' => $release, 'archive' => $result['archive']];
    }

    private function service(?Applier $applier = null): UpdateService
    {
        return new UpdateService(
            new Environment,
            new ReleaseFeed(''),
            new Downloader,
            new BackupManager,
            new Extractor,
            $applier ?? new Applier($this->manifest()),
            new Verifier,
            app(CPanelUpdateRepository::class),
        );
    }

    public function test_happy_path_applies_the_release_and_records_success(): void
    {
        $built = $this->buildAndFakeRelease('NEW-CORE');

        $target = $this->tmp('target');
        $this->writeFile($target, 'config/app.php', 'OLD-CORE');
        $this->writeFile($target, 'app/Site/keep.php', 'SITE');
        $this->writeFile($target, '.env', 'SECRET');

        $audit = $this->service()->update($built['release'], [
            'target_root' => $target,
            'run_system' => false,
        ]);

        $this->assertSame('success', $audit->status);
        $this->assertSame('2.0.0', $audit->to_version);
        $this->assertSame('NEW-CORE', file_get_contents($target.'/config/app.php'));
        // Site + preserve untouched.
        $this->assertSame('SITE', file_get_contents($target.'/app/Site/keep.php'));
        $this->assertSame('SECRET', file_get_contents($target.'/.env'));

        $this->assertDatabaseHas('cms_updates', ['to_version' => '2.0.0', 'status' => 'success']);
    }

    public function test_a_failure_after_apply_rolls_the_files_back(): void
    {
        $built = $this->buildAndFakeRelease('NEW-CORE');

        $target = $this->tmp('target');
        $this->writeFile($target, 'config/app.php', 'OLD-CORE');

        // An Applier that corrupts a file then fails mid-apply.
        $badApplier = new class($this->manifest()) extends Applier
        {
            public function apply(string $extractedDir, string $targetRoot): array
            {
                file_put_contents($targetRoot.'/config/app.php', 'CORRUPTED');
                throw new RuntimeException('boom during apply');
            }
        };

        try {
            $this->service($badApplier)->update($built['release'], [
                'target_root' => $target,
                'run_system' => false,
            ]);
            $this->fail('Expected the update to fail.');
        } catch (UpdateException $e) {
            $this->assertStringContainsString('boom during apply', $e->getMessage());
        }

        // Rolled back to the original content.
        $this->assertSame('OLD-CORE', file_get_contents($target.'/config/app.php'));
        $this->assertDatabaseHas('cms_updates', ['status' => 'rolled_back']);
    }

    public function test_checksum_mismatch_aborts_before_touching_the_install(): void
    {
        $url = 'https://releases.test/bad.tar.gz';
        Http::fake([$url => Http::response('not-the-real-bytes')]);

        $target = $this->tmp('target');
        $this->writeFile($target, 'config/app.php', 'OLD-CORE');

        $release = ['version' => '2.0.0', 'url' => $url, 'sha256' => hash('sha256', 'expected-different')];

        try {
            $this->service()->update($release, ['target_root' => $target, 'run_system' => false]);
            $this->fail('Expected a checksum failure.');
        } catch (UpdateException $e) {
            $this->assertStringContainsString('Checksum mismatch', $e->getMessage());
        }

        // Untouched + audited as failed (not rolled_back — nothing was applied).
        $this->assertSame('OLD-CORE', file_get_contents($target.'/config/app.php'));
        $this->assertDatabaseHas('cms_updates', ['status' => 'failed']);
    }

    public function test_signature_is_verified_when_a_public_key_is_configured(): void
    {
        $keys = Signer::generateKeypair();
        config(['cms.update.public_key' => $keys['public']]);

        $built = $this->buildAndFakeRelease('NEW-CORE', $keys);

        $target = $this->tmp('target');
        $this->writeFile($target, 'config/app.php', 'OLD-CORE');

        $audit = $this->service()->update($built['release'], [
            'target_root' => $target,
            'run_system' => false,
        ]);

        $this->assertSame('success', $audit->status);
        $this->assertSame('NEW-CORE', file_get_contents($target.'/config/app.php'));
    }

    public function test_a_bad_signature_is_rejected_when_a_public_key_is_configured(): void
    {
        $keys = Signer::generateKeypair();
        $other = Signer::generateKeypair();
        // Configure a public key that does NOT match the signing key.
        config(['cms.update.public_key' => $other['public']]);

        $built = $this->buildAndFakeRelease('NEW-CORE', $keys);

        $target = $this->tmp('target');
        $this->writeFile($target, 'config/app.php', 'OLD-CORE');

        try {
            $this->service()->update($built['release'], ['target_root' => $target, 'run_system' => false]);
            $this->fail('Expected signature rejection.');
        } catch (UpdateException $e) {
            $this->assertStringContainsString('signature', strtolower($e->getMessage()));
        }

        $this->assertSame('OLD-CORE', file_get_contents($target.'/config/app.php'));
    }
}
