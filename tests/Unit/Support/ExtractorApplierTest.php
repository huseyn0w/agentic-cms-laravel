<?php

namespace Tests\Unit\Support;

use App\Support\Updater\Applier;
use App\Support\Updater\Extractor;
use App\Support\Updater\PathManifest;
use App\Support\Updater\ReleaseBuilder;
use Tests\TestCase;

/**
 * Extract → validate → apply: the core of a safe update. A validated release
 * overwrites core-owned files and leaves site/preserve paths untouched, and the
 * Applier refuses to write anything outside core even if a manifest claims to.
 */
class ExtractorApplierTest extends TestCase
{
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

    public function test_validate_passes_for_a_freshly_built_release(): void
    {
        $src = $this->tmp('src');
        $this->writeFile($src, 'config/app.php', '<?php return ["v"=>2];');

        $out = $this->tmp('out');
        $result = (new ReleaseBuilder($this->manifest()))->build($src, $out, '1.1.0');

        $work = $this->tmp('work');
        $extractor = new Extractor;
        $extracted = $extractor->extract($result['archive'], $work);

        $this->assertTrue($extractor->validateManifest($extracted));
    }

    public function test_validate_fails_when_an_extracted_file_is_tampered(): void
    {
        $src = $this->tmp('src');
        $this->writeFile($src, 'config/app.php', '<?php return ["v"=>2];');
        $out = $this->tmp('out');
        $result = (new ReleaseBuilder($this->manifest()))->build($src, $out, '1.1.0');

        $work = $this->tmp('work');
        $extractor = new Extractor;
        $extracted = $extractor->extract($result['archive'], $work);

        file_put_contents($extracted.'/config/app.php', 'tampered');

        $this->assertFalse($extractor->validateManifest($extracted));
    }

    public function test_apply_overwrites_core_and_leaves_site_and_preserve_untouched(): void
    {
        // Build a release that changes a core file.
        $src = $this->tmp('src');
        $this->writeFile($src, 'config/app.php', 'NEW-CORE');
        $out = $this->tmp('out');
        $result = (new ReleaseBuilder($this->manifest()))->build($src, $out, '2.0.0');

        $work = $this->tmp('work');
        $extracted = (new Extractor)->extract($result['archive'], $work);

        // Target install: old core + a site file + a preserved secret.
        $target = $this->tmp('target');
        $this->writeFile($target, 'config/app.php', 'OLD-CORE');
        $this->writeFile($target, 'app/Site/Providers/SiteServiceProvider.php', 'SITE');
        $this->writeFile($target, '.env', 'SECRET=keep');

        $applied = (new Applier($this->manifest()))->apply($extracted, $target);

        $this->assertContains('config/app.php', $applied);
        $this->assertSame('NEW-CORE', file_get_contents($target.'/config/app.php'));
        // Site + preserve untouched.
        $this->assertSame('SITE', file_get_contents($target.'/app/Site/Providers/SiteServiceProvider.php'));
        $this->assertSame('SECRET=keep', file_get_contents($target.'/.env'));
    }

    public function test_apply_refuses_to_write_a_non_core_path_even_if_listed(): void
    {
        // Hand-craft an extracted tree whose manifest lies: it lists .env.
        $extracted = $this->tmp('evil');
        $this->writeFile($extracted, '.env', 'PWNED');
        file_put_contents($extracted.'/release-manifest.json', json_encode([
            'version' => '9.9.9',
            'files' => [['path' => '.env', 'sha256' => hash('sha256', 'PWNED')]],
        ]));

        $target = $this->tmp('target');
        $this->writeFile($target, '.env', 'SECRET=keep');

        $applied = (new Applier($this->manifest()))->apply($extracted, $target);

        $this->assertNotContains('.env', $applied);
        $this->assertSame('SECRET=keep', file_get_contents($target.'/.env'));
    }
}
