<?php

namespace Tests\Unit\Support;

use App\Support\PluginManager;
use Tests\TestCase;

/**
 * The plugin discovery scans both the core plugin dir (app/Plugins) and the
 * site-owned dir (app/Site/Plugins), so a fork can drop plugins into its own
 * zone without editing core. The site zone is never overwritten by an update.
 */
class PluginManagerSiteScanTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = app_path('Site/Plugins/Fixture');
    }

    protected function tearDown(): void
    {
        if (is_file($this->dir.'/FixturePlugin.php')) {
            @unlink($this->dir.'/FixturePlugin.php');
        }
        // Clean up the fixture dirs we created (leave app/Site itself alone).
        @rmdir($this->dir);
        @rmdir(app_path('Site/Plugins'));
        parent::tearDown();
    }

    public function test_discovers_a_plugin_placed_in_the_site_zone(): void
    {
        if (! is_dir($this->dir)) {
            mkdir($this->dir, 0777, true);
        }

        file_put_contents($this->dir.'/FixturePlugin.php', <<<'PHP'
        <?php

        namespace App\Site\Plugins\Fixture;

        use App\Plugins\Contracts\PluginInterface;
        use App\Support\Hooks;

        class FixturePlugin implements PluginInterface
        {
            public function slug(): string { return 'site-fixture'; }
            public function name(): string { return 'Site Fixture'; }
            public function description(): string { return 'test'; }
            public function boot(Hooks $hooks): void {}
        }
        PHP);

        require $this->dir.'/FixturePlugin.php';

        $plugins = app(PluginManager::class)->discover();

        $this->assertArrayHasKey('site-fixture', $plugins);
    }
}
