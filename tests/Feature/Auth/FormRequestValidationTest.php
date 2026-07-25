<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    private function passes(string $requestClass, array $data): bool
    {
        return Validator::make($data, (new $requestClass)->rules())->passes();
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->assertTrue($this->passes(LoginRequest::class, ['email' => 'a@b.com', 'password' => 'x']));
        $this->assertFalse($this->passes(LoginRequest::class, ['email' => '', 'password' => '']));
    }

    public function test_forgot_password_requires_valid_email(): void
    {
        $this->assertTrue($this->passes(ForgotPasswordRequest::class, ['email' => 'a@b.com']));
        $this->assertFalse($this->passes(ForgotPasswordRequest::class, ['email' => 'not-an-email']));
    }

    public function test_reset_password_requires_token_email_and_confirmed_password(): void
    {
        $this->assertTrue($this->passes(ResetPasswordRequest::class, [
            'token' => 't', 'email' => 'a@b.com', 'password' => 'password1', 'password_confirmation' => 'password1',
        ]));
        $this->assertFalse($this->passes(ResetPasswordRequest::class, [
            'token' => 't', 'email' => 'a@b.com', 'password' => 'password1', 'password_confirmation' => 'nope',
        ]));
    }

    public function test_register_rules_match_legacy_validator(): void
    {
        $this->assertFalse($this->passes(RegisterRequest::class, [
            'name' => '', 'username' => '', 'email' => 'bad', 'password' => 'short', 'password_confirmation' => 'x',
        ]));
        $this->assertTrue($this->passes(RegisterRequest::class, [
            'name' => 'A', 'username' => 'abc', 'email' => 'fresh@example.com',
            'password' => 'password1', 'password_confirmation' => 'password1',
        ]));
    }

    public function test_all_requests_authorize(): void
    {
        foreach ([LoginRequest::class, RegisterRequest::class, ForgotPasswordRequest::class, ResetPasswordRequest::class] as $class) {
            $this->assertTrue((new $class)->authorize());
        }
    }
}
