<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $default = \Config::get('app.locale');
        $isAdmin = $request->is('agentic-cms-laravel-admin', 'agentic-cms-laravel-admin/*');

        if (! empty($request->route('lang'))) {
            // Admin editor routes carry a `lang` route param and persist it, so
            // admin language switching stays session-based.
            $locale = $request->route('lang');
            \Session::put('locale', $locale);
        } elseif (! $isAdmin && lang_exist((string) $request->route('locale'))) {
            // Front: the URL prefix is the source of truth. Never touch the
            // session, so hovering/prefetching a language link can't switch it.
            $locale = $request->route('locale');
        } elseif ($isAdmin) {
            // Admin list pages (no lang param): fall back to the stored locale.
            $locale = session('locale') ?: $default;
        } else {
            // Front route without a locale prefix: the default locale, ignoring
            // any stale session value left over from the admin panel.
            $locale = $default;
        }

        \App::setLocale(lang_exist($locale) ? $locale : $default);

        return $next($request);
    }
}
