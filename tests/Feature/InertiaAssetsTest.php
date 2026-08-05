<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Inertia root template (resources/views/app.blade.php) must ship the
 * scoped Tailwind stylesheets. It once loaded only app.tsx, which carries no
 * CSS of its own, so every built (non-dev) Inertia page — the whole admin panel
 * and the auth screens — rendered unstyled. This guards that regression: an
 * Inertia page's server-rendered head links both scoped theme builds.
 */
class InertiaAssetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_inertia_root_loads_the_scoped_theme_stylesheets(): void
    {
        // The login screen is a guest Inertia page rendered through app.blade.php.
        $html = $this->get('/login')->assertOk()->getContent();

        // app.css = .theme-default (auth + public), admin.css = .theme-admin.
        $this->assertMatchesRegularExpression('#/build/assets/app-[^"]+\.css#', $html);
        $this->assertMatchesRegularExpression('#/build/assets/admin-[^"]+\.css#', $html);
    }
}
