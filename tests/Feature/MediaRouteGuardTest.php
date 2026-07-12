<?php

namespace Tests\Feature;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fix #3: the media route previously had no permission middleware. A regular
 * admin-panel user without the management permission must now be denied.
 */
class MediaRouteGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_access_media(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)->get('/agentic-cms-laravel-admin/media')->assertStatus(200);
    }

    public function test_user_without_permission_is_denied_media(): void
    {
        $user = User::factory()->create(['role_id' => 2]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/media')->assertStatus(403);
    }
}
