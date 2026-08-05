<?php

namespace App\Services\Seo;

use Illuminate\Http\Request;

/**
 * Decides whether a request should be rendered on the server.
 *
 * Server-side rendering exists here for one reason: a crawler that does not
 * run JavaScript should still read the page. That reason only applies to
 * pages a crawler can reach, so the answer is yes for a short, explicit list
 * of public routes and no for everything else.
 */
class SsrPolicy
{
    public function appliesTo(Request $request): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        // Only a full page load renders HTML at all; an Inertia navigation
        // answers with JSON, and anything that is not a GET has no crawler
        // reading it.
        if (! $request->isMethod('GET')) {
            return false;
        }

        $name = $request->route()?->getName();

        return $name !== null && in_array($name, $this->routes(), true);
    }

    private function enabled(): bool
    {
        return (bool) config('inertia.ssr.public.enabled', false);
    }

    /** @return array<int, string> */
    private function routes(): array
    {
        return (array) config('inertia.ssr.public.routes', []);
    }
}
