<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\ValidateGeoSettings;
use App\Services\CPanel\GeoSettingsService;
use Inertia\Inertia;

/**
 * Admin GEO settings page (global, singleton row id = 1).
 * Gated by the manage_general_settings middleware on the route group.
 */
class CPanelGeoSettingsController extends CPanelBaseController
{
    public function __construct(GeoSettingsService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index()
    {
        $geo = $this->service->currentOrNew();

        return Inertia::render('cpanel/settings/Geo', [
            'geo_settings' => [
                'business_name' => $geo->business_name,
                'business_type' => $geo->business_type ?? 'Organization',
                'description' => $geo->description,
                'founder_name' => $geo->founder_name,
                'services' => $geo->services,
                'service_area' => $geo->service_area,
                'contact_email' => $geo->contact_email,
                'contact_phone' => $geo->contact_phone,
                'address' => $geo->address,
                'same_as' => $geo->same_as,
                'faq' => $geo->faq,
                'emit_jsonld' => (bool) ($geo->emit_jsonld ?? true),
                'include_in_llms' => (bool) ($geo->include_in_llms ?? true),
            ],
        ]);
    }

    public function store(ValidateGeoSettings $request)
    {
        $this->service->save($request);

        return back()->with('success', __('cpanel/settings.geo_settings_updates_success'));
    }
}
