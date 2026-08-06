<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\User;
use App\Services\Auth\TwoFactorService;
use App\Services\CPanel\CPanelUserService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        RateLimiter::clear('');
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    private function enrolledUser(): User
    {
        $user = User::factory()->create(['role_id' => 2, 'password' => 'secret123']);
        $svc = app(CPanelUserService::class);
        $svc->startTwoFactorEnrollment($user, app(TwoFactorService::class)->generateSecret());
        $svc->confirmTwoFactor($user->refresh(), ['recover-aa-recover-bb']);

        return $user->refresh();
    }

    public function test_password_login_redirects_2fa_user_to_challenge_without_authenticating(): void
    {
        $user = $this->enrolledUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
    }

    public function test_valid_totp_completes_login(): void
    {
        $user = $this->enrolledUser();
        $this->post('/login', ['email' => $user->email, 'password' => 'secret123']);

        $code = (new Google2FA)->getCurrentOtp($user->two_factor_secret);
        $this->post('/two-factor/challenge', ['code' => $code])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('security_audit_log', ['action' => 'login', 'user_id' => $user->id]);
    }

    public function test_recovery_code_completes_login_and_is_consumed(): void
    {
        $user = $this->enrolledUser();
        $this->post('/login', ['email' => $user->email, 'password' => 'secret123']);

        $this->post('/two-factor/challenge', ['code' => 'recover-aa-recover-bb'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertSame([], $user->refresh()->two_factor_recovery_codes ?? []);
    }

    public function test_wrong_code_is_rejected_and_audited(): void
    {
        $user = $this->enrolledUser();
        $this->post('/login', ['email' => $user->email, 'password' => 'secret123']);

        $this->post('/two-factor/challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertDatabaseHas('security_audit_log', ['action' => '2fa_failed', 'user_id' => $user->id]);
    }

    public function test_challenge_without_pending_login_redirects_to_login(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect('/login');
    }
}
