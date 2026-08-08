<?php

namespace Tests\Unit\Support;

use App\Support\Updater\PathManifest;
use Tests\TestCase;

/**
 * The PathManifest is the heart of a safe updater: it classifies every repo
 * path as core-owned (overwritten on update), site-owned (never touched), or
 * preserve/state (never touched). Ownership is resolved by longest-prefix match,
 * so nested site paths (app/Site) win over their core parent (app).
 */
class PathManifestTest extends TestCase
{
    private function manifest(): PathManifest
    {
        return new PathManifest(config('cms.paths'));
    }

    public function test_plain_core_paths_are_core_owned(): void
    {
        $m = $this->manifest();

        $this->assertTrue($m->isCoreOwned('app/Http/Controllers/PageController.php'));
        $this->assertTrue($m->isCoreOwned('config/app.php'));
        $this->assertTrue($m->isCoreOwned('resources/js/app.tsx'));
        $this->assertTrue($m->isCoreOwned('vendor/autoload.php'));
        $this->assertTrue($m->isCoreOwned('public/build/manifest.json'));
    }

    public function test_nested_site_paths_win_over_their_core_parent(): void
    {
        $m = $this->manifest();

        // app is core, but app/Site is site — the longer prefix must win.
        $this->assertFalse($m->isCoreOwned('app/Site/Providers/SiteServiceProvider.php'));
        $this->assertTrue($m->isSiteOwned('app/Site/Providers/SiteServiceProvider.php'));

        $this->assertFalse($m->isCoreOwned('config/site.php'));
        $this->assertTrue($m->isSiteOwned('config/site.php'));

        $this->assertFalse($m->isCoreOwned('resources/js/site/Theme.tsx'));
        $this->assertTrue($m->isSiteOwned('resources/js/site/Theme.tsx'));
    }

    public function test_state_paths_are_preserved_never_overwritten(): void
    {
        $m = $this->manifest();

        $this->assertTrue($m->isProtected('.env'));
        $this->assertTrue($m->isProtected('storage/logs/laravel.log'));
        $this->assertTrue($m->isProtected('public/uploads/images/x.jpg'));

        // Protected paths are never core-owned.
        $this->assertFalse($m->isCoreOwned('.env'));
        $this->assertFalse($m->isCoreOwned('storage/logs/laravel.log'));
    }

    public function test_core_and_protected_sets_never_overlap(): void
    {
        $m = $this->manifest();

        // Every declared site/preserve path must classify as protected, never
        // as core-owned. This is the safety invariant the updater relies on.
        foreach ([...$m->siteOwnedPrefixes(), ...$m->preservePrefixes()] as $path) {
            $sample = $path.'/example';
            $this->assertFalse(
                $m->isCoreOwned($sample),
                "Protected path '{$path}' must not be core-owned"
            );
            $this->assertTrue(
                $m->isProtected($sample),
                "Path '{$path}' must be protected"
            );
        }
    }

    public function test_unknown_paths_default_to_protected(): void
    {
        // Unknown = not overwritten. The updater only writes explicit core paths,
        // so anything unclassified is left alone.
        $m = $this->manifest();

        $this->assertFalse($m->isCoreOwned('some/random/user-file.txt'));
        $this->assertTrue($m->isProtected('some/random/user-file.txt'));
    }
}
