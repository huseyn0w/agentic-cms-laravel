<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_enable_then_confirm_activates_2fa_and_issues_codes(): void
    {
        $user = User::factory()->create(['role_id' => 2, 'password' => 'secret123']);

        $enable = $this->actingAs($user)->post('/two-factor/enable');
        $enable->assertRedirect();
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertFalse($user->hasEnabledTwoFactor());

        $code = (new Google2FA)->getCurrentOtp($user->two_factor_secret);
        $confirm = $this->actingAs($user)->post('/two-factor/confirm', ['code' => $code]);
        $confirm->assertRedirect();
        $confirm->assertSessionHas('two_factor_recovery_codes');

        $user->refresh();
        $this->assertTrue($user->hasEnabledTwoFactor());
        $this->assertCount(8, $user->two_factor_recovery_codes);
        $this->assertDatabaseHas('security_audit_log', ['action' => '2fa_enabled', 'user_id' => $user->id]);
    }

    public function test_confirm_rejects_a_wrong_code(): void
    {
        $user = User::factory()->create(['role_id' => 2, 'password' => 'secret123']);
        $this->actingAs($user)->post('/two-factor/enable');

        $this->actingAs($user)->post('/two-factor/confirm', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->refresh()->hasEnabledTwoFactor());
    }

    public function test_disable_requires_current_password_and_clears_columns(): void
    {
        $user = User::factory()->create(['role_id' => 2, 'password' => 'secret123']);
        $this->actingAs($user)->post('/two-factor/enable');
        $secret = $user->refresh()->two_factor_secret;
        $code = (new Google2FA)->getCurrentOtp($secret);
        $this->actingAs($user)->post('/two-factor/confirm', ['code' => $code]);

        // Wrong password refused.
        $this->actingAs($user)->delete('/two-factor', ['password' => 'nope'])
            ->assertSessionHasErrors('password');
        $this->assertTrue($user->refresh()->hasEnabledTwoFactor());

        // Correct password disables.
        $this->actingAs($user)->delete('/two-factor', ['password' => 'secret123'])->assertRedirect();
        $user->refresh();
        $this->assertFalse($user->hasEnabledTwoFactor());
        $this->assertNull($user->two_factor_secret);
        $this->assertDatabaseHas('security_audit_log', ['action' => '2fa_disabled', 'user_id' => $user->id]);
    }
}
