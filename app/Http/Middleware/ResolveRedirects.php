<?php

namespace App\Http\Middleware;

use App\Services\RedirectService;
use Closure;
use Illuminate\Http\Request;

/**
 * Issues managed 301/302 redirects for old URLs (e.g. WordPress permalinks after
 * a migration). Runs early in the web stack and short-circuits with a redirect
 * before the front catch-all route resolves. Only GET/HEAD requests are checked,
 * and admin paths are skipped, so it can never interfere with the panel or form
 * posts. A miss is a cheap cached-map lookup and falls through untouched.
 */
class ResolveRedirects
{
    public function __construct(private RedirectService $redirects) {}

    public function handle(Request $request, Closure $next)
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        if ($request->is('agentic-cms-laravel-admin', 'agentic-cms-laravel-admin/*')) {
            return $next($request);
        }

        $match = $this->redirects->resolve($request->path());

        if ($match !== null) {
            $this->redirects->recordHit($request->path());

            return redirect($match['target'], $match['status']);
        }

        return $next($request);
    }
}
