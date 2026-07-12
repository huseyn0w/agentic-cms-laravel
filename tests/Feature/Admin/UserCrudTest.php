<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Full admin CRUD round-trip for users via the CPanel user management section
 * (guarded by manage_users).
 */
class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    public function test_admin_can_create_a_user(): void
    {
        $response = $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/users/new', [
            'username' => 'createduser',
            'email' => 'createduser@example.com',
            'role_id' => 2,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('cpanel_all_users_list'));

        $this->assertDatabaseHas('users', [
            'username' => 'createduser',
            'email' => 'createduser@example.com',
            'role_id' => 2,
        ]);
    }

    public function test_admin_can_update_a_user(): void
    {
        $user = User::factory()->create(['role_id' => 2]);

        $response = $this->actingAs($this->admin)->put('/agentic-cms-laravel-admin/users/'.$user->id.'/update', [
            'username' => $user->username,
            'email' => $user->email,
            'role_id' => 2,
            'name' => 'Renamed',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Renamed', $user->fresh()->name);
    }

    public function test_admin_can_delete_a_user(): void
    {
        $user = User::factory()->create(['role_id' => 2]);

        $this->actingAs($this->admin)
            ->delete('/agentic-cms-laravel-admin/users/'.$user->id.'/delete')
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_validation_blocks_duplicate_username_and_email(): void
    {
        $existing = User::factory()->create(['role_id' => 2]);

        $response = $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/users/new')
            ->post('/agentic-cms-laravel-admin/users/new', [
                'username' => $existing->username,
                'email' => $existing->email,
                'role_id' => 2,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertSessionHasErrors(['username', 'email']);
    }

    public function test_user_with_panel_access_but_no_user_permission_is_blocked(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoUsers',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_users' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/users')->assertStatus(401);
    }
}
