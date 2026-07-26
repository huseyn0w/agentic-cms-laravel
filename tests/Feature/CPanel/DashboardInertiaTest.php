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
                ->has('comments'));
    }
}
