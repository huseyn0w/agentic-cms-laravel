<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Full admin CRUD round-trip for roles (guarded by manage_roles, which checks
 * the manage_user_roles permission). The repository rebuilds the permission
 * map server-side and only flips whitelisted permission names.
 */
class RoleCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    public function test_roles_list_renders_inertia_with_shaped_rows(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/roles')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/roles/List')
                ->has('roles_list.data.0.id')
                ->has('roles_list.data.0.name'));
    }

    public function test_new_role_form_renders_inertia_with_permission_options(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/roles/new')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/roles/Form')
                ->where('entity', null)
                ->has('permission_options.0.name')
                ->has('permission_options.0.label'));
    }

    public function test_edit_role_form_renders_inertia_with_enabled_permissions(): void
    {
        $role = UserRoles::create([
            'name' => 'Editorish',
            'permissions' => json_encode(['manage_posts' => 1, 'manage_users' => 0]),
        ]);

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/roles/'.$role->id)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/roles/Form')
                ->where('entity.name', 'Editorish')
                ->where('entity.permissions', fn ($perms) => collect($perms)->contains('manage_posts')
                    && ! collect($perms)->contains('manage_users')));
    }

    public function test_admin_can_create_a_role(): void
    {
        $response = $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/roles/new', [
            'name' => 'Author',
            'permissions' => ['manage_posts', 'manage_comments'],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('cpanel_user_roles'));

        $role = UserRoles::where('name', 'Author')->firstOrFail();
        $permissions = json_decode($role->permissions, true);
        $this->assertSame(1, $permissions['manage_posts']);
        $this->assertSame(1, $permissions['manage_comments']);
        $this->assertSame(0, $permissions['manage_users']);
    }

    public function test_admin_can_update_a_role(): void
    {
        $role = UserRoles::create([
            'name' => 'Temp',
            'permissions' => json_encode(['manage_posts' => 1]),
        ]);

        $response = $this->actingAs($this->admin)->put('/agentic-cms-laravel-admin/roles/'.$role->id.'/update', [
            'name' => 'Temp Renamed',
            'permissions' => ['manage_pages'],
        ]);

        $response->assertSessionHasNoErrors();
        $fresh = $role->fresh();
        $this->assertSame('Temp Renamed', $fresh->name);
        $permissions = json_decode($fresh->permissions, true);
        $this->assertSame(1, $permissions['manage_pages']);
        $this->assertSame(0, $permissions['manage_posts']);
    }

    public function test_admin_can_delete_a_role(): void
    {
        $role = UserRoles::create([
            'name' => 'Disposable',
            'permissions' => json_encode([]),
        ]);

        // Row delete now redirects back (Inertia router.delete) instead of the
        // legacy jQuery-AJAX 'ok' echo.
        $this->actingAs($this->admin)
            ->delete('/agentic-cms-laravel-admin/roles/'.$role->id.'/delete')
            ->assertRedirect();

        $this->assertDatabaseMissing('user_roles', ['id' => $role->id]);
    }

    public function test_validation_blocks_duplicate_role_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/roles/new')
            ->post('/agentic-cms-laravel-admin/roles/new', ['name' => 'Administrator']);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_user_with_panel_access_but_no_role_permission_is_blocked(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoRoles',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_user_roles' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/roles')->assertStatus(401);
    }
}
