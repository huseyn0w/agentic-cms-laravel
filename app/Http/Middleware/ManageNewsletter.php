<?php

namespace App\Http\Middleware;

use App\Http\Models\UserRoles;
use Closure;
use Illuminate\Support\Facades\Auth;

class ManageNewsletter
{
    /**
     * Gate the admin newsletter routes behind the manage_newsletter capability.
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user()->cannot('manage_newsletter', UserRoles::class)) {
            abort(401);
        }

        return $next($request);
    }
}
