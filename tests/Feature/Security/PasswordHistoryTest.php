<?php

namespace Tests\Feature\Security;

use App\Http\Models\CPanel\CPanelSecuritySettings;
use App\Http\Models\User;
use App\Services\Auth\PasswordHistoryService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Password-reuse policy: the UserObserver snapshots each password into
 * password_histories, and PasswordHistoryService rejects a candidate that
 * matches the current password or one of the last N (password_history_count).
 * N = 0 disables the policy.
 */
class PasswordHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function setCount(int $n): void
    {
        CPanelSecuritySettings::firstOrNew(['id' => 1])
            ->forceFill(['password_history_count' => $n])->save();
    }

    /** Create a user and set a known plaintext password (mutator hashes once). */
    private function userWithPassword(string $plaintext): User
    {
        $user = User::factory()->create();
        $user->password = $plaintext;
        $user->save();

        return $user->fresh();
    }

    private function service(): PasswordHistoryService
    {
        return app(PasswordHistoryService::class);
    }

    public function test_observer_records_a_history_row_on_password_change(): void
    {
        $user = $this->userWithPassword('PlainOne1!');

        $this->assertDatabaseHas('password_histories', ['user_id' => $user->id]);
    }

    public function test_flags_reuse_of_the_current_password_when_enabled(): void
    {
        $this->setCount(3);
        $user = $this->userWithPassword('PlainOne1!');

        $this->assertTrue($this->service()->isReused($user, 'PlainOne1!'));
        $this->assertFalse($this->service()->isReused($user, 'BrandNew9!'));
    }

    public function test_policy_is_disabled_when_count_is_zero(): void
    {
        $this->setCount(0);
        $user = $this->userWithPassword('PlainOne1!');

        $this->assertFalse($this->service()->isReused($user, 'PlainOne1!'));
    }

    public function test_flags_an_earlier_password_still_within_the_window(): void
    {
        $this->setCount(3);
        $user = $this->userWithPassword('PlainOne1!');
        $user->password = 'PlainTwo2!';
        $user->save();
        $user->password = 'PlainThree3!';
        $user->save();
        $user = $user->fresh();

        // PlainOne1! is one of the last 3 → reused.
        $this->assertTrue($this->service()->isReused($user, 'PlainOne1!'));
    }

    public function test_forgets_a_password_that_fell_outside_the_window(): void
    {
        $this->setCount(1);
        $user = $this->userWithPassword('PlainOne1!');
        $user->password = 'PlainTwo2!';
        $user->save();
        $user->password = 'PlainThree3!';
        $user->save();
        $user = $user->fresh();

        // Only the newest password is remembered → the oldest is allowed again.
        $this->assertFalse($this->service()->isReused($user, 'PlainOne1!'));
    }

    public function test_change_password_route_rejects_a_reused_password(): void
    {
        $this->setCount(2);
        $user = $this->userWithPassword('PlainOne1!');

        $this->actingAs($user)->put('/profile/change_password', [
            'current_password' => 'PlainOne1!',
            'password' => 'PlainOne1!',
            'password_confirmation' => 'PlainOne1!',
        ])->assertSessionHasErrors('password');
    }

    public function test_change_password_route_accepts_a_fresh_password(): void
    {
        $this->setCount(2);
        $user = $this->userWithPassword('PlainOne1!');

        $this->actingAs($user)->put('/profile/change_password', [
            'current_password' => 'PlainOne1!',
            'password' => 'FreshPass9!',
            'password_confirmation' => 'FreshPass9!',
        ])->assertSessionHasNoErrors();
    }
}
