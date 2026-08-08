<?php

namespace App\Http\Middleware;

use App\Http\Models\UserRoles;
use Closure;
use Illuminate\Support\Facades\Auth;

class ManageContent
{
    /**
     * Gate the generic plugin content-type CRUD behind the manage_content
     * capability.
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user()->cannot('manage_content', UserRoles::class)) {
            abort(401);
        }

        return $next($request);
    }
}
