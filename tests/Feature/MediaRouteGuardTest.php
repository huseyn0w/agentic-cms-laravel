<?php

namespace Tests\Feature;

use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
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
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    public function test_admin_can_access_media(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)->get('/agentic-cms-laravel-admin/media')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/media/Index')
                ->where('library_src', '/filemanager')
                ->has('upload_endpoint'));
    }

    public function test_user_without_permission_is_denied_media(): void
    {
        $user = User::factory()->create(['role_id' => 2]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/media')->assertStatus(403);
    }

    public function test_panel_user_without_manage_media_is_denied(): void
    {
        // Can see the panel but lacks the media capability specifically: the
        // manage_media middleware must reject the request (401).
        $role = UserRoles::create([
            'name' => 'PanelNoMedia',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_media' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/media')->assertStatus(401);
    }
}
