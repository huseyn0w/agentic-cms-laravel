<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\ValidateSeoSettings;
use App\Services\CPanel\SeoSettingsService;
use Inertia\Inertia;

/**
 * Phase 7 (SEO/GEO): admin SEO settings page (global, singleton row id = 1).
 * Gated by the manage_general_settings middleware on the route group.
 */
class CPanelSeoSettingsController extends CPanelBaseController
{
    public function __construct(SeoSettingsService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index()
    {
        $seo = $this->service->currentOrNew();

        return Inertia::render('cpanel/settings/Seo', [
            'seo_settings' => [
                'title_separator' => $seo->title_separator,
                'default_meta_description' => $seo->default_meta_description,
                'default_og_image' => $seo->default_og_image,
                'og_site_name' => $seo->og_site_name,
                'twitter_handle' => $seo->twitter_handle,
                'google_site_verification' => $seo->google_site_verification,
                'bing_site_verification' => $seo->bing_site_verification,
                'ga4_measurement_id' => $seo->ga4_measurement_id,
                'gtm_container_id' => $seo->gtm_container_id,
                'discourage_search_engines' => (bool) $seo->discourage_search_engines,
                'sitemap_enabled' => (bool) $seo->sitemap_enabled,
                'robots_extra' => $seo->robots_extra,
            ],
        ]);
    }

    public function store(ValidateSeoSettings $request)
    {
        $this->service->save($request);

        return back()->with('success', __('cpanel/settings.seo_settings_updates_success'));
    }
}
