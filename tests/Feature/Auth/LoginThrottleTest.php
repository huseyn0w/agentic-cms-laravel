<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        RateLimiter::clear('');
    }

    public function test_lockout_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['role_id' => 2, 'password' => 'secret123']);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'wrong',
            ])->assertSessionHasErrors('email');
        }

        // 6th attempt is locked out; the error message is the throttle string.
        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts',
            session('errors')->first('email')
        );
    }
}
