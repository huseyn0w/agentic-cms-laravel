<?php

namespace App\Http\Middleware;

use App\Http\Models\UserRoles;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManageMedia
{
    /**
     * Gate the media-library routes behind the dedicated manage_media
     * capability (previously they rode on manage_general_settings).
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user()->cannot('manage_media', UserRoles::class)) {
            abort(401);
        }

        return $next($request);
    }
}
