<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\CPanel\CPanelAuditLog;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Security audit log: authentication events are recorded, and the admin
 * Security screen renders + filters them. Screen gated by
 * manage_general_settings. Seeded admin = admin / agentic-cmsadmin123.
 */
class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    private const SCREEN = '/agentic-cms-laravel-admin/security';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    public function test_successful_login_is_audited(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->post('/login', ['email' => $admin->email, 'password' => 'agentic-cmsadmin123'])
            ->assertRedirect();

        $this->assertDatabaseHas('security_audit_log', ['action' => 'login', 'user_id' => $admin->id]);
    }

    public function test_failed_login_is_audited(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->post('/login', ['email' => $admin->email, 'password' => 'wrong-password']);

        $this->assertDatabaseHas('security_audit_log', ['action' => 'login_failed']);
    }

    public function test_admin_sees_the_security_screen(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)->get(self::SCREEN)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/security/Index')
                ->has('audit_log.data')
                ->has('actions'));
    }

    public function test_filter_limits_to_one_action(): void
    {
        CPanelAuditLog::create(['action' => 'login', 'created_at' => now()]);
        CPanelAuditLog::create(['action' => 'logout', 'created_at' => now()]);
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)->get(self::SCREEN.'?action=login')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filter', 'login')
                ->where('audit_log.data', fn ($data) => collect($data)->every(fn ($r) => $r['action'] === 'login')));
    }

    public function test_screen_requires_settings_permission(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoSettings',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_general_settings' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(self::SCREEN)->assertStatus(401);
    }
}
