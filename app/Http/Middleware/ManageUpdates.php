<?php

namespace App\Http\Middleware;

use App\Http\Models\UserRoles;
use Closure;
use Illuminate\Support\Facades\Auth;

class ManageUpdates
{
    /**
     * Gate the admin core-update routes behind the manage_updates capability.
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user()->cannot('manage_updates', UserRoles::class)) {
            abort(401);
        }

        return $next($request);
    }
}
