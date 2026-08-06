<?php

namespace Tests\Feature\CPanel;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    public function test_dashboard_renders_inertia_component_with_props(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/agentic-cms-laravel-admin')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/Dashboard')
                ->has('posts')
                ->has('users')
                ->has('comments')
                ->has('counts', fn (AssertableInertia $c) => $c
                    ->has('posts')
                    ->has('users')
                    ->has('comments')
                    ->has('comments_pending')
                    ->has('scheduled')));
    }

    /**
     * The shared auth.can map drives the admin sidebar. The Administrator role
     * holds every permission, so each ability must resolve true — the abilities
     * are authorized through the UserRoles-bound UserPolicy, so the shared map
     * must check them against that model (regression: an empty map hid the whole
     * sidebar nav).
     */
    public function test_admin_shared_abilities_reflect_role_permissions(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/agentic-cms-laravel-admin')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.can.see_admin_panel', true)
                ->where('auth.can.manage_posts', true)
                ->where('auth.can.manage_users', true)
                ->where('auth.can.manage_menus', true));
    }
}
