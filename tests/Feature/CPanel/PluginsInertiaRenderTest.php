<?php

namespace Tests\Feature\CPanel;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The admin plugin manager is on Inertia (cpanel/plugins/List). Listing renders
 * the discovered plugins; the toggle endpoint stays a PUT mutation.
 */
class PluginsInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_plugins_list_renders_inertia_component(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/agentic-cms-laravel-admin/plugins')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/plugins/List')
                ->has('plugins'));
    }

    public function test_plugins_list_requires_admin(): void
    {
        $this->get('/agentic-cms-laravel-admin/plugins')->assertRedirect('/login');
    }
}
