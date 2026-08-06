<?php

namespace Tests\Feature\Auth;

use App\Http\Models\CPanel\CPanelSecuritySettings;
use App\Services\Auth\PasswordPolicy;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use ReflectionClass;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function policy(array $overrides = []): void
    {
        CPanelSecuritySettings::firstOrNew(['id' => 1])->fill(array_merge([
            'password_min_length' => 8,
            'password_require_mixed_case' => false,
            'password_require_numbers' => false,
            'password_require_symbols' => false,
            'password_check_hibp' => false,
        ], $overrides))->save();
    }

    private function passes(string $password): bool
    {
        return ! Validator::make(
            ['password' => $password],
            ['password' => [app(PasswordPolicy::class)->rule()]]
        )->fails();
    }

    public function test_default_enforces_min_length_eight(): void
    {
        $this->policy();
        $this->assertFalse($this->passes('1234567'));   // 7 chars
        $this->assertTrue($this->passes('12345678'));    // 8 chars
    }

    public function test_configured_min_length_is_used(): void
    {
        $this->policy(['password_min_length' => 12]);
        $this->assertFalse($this->passes('12345678'));   // 8 < 12
        $this->assertTrue($this->passes('123456789012')); // 12
    }

    public function test_mixed_case_requirement(): void
    {
        $this->policy(['password_require_mixed_case' => true]);
        $this->assertFalse($this->passes('lowercase123'));
        $this->assertTrue($this->passes('MixedCase123'));
    }

    public function test_numbers_and_symbols_requirements(): void
    {
        $this->policy(['password_require_numbers' => true]);
        $this->assertFalse($this->passes('NoDigitsHere'));
        $this->assertTrue($this->passes('HasDigit1Here'));

        $this->policy(['password_require_symbols' => true]);
        $this->assertFalse($this->passes('NoSymbols123'));
        $this->assertTrue($this->passes('Has#Symbol123'));
    }

    public function test_hibp_toggle_wires_the_uncompromised_constraint(): void
    {
        $this->policy(['password_check_hibp' => true]);
        $rule = app(PasswordPolicy::class)->rule();

        $prop = (new ReflectionClass($rule))->getProperty('uncompromised');
        $prop->setAccessible(true);
        $this->assertTrue($prop->getValue($rule));
    }
}
