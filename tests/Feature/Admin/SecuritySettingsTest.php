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
 * Login-protection settings on the Security screen: the form ships its current
 * values as a prop, saving persists the singleton, and the block threshold is
 * validated to sit at or above the short-throttle limit. Gated by
 * manage_general_settings. Seeded admin = admin / agentic-cmsadmin123.
 */
class SecuritySettingsTest extends TestCase
{
    use RefreshDatabase;

    private const SCREEN = '/agentic-cms-laravel-admin/security';

    private const SAVE = '/agentic-cms-laravel-admin/security/settings';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    private function admin(): User
    {
        return User::where('username', 'admin')->firstOrFail();
    }

    public function test_screen_ships_security_settings_prop_with_defaults(): void
    {
        $this->actingAs($this->admin())->get(self::SCREEN)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/security/Index')
                ->has('security_settings', fn (AssertableInertia $s) => $s
                    ->where('login_throttle_enabled', true)
                    ->where('login_max_attempts', 5)
                    ->where('login_decay_minutes', 1)
                    ->where('login_block_enabled', false)
                    ->where('login_block_threshold', 10)
                    ->where('login_block_minutes', 60)
                    ->where('require_2fa_for_admins', false)
                    ->where('password_min_length', 8)
                    ->where('password_require_mixed_case', false)
                    ->where('password_require_numbers', false)
                    ->where('password_require_symbols', false)
                    ->where('password_check_hibp', false)
                    ->where('hsts_enabled', false)
                    ->where('hsts_max_age', 15552000)
                    ->where('csp', '')
                    ->where('csp_report_only', false)));
    }

    public function test_saving_persists_the_singleton(): void
    {
        $this->actingAs($this->admin())->post(self::SAVE, [
            'login_throttle_enabled' => true,
            'login_max_attempts' => 3,
            'login_decay_minutes' => 5,
            'login_block_enabled' => true,
            'login_block_threshold' => 8,
            'login_block_minutes' => 120,
        ])->assertRedirect();

        $this->assertDatabaseHas('security_settings', [
            'id' => 1,
            'login_max_attempts' => 3,
            'login_decay_minutes' => 5,
            'login_block_enabled' => 1,
            'login_block_threshold' => 8,
            'login_block_minutes' => 120,
        ]);
    }

    public function test_saving_persists_require_2fa_toggle(): void
    {
        $this->actingAs($this->admin())->post(self::SAVE, [
            'login_throttle_enabled' => true,
            'login_max_attempts' => 5,
            'login_decay_minutes' => 1,
            'login_block_enabled' => false,
            'login_block_threshold' => 10,
            'login_block_minutes' => 60,
            'require_2fa_for_admins' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('security_settings', ['id' => 1, 'require_2fa_for_admins' => 1]);
    }

    public function test_saving_persists_password_policy(): void
    {
        $this->actingAs($this->admin())->post(self::SAVE, [
            'login_throttle_enabled' => true,
            'login_max_attempts' => 5,
            'login_decay_minutes' => 1,
            'login_block_enabled' => false,
            'login_block_threshold' => 10,
            'login_block_minutes' => 60,
            'password_min_length' => 14,
            'password_require_mixed_case' => true,
            'password_require_numbers' => true,
            'password_require_symbols' => true,
            'password_check_hibp' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('security_settings', [
            'id' => 1,
            'password_min_length' => 14,
            'password_require_mixed_case' => 1,
            'password_require_numbers' => 1,
            'password_require_symbols' => 1,
            'password_check_hibp' => 1,
        ]);
    }

    public function test_saving_persists_security_headers(): void
    {
        $this->actingAs($this->admin())->post(self::SAVE, [
            'login_throttle_enabled' => true,
            'login_max_attempts' => 5,
            'login_decay_minutes' => 1,
            'login_block_enabled' => false,
            'login_block_threshold' => 10,
            'login_block_minutes' => 60,
            'hsts_enabled' => true,
            'hsts_max_age' => 31536000,
            'csp' => "default-src 'self'",
            'csp_report_only' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('security_settings', [
            'id' => 1,
            'hsts_enabled' => 1,
            'hsts_max_age' => 31536000,
            'csp' => "default-src 'self'",
            'csp_report_only' => 1,
        ]);
    }

    public function test_block_threshold_must_be_at_least_max_attempts(): void
    {
        $this->actingAs($this->admin())->post(self::SAVE, [
            'login_max_attempts' => 10,
            'login_decay_minutes' => 1,
            'login_block_threshold' => 4, // below max — invalid
            'login_block_minutes' => 60,
        ])->assertSessionHasErrors('login_block_threshold');

        $this->assertDatabaseMissing('security_settings', ['id' => 1]);
    }

    public function test_saving_requires_settings_permission(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoSettings',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_general_settings' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->post(self::SAVE, [
            'login_max_attempts' => 3,
            'login_decay_minutes' => 1,
            'login_block_threshold' => 10,
            'login_block_minutes' => 60,
        ])->assertStatus(401);
    }
}
