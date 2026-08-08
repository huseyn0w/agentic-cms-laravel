<?php

namespace App\Console\Commands;

use App\Support\Updater\ReleaseBuilder;
use App\Support\Updater\Signer;
use Illuminate\Console\Command;

/**
 * Package a prebuilt core release (tar.gz + manifest + sha256 [+ signature]).
 *
 * Run by the CI release job after `composer install --no-dev` and
 * `npm run build`, so the working tree already carries vendor/ and the compiled
 * public/build + bootstrap/ssr. The output is uploaded to the GitHub Release
 * that the in-admin updater consumes.
 *
 * If CMS_RELEASE_SIGN_KEY (base64 sodium secret key) is present, the archive is
 * signed; the updater requires that signature in production.
 */
class BuildRelease extends Command
{
    protected $signature = 'cms:build-release {version?} {--output=}';

    protected $description = 'Package a prebuilt core release archive with manifest, checksum, and signature';

    public function handle(ReleaseBuilder $builder): int
    {
        $version = $this->argument('version') ?: cms_version();
        $output = $this->option('output') ?: storage_path('app/releases');

        $this->info("Building release {$version}…");

        $result = $builder->build(base_path(), $output, $version);

        $this->line("  files:    {$result['files']}");
        $this->line("  archive:  {$result['archive']}");
        $this->line("  sha256:   {$result['sha256']}");

        $signKey = config('cms.release.sign_key');
        if (is_string($signKey) && $signKey !== '') {
            $sigPath = (new Signer)->sign($result['archive'], $signKey);
            $this->line("  signature: {$sigPath}");
        } else {
            $this->warn('  No CMS_RELEASE_SIGN_KEY — archive is unsigned (production updater will refuse it).');
        }

        $this->info('Release built.');

        return self::SUCCESS;
    }
}
