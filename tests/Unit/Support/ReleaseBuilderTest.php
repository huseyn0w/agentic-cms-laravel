<?php

namespace Tests\Unit\Support;

use App\Support\Updater\PathManifest;
use App\Support\Updater\ReleaseBuilder;
use PharData;
use Tests\TestCase;

/**
 * The release builder packages a prebuilt core release: a tar.gz of ONLY the
 * core-owned paths (site/preserve excluded), a release-manifest.json listing
 * each file + its sha256, and a top-level sha256 of the archive. This is what
 * the CI release job produces and what the in-admin updater consumes.
 */
class ReleaseBuilderTest extends TestCase
{
    private string $src;

    private string $out;

    protected function setUp(): void
    {
        parent::setUp();
        $this->src = sys_get_temp_dir().'/cms-rel-src-'.bin2hex(random_bytes(4));
        $this->out = sys_get_temp_dir().'/cms-rel-out-'.bin2hex(random_bytes(4));
        mkdir($this->src, 0777, true);
        mkdir($this->out, 0777, true);

        // Core-owned files.
        $this->writeFixture('app/Http/Controllers/PageController.php', '<?php // core');
        $this->writeFixture('config/app.php', '<?php return [];');
        $this->writeFixture('public/build/manifest.json', '{}');

        // Site + preserve files that must NOT be packaged.
        $this->writeFixture('app/Site/Providers/SiteServiceProvider.php', '<?php // site');
        $this->writeFixture('.env', 'SECRET=1');
        $this->writeFixture('storage/logs/laravel.log', 'log');
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->src);
        $this->rrmdir($this->out);
        parent::tearDown();
    }

    private function writeFixture(string $rel, string $contents): void
    {
        $full = $this->src.'/'.$rel;
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

    private function builder(): ReleaseBuilder
    {
        return new ReleaseBuilder(new PathManifest(config('cms.paths')));
    }

    public function test_manifest_lists_core_files_only(): void
    {
        $result = $this->builder()->build($this->src, $this->out, '1.2.0');

        $manifest = json_decode(file_get_contents($result['manifest']), true);
        $paths = array_column($manifest['files'], 'path');

        $this->assertContains('app/Http/Controllers/PageController.php', $paths);
        $this->assertContains('config/app.php', $paths);
        $this->assertContains('public/build/manifest.json', $paths);

        // Site + preserve are excluded.
        $this->assertNotContains('app/Site/Providers/SiteServiceProvider.php', $paths);
        $this->assertNotContains('.env', $paths);
        $this->assertNotContains('storage/logs/laravel.log', $paths);
    }

    public function test_manifest_records_version_and_per_file_checksums(): void
    {
        $result = $this->builder()->build($this->src, $this->out, '1.2.0');
        $manifest = json_decode(file_get_contents($result['manifest']), true);

        $this->assertSame('1.2.0', $manifest['version']);
        $this->assertArrayHasKey('min_php', $manifest);
        $this->assertArrayHasKey('min_from_version', $manifest);

        $entry = collect($manifest['files'])->firstWhere('path', 'config/app.php');
        $this->assertSame(hash('sha256', '<?php return [];'), $entry['sha256']);
    }

    public function test_archive_contains_core_files_and_the_manifest(): void
    {
        $result = $this->builder()->build($this->src, $this->out, '1.2.0');

        $this->assertFileExists($result['archive']);

        $phar = new PharData($result['archive']);
        $this->assertTrue(isset($phar['app/Http/Controllers/PageController.php']));
        $this->assertTrue(isset($phar['release-manifest.json']));
        $this->assertFalse(isset($phar['.env']));
        $this->assertFalse(isset($phar['app/Site/Providers/SiteServiceProvider.php']));
    }

    public function test_top_level_sha256_matches_the_archive(): void
    {
        $result = $this->builder()->build($this->src, $this->out, '1.2.0');

        $this->assertSame(hash_file('sha256', $result['archive']), $result['sha256']);
        $this->assertSame($result['sha256'], trim(file_get_contents($result['archive'].'.sha256')));
    }
}
