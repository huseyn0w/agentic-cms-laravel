<?php

namespace App\Http\Controllers\CPanel;

use App\Services\CPanel\CPanelPluginService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin plugin manager: list discovered plugins and toggle them on/off. Pure
 * HTTP boundary — all logic/data access lives in CPanelPluginService.
 */
class CPanelPluginController extends CPanelBaseController
{
    public function __construct(CPanelPluginService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index(): Response
    {
        return Inertia::render('cpanel/plugins/List', [
            'plugins' => $this->service->listForAdmin()->all(),
        ]);
    }

    public function toggle(Request $request)
    {
        $data = $request->validate([
            'slug' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        if (! $this->service->toggle($data['slug'], (bool) $data['enabled'])) {
            abort(404);
        }

        return redirect()->route('cpanel_plugins_list')->with('success', __('cpanel/plugins.saved'));
    }
}
