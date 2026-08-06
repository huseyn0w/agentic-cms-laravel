<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Site lockdown (private / pre-launch mode). When enabled in security_settings,
 * guests hitting the public front-end are redirected to the login form;
 * authenticated users pass through so an admin can preview the live site.
 *
 * Applied to the public front route group only. The login form and the auth
 * routes live outside that group, so they stay reachable and there is no
 * redirect loop. The social-login entry points (login/{provider}) DO sit inside
 * the group, so they are exempted here or a guest could never sign in via
 * Google while the site is locked.
 *
 * Default off, so existing installs are unaffected until an admin opts in.
 */
class EnforceSiteLockdown
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! get_security_settings('site_lockdown_enabled')) {
            return $next($request);
        }

        if (Auth::check()) {
            return $next($request);
        }

        // Social-login entry points must stay reachable so a guest can sign in.
        if ($request->is('login/*')) {
            return $next($request);
        }

        return redirect()->guest(route('login'));
    }
}
