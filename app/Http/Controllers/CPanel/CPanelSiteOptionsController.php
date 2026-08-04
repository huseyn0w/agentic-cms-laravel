<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\ValidateSiteOptions;
use App\Services\CPanel\SiteOptionsService;
use Inertia\Inertia;

class CPanelSiteOptionsController extends CPanelBaseController
{
    public function __construct(SiteOptionsService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index()
    {
        $options = $this->service->current();

        return Inertia::render('cpanel/settings/SiteOptions', [
            'site_options' => [
                'logo_url' => $options->logo_url,
                'copyright' => $options->copyright,
                'linkedin_url' => $options->linkedin_url,
                'github_url' => $options->github_url,
            ],
        ]);
    }

    public function store(ValidateSiteOptions $request)
    {
        $this->service->update(1, $request);

        return back()->with('success', __('cpanel/settings.site_options_updates_success'));
    }
}
