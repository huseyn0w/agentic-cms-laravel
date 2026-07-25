<?php

namespace Tests\Feature\Auth;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthRoutesResolveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function authRouteNames(): array
    {
        return [
            'login' => ['login'],
            'logout' => ['logout'],
            'register' => ['register'],
            'password.request' => ['password.request'],
            'password.email' => ['password.email'],
            'password.reset' => ['password.reset'],
            'password.update' => ['password.update'],
            'verification.notice' => ['verification.notice'],
            'verification.verify' => ['verification.verify'],
            'verification.resend' => ['verification.resend'],
        ];
    }

    #[DataProvider('authRouteNames')]
    public function test_auth_route_is_registered(string $name): void
    {
        $this->assertTrue(Route::has($name), "Route [$name] must exist");
    }

    public function test_login_page_still_renders(): void
    {
        $this->get('/login')->assertStatus(200);
    }
}
