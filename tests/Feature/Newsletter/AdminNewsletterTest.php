<?php

namespace Tests\Feature\Newsletter;

use App\Http\Models\NewsletterSubscriber;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminNewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function adminUser(): User
    {
        // The seeded Administrator role (id 1) has every permission.
        return User::factory()->create(['role_id' => 1]);
    }

    public function test_panel_user_without_manage_newsletter_is_denied(): void
    {
        $this->markTestSkipped('route added in Task 7');

        $role = UserRoles::create([
            'name' => 'PanelNoNewsletter',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_newsletter' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/newsletter')->assertStatus(401);
    }

    public function test_seeded_administrator_has_manage_newsletter(): void
    {
        // UserPolicy reads Auth::user() in its constructor, so the user must be
        // authenticated for can() to resolve their role permissions.
        $this->actingAs($this->adminUser());

        $this->assertTrue(auth()->user()->can('manage_newsletter', UserRoles::class));
    }

    public function test_permission_row_exists(): void
    {
        $this->assertDatabaseHas('user_permissions', ['name' => 'manage_newsletter']);
    }

    public function test_migration_backfills_full_access_role(): void
    {
        // A full-access role created WITHOUT the flag (simulating a live install
        // before this migration) is backfilled to 1 by re-running up().
        $role = UserRoles::create([
            'name' => 'LegacyAdmin',
            'permissions' => json_encode([
                'see_admin_panel' => 1, 'manage_users' => 1, 'manage_posts' => 1,
            ]),
        ]);

        (new \AddManageNewsletterPermission)->up();

        $perms = json_decode(DB::table('user_roles')->find($role->id)->permissions, true);
        $this->assertSame(1, $perms['manage_newsletter']);
    }
}
