<?php

namespace Tests\Feature\Security;

use App\Http\Models\CPanel\CPanelSecuritySettings;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Site lockdown (private / pre-launch mode): when enabled, guests are
 * redirected to the login form on the public front-end; authenticated users
 * pass through. The login form and social-login entry points stay reachable so
 * a guest can still sign in. Default off, so existing installs are unaffected.
 */
class SiteLockdownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    private function lockdown(bool $enabled): void
    {
        CPanelSecuritySettings::firstOrNew(['id' => 1])
            ->forceFill(['site_lockdown_enabled' => $enabled])->save();
    }

    private function admin(): User
    {
        return User::where('username', 'admin')->firstOrFail();
    }

    public function test_guest_reaches_the_front_page_when_lockdown_is_off(): void
    {
        $this->lockdown(false);
        $this->get('/')->assertOk();
    }

    public function test_guest_is_redirected_to_login_when_lockdown_is_on(): void
    {
        $this->lockdown(true);
        $this->get('/')->assertRedirect('/login');
    }

    public function test_authenticated_user_passes_through_lockdown(): void
    {
        $this->lockdown(true);
        $this->actingAs($this->admin())->get('/')->assertOk();
    }

    public function test_login_form_stays_reachable_during_lockdown(): void
    {
        $this->lockdown(true);
        // /login lives outside the locked front group, so there is no redirect
        // loop and a guest can still sign in.
        $this->get('/login')->assertOk();
    }

    public function test_social_login_entry_point_stays_reachable_during_lockdown(): void
    {
        // login/{provider} sits inside the locked front group; it must be exempt
        // or Google sign-in breaks while the site is locked. It redirects to the
        // provider (302) rather than bouncing to /login.
        $this->lockdown(true);
        $this->get('/login/google')->assertRedirect();
        $this->get('/login/google')->assertRedirectContains('accounts.google.com');
    }
}
