<?php

namespace App\Http\Middleware;

use App\Http\Models\UserRoles;
use Closure;
use Illuminate\Support\Facades\Auth;

class ManageMessages
{
    /**
     * Gate the admin contact-inbox routes behind the manage_messages capability.
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user()->cannot('manage_messages', UserRoles::class)) {
            abort(401);
        }

        return $next($request);
    }
}
