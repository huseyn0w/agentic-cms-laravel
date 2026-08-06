<?php

namespace Tests\Feature\Auth;

use App\Http\Models\User;
use App\Services\CPanel\CPanelUserService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private CPanelUserService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->svc = app(CPanelUserService::class);
    }

    public function test_enroll_confirm_consume_and_disable(): void
    {
        $user = User::factory()->create(['role_id' => 2]);

        $this->svc->startTwoFactorEnrollment($user, 'ABCDEFGHIJ234567');
        $user->refresh();
        $this->assertFalse($user->hasEnabledTwoFactor()); // pending, not confirmed
        $this->assertSame('ABCDEFGHIJ234567', $user->two_factor_secret);

        $this->svc->confirmTwoFactor($user, ['code-one-aaaaaaaa', 'code-two-bbbbbbbb']);
        $user->refresh();
        $this->assertTrue($user->hasEnabledTwoFactor());

        $this->assertTrue($this->svc->consumeTwoFactorRecoveryCode($user, 'code-one-aaaaaaaa'));
        $user->refresh();
        $this->assertSame(['code-two-bbbbbbbb'], array_values($user->two_factor_recovery_codes));
        // A consumed code cannot be reused.
        $this->assertFalse($this->svc->consumeTwoFactorRecoveryCode($user, 'code-one-aaaaaaaa'));

        $this->svc->disableTwoFactor($user);
        $user->refresh();
        $this->assertFalse($user->hasEnabledTwoFactor());
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
    }
}
