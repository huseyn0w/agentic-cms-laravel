<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\ValidateThemeSettings;
use App\Services\CPanel\ThemeSettingsService;
use Inertia\Inertia;

/**
 * Admin theme settings page (global, singleton row id = 1) — tier-1 theming.
 * Gated by the manage_general_settings middleware on the route group.
 */
class CPanelThemeSettingsController extends CPanelBaseController
{
    public function __construct(ThemeSettingsService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index()
    {
        $theme = $this->service->currentOrNew();

        return Inertia::render('cpanel/settings/Theme', [
            'theme_settings' => [
                'site_title' => $theme->site_title,
                'accent_color' => $theme->accent_color,
                'font_family' => $theme->font_family,
                'radius' => $theme->radius,
            ],
        ]);
    }

    public function store(ValidateThemeSettings $request)
    {
        $this->service->save($request);

        return back()->with('success', __('cpanel/settings.theme_settings_updates_success'));
    }
}
