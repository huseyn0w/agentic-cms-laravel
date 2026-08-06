<?php

namespace Tests\Feature\Auth;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class TwoFactorColumnsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_two_factor_columns_encrypt_and_report_state(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $this->assertFalse($user->hasEnabledTwoFactor());

        $user->forceFill([
            'two_factor_secret' => 'ABCDEFGHIJ234567',
            'two_factor_recovery_codes' => ['aaaaaaaa-bbbbbbbb'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        $fresh = User::find($user->id);
        $this->assertTrue($fresh->hasEnabledTwoFactor());
        $this->assertSame('ABCDEFGHIJ234567', $fresh->two_factor_secret);
        $this->assertSame(['aaaaaaaa-bbbbbbbb'], $fresh->two_factor_recovery_codes);

        // Stored ciphertext is not the plaintext.
        $raw = $user->getConnection()->table('users')->where('id', $user->id)->value('two_factor_secret');
        $this->assertNotSame('ABCDEFGHIJ234567', $raw);
        $this->assertSame('ABCDEFGHIJ234567', Crypt::decryptString($raw));
    }

    public function test_require_2fa_setting_defaults_off(): void
    {
        $this->assertNull(get_security_settings('require_2fa_for_admins'));
    }
}
