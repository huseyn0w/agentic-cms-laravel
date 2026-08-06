<?php

namespace Tests\Feature\Auth;

use App\Http\Models\CPanel\CPanelSecuritySettings;
use App\Http\Models\User;
use App\Services\Auth\TwoFactorService;
use App\Services\CPanel\CPanelUserService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequireTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    private function require2fa(bool $on): void
    {
        CPanelSecuritySettings::firstOrNew(['id' => 1])
            ->forceFill(['require_2fa_for_admins' => $on])->save();
    }

    public function test_off_is_a_noop(): void
    {
        $this->require2fa(false);
        $admin = User::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin)->get('/agentic-cms-laravel-admin')->assertOk();
    }

    public function test_admin_without_2fa_is_redirected_to_enrollment_when_required(): void
    {
        $this->require2fa(true);
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)->get('/agentic-cms-laravel-admin')
            ->assertRedirect(route('cpanel_myprofile'));

        // The profile page itself stays reachable so the admin can enroll.
        $this->actingAs($admin)->get(route('cpanel_myprofile'))->assertOk();
    }

    public function test_admin_with_2fa_passes(): void
    {
        $this->require2fa(true);
        $admin = User::where('username', 'admin')->firstOrFail();
        $svc = app(CPanelUserService::class);
        $svc->startTwoFactorEnrollment($admin, app(TwoFactorService::class)->generateSecret());
        $svc->confirmTwoFactor($admin->refresh(), ['aa-bb']);

        $this->actingAs($admin->refresh())->get('/agentic-cms-laravel-admin')->assertOk();
    }
}
