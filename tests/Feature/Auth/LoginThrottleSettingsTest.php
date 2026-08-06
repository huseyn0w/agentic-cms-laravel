<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\CPanel\CPanelSecuritySettings;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * The login throttle reads its knobs from the security_settings singleton
 * instead of the old hardcoded maxAttempts=5 / decay=1. Covers: configured
 * limit, the master off-switch, and the longer auto-block tier tripping at its
 * own (lower) threshold independent of the short throttle.
 */
class LoginThrottleSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        RateLimiter::clear('');
    }

    private function settings(array $overrides): void
    {
        CPanelSecuritySettings::firstOrNew(['id' => 1])->fill(array_merge([
            'login_throttle_enabled' => true,
            'login_max_attempts' => 5,
            'login_decay_minutes' => 1,
            'login_block_enabled' => false,
            'login_block_threshold' => 10,
            'login_block_minutes' => 60,
        ], $overrides))->save();
    }

    private function attempt(User $user)
    {
        return $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ]);
    }

    public function test_lockout_uses_configured_max_attempts(): void
    {
        $this->settings(['login_max_attempts' => 2]);
        $user = User::factory()->create(['role_id' => 2, 'password' => 'secret123']);

        $this->attempt($user)->assertSessionHasErrors('email');
        $this->attempt($user)->assertSessionHasErrors('email');

        // 3rd attempt is now locked out (limit is 2, not the old default 5).
        $this->attempt($user)->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts',
            session('errors')->first('email')
        );
    }

    public function test_disabled_throttle_never_locks_out(): void
    {
        $this->settings(['login_throttle_enabled' => false, 'login_max_attempts' => 2]);
        $user = User::factory()->create(['role_id' => 2, 'password' => 'secret123']);

        for ($i = 0; $i < 8; $i++) {
            $this->attempt($user)->assertSessionHasErrors('email');
        }

        // Still the plain "wrong credentials" error, never the throttle message.
        $this->assertStringNotContainsString(
            'Too many login attempts',
            session('errors')->first('email')
        );
    }

    public function test_auto_block_tier_trips_at_its_own_threshold(): void
    {
        // Short throttle set high so it never fires within this test; only the
        // auto-block tier (threshold 3) can produce the lockout here.
        $this->settings([
            'login_max_attempts' => 50,
            'login_block_enabled' => true,
            'login_block_threshold' => 3,
        ]);
        $user = User::factory()->create(['role_id' => 2, 'password' => 'secret123']);

        $this->attempt($user)->assertSessionHasErrors('email');
        $this->attempt($user)->assertSessionHasErrors('email');
        $this->attempt($user)->assertSessionHasErrors('email');

        // 4th attempt is blocked by the auto-block tier.
        $this->attempt($user);
        $this->assertStringContainsString(
            'Too many login attempts',
            session('errors')->first('email')
        );
    }
}
