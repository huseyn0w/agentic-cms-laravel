<?php

namespace Tests\Feature\Auth;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleOnlySocialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_google_service_is_configured(): void
    {
        $this->assertIsArray(config('services.google'));
        $this->assertArrayHasKey('redirect', config('services.google'));
    }

    public function test_removed_providers_are_not_configured(): void
    {
        $this->assertNull(config('services.facebook'));
        $this->assertNull(config('services.github'));
        $this->assertNull(config('services.linkedin'));
    }

    public function test_google_provider_route_resolves(): void
    {
        // Assert the route is registered and its constraint admits "google",
        // without depending on live Socialite/Google OAuth credentials
        // (a real HTTP call would either redirect or throw depending on
        // env-specific Socialite config, which is not a reliable signal).
        $route = collect(app('router')->getRoutes())
            ->first(fn ($r) => $r->uri() === 'login/{provider}');

        $this->assertNotNull($route, 'The login/{provider} route must be registered.');
        $this->assertTrue(
            $route->matches(Request::create('/login/google', 'GET'), false),
            'The login/{provider} route constraint must admit "google".'
        );
    }

    #[DataProvider('removedProviders')]
    public function test_removed_provider_routes_404(string $provider): void
    {
        $this->get("/login/$provider")->assertNotFound();
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function removedProviders(): array
    {
        return [['github'], ['facebook'], ['linkedin'], ['twitter']];
    }
}
