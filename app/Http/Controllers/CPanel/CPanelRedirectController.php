<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\ValidateRedirect;
use App\Services\RedirectService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Admin redirect manager. Gated by manage_general_settings (route group). Thin:
 * shaping + delegation to RedirectService.
 */
class CPanelRedirectController extends CPanelBaseController
{
    public function __construct(private RedirectService $redirects)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $search = is_string($search) ? $search : null;

        $redirects = $this->redirects->list($search, $this->per_page);

        $redirects->getCollection()->transform(fn ($r) => [
            'id' => $r->id,
            'source_path' => $r->source_path,
            'target' => $r->target,
            'status_code' => $r->status_code,
            'hits' => $r->hits,
        ]);

        return Inertia::render('cpanel/redirects/Index', [
            'redirects' => $redirects,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(ValidateRedirect $request)
    {
        $this->redirects->save(
            $request->validated('source_path'),
            $request->validated('target'),
            (int) ($request->validated('status_code') ?? 301),
        );

        return back()->with('success', __('cpanel/redirects.saved'));
    }

    public function destroy(int $id)
    {
        $this->redirects->delete($id);

        return back()->with('success', __('cpanel/redirects.deleted'));
    }
}
