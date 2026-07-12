<?php

namespace Tests\Feature;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fix #2: the `see_admin_panel` alias must map to AdminPanelMiddleware, which
 * checks the `see_admin_panel` permission. The seeded admin must keep access;
 * a user lacking the permission must be denied with a 403.
 */
class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeded_admin_can_reach_admin_panel(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->assertSame(1, $admin->role->id, 'Seeded admin should hold role_id 1');

        $response = $this->actingAs($admin)->get('/agentic-cms-laravel-admin');

        $response->assertStatus(200);
    }

    public function test_user_without_permission_gets_403(): void
    {
        // role_id 2 = standard user, see_admin_panel permission = 0.
        $user = User::factory()->create(['role_id' => 2]);

        $response = $this->actingAs($user)->get('/agentic-cms-laravel-admin');

        $response->assertStatus(403);
    }
}
