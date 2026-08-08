<?php

namespace Tests\Unit\Support;

use App\Support\Updater\SiteBootstrap;
use Tests\TestCase;

/**
 * Core boots a fork's SiteServiceProvider only when the fork actually ships one.
 * A stock install has no site provider and must boot unchanged.
 */
class SiteBootstrapTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = app_path('Site/Providers');
    }

    protected function tearDown(): void
    {
        if (is_file($this->dir.'/SiteServiceProvider.php')) {
            @unlink($this->dir.'/SiteServiceProvider.php');
        }
        @rmdir($this->dir);
        @rmdir(app_path('Site'));
        parent::tearDown();
    }

    public function test_registers_the_site_provider_only_when_present(): void
    {
        // A require in this process can't be undone, so both sides are checked
        // in order within one test: no-op first, then registration.
        if (! class_exists(SiteBootstrap::PROVIDER, false)) {
            $fresh = $this->app->make('app');
            $fresh->forgetInstance('site.booted');

            SiteBootstrap::register($fresh);

            $this->assertFalse(
                $fresh->bound('site.booted'),
                'register() must be a no-op when no site provider exists'
            );
        }

        if (! is_dir($this->dir)) {
            mkdir($this->dir, 0777, true);
        }

        file_put_contents($this->dir.'/SiteServiceProvider.php', <<<'PHP'
        <?php

        namespace App\Site\Providers;

        use Illuminate\Support\ServiceProvider;

        class SiteServiceProvider extends ServiceProvider
        {
            public function register(): void
            {
                $this->app->instance('site.booted', true);
            }
        }
        PHP);

        require $this->dir.'/SiteServiceProvider.php';

        SiteBootstrap::register($this->app);

        $this->assertTrue($this->app->bound('site.booted'));
        $this->assertTrue($this->app->make('site.booted'));
    }
}
