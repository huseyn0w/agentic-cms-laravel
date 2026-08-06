<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds hardening response headers. The baseline set is always sent with safe
 * defaults; HSTS and CSP are opt-in via the security_settings singleton so
 * production is never broken by a default policy. CSP is admin-authored (we
 * ship no default policy — a wrong one would break the Inertia/Vite frontend).
 */
class SecurityHeaders
{
    private const BASELINE = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'X-XSS-Protection' => '0',
        'Permissions-Policy' => 'browsing-topics=(), camera=(), microphone=(), geolocation=()',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::BASELINE as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        if ($request->secure() && get_security_settings('hsts_enabled')) {
            $maxAge = (int) (get_security_settings('hsts_max_age') ?? 15552000);
            $response->headers->set('Strict-Transport-Security', "max-age={$maxAge}; includeSubDomains");
        }

        $csp = trim((string) get_security_settings('csp'));
        if ($csp !== '') {
            $header = get_security_settings('csp_report_only')
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';
            $response->headers->set($header, $csp);
        }

        return $response;
    }
}
