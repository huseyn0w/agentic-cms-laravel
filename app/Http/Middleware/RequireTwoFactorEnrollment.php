<?php

namespace App\Http\Middleware;

use App\Http\Models\UserRoles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When `require_2fa_for_admins` is on, any authenticated user who can see the
 * admin panel but has not enrolled in 2FA is redirected to their profile to
 * enroll. No-op when the setting is off. The profile route is exempt so the
 * enrollment page itself always renders (no redirect loop); the /two-factor/*
 * enrollment endpoints live outside the admin group and are never seen here.
 * Mirrors EnsureEmailIsVerifiedWhenRequired.
 */
class RequireTwoFactorEnrollment
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! get_security_settings('require_2fa_for_admins')) {
            return $next($request);
        }

        if ($request->routeIs('cpanel_myprofile')) {
            return $next($request);
        }

        $user = $request->user();

        if ($user
            && $user->can('see_admin_panel', UserRoles::class)
            && ! $user->hasEnabledTwoFactor()) {
            return redirect()->route('cpanel_myprofile')
                ->with('status', trans('cpanel/twofactor.enrollment_required'));
        }

        return $next($request);
    }
}
