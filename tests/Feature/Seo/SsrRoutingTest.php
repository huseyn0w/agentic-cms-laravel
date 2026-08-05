<?php

namespace Tests\Feature\Seo;

use App\Http\Middleware\EnableSsrOnPublicRoutes;
use App\Services\Seo\SsrPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Tests\TestCase;

/**
 * Server-side rendering is turned on per request by EnableSsrOnPublicRoutes,
 * which asks SsrPolicy and writes the answer into `inertia.ssr.enabled` on the
 * way in. That flag is what Inertia reads when it builds the response, so
 * these tests pin exactly which requests get it: full-page GETs of an
 * allow-listed public route, and nothing else.
 *
 * The allow-list is empty in config until public pages move to Inertia, so
 * each case names its own route and lists it explicitly. The request is built
 * by hand rather than dispatched through the router, because the public
 * catch-all (`/{locale?}/{slug?}`) would otherwise swallow any probe path.
 */
class SsrRoutingTest extends TestCase
{
    private function requestFor(string $method, ?string $routeName): Request
    {
        $request = Request::create('/probe', $method);

        $route = new Route([$method], '/probe', []);

        if ($routeName !== null) {
            $route->name($routeName);
        }

        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    public function test_policy_allows_an_allow_listed_public_get(): void
    {
        config([
            'inertia.ssr.public.enabled' => true,
            'inertia.ssr.public.routes' => ['ssr_probe'],
        ]);

        $this->assertTrue(
            app(SsrPolicy::class)->appliesTo($this->requestFor('GET', 'ssr_probe'))
        );
    }

    public function test_policy_denies_when_the_master_switch_is_off(): void
    {
        config([
            'inertia.ssr.public.enabled' => false,
            'inertia.ssr.public.routes' => ['ssr_probe'],
        ]);

        $this->assertFalse(
            app(SsrPolicy::class)->appliesTo($this->requestFor('GET', 'ssr_probe'))
        );
    }

    public function test_policy_denies_a_route_not_on_the_allow_list(): void
    {
        config([
            'inertia.ssr.public.enabled' => true,
            'inertia.ssr.public.routes' => ['something_else'],
        ]);

        $this->assertFalse(
            app(SsrPolicy::class)->appliesTo($this->requestFor('GET', 'ssr_probe'))
        );
    }

    public function test_policy_denies_a_non_get_request(): void
    {
        config([
            'inertia.ssr.public.enabled' => true,
            'inertia.ssr.public.routes' => ['ssr_probe'],
        ]);

        $this->assertFalse(
            app(SsrPolicy::class)->appliesTo($this->requestFor('POST', 'ssr_probe'))
        );
    }

    public function test_policy_denies_an_unnamed_route(): void
    {
        config([
            'inertia.ssr.public.enabled' => true,
            'inertia.ssr.public.routes' => ['ssr_probe'],
        ]);

        $this->assertFalse(
            app(SsrPolicy::class)->appliesTo($this->requestFor('GET', null))
        );
    }

    public function test_middleware_writes_the_decision_into_config(): void
    {
        config([
            'inertia.ssr.enabled' => false,
            'inertia.ssr.public.enabled' => true,
            'inertia.ssr.public.routes' => ['ssr_probe'],
        ]);

        $middleware = new EnableSsrOnPublicRoutes(app(SsrPolicy::class));

        $middleware->handle(
            $this->requestFor('GET', 'ssr_probe'),
            fn () => new Response('ok')
        );

        $this->assertTrue(config('inertia.ssr.enabled'));
    }

    public function test_middleware_turns_ssr_off_for_a_request_that_does_not_qualify(): void
    {
        // Pre-set true so we prove the middleware actively writes false, rather
        // than a stale true simply surviving.
        config([
            'inertia.ssr.enabled' => true,
            'inertia.ssr.public.enabled' => true,
            'inertia.ssr.public.routes' => ['ssr_probe'],
        ]);

        $middleware = new EnableSsrOnPublicRoutes(app(SsrPolicy::class));

        $middleware->handle(
            $this->requestFor('GET', 'admin_dashboard'),
            fn () => new Response('ok')
        );

        $this->assertFalse(config('inertia.ssr.enabled'));
    }
}
