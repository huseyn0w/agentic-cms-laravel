<?php

namespace App\Http\Middleware;

use App\Services\Seo\SsrPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns server-side rendering on for the handful of routes that need it.
 *
 * Inertia reads `inertia.ssr.enabled` when it builds the response, which is
 * after the whole middleware stack has run on the way in - so writing the
 * decision into config here reaches it in time. The config value is a
 * per-request flag, never an env setting; see config/inertia.php.
 */
class EnableSsrOnPublicRoutes
{
    public function __construct(private readonly SsrPolicy $policy) {}

    public function handle(Request $request, Closure $next): Response
    {
        config(['inertia.ssr.enabled' => $this->policy->appliesTo($request)]);

        return $next($request);
    }
}
