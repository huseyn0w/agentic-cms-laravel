<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\ValidateGeneralSettings;
use App\Services\CPanel\GeneralSettingsService;
use Inertia\Inertia;

class CPanelGeneralSettingController extends CPanelBaseController
{
    public function __construct(GeneralSettingsService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index()
    {
        $settings = $this->service->current();

        return Inertia::render('cpanel/settings/General', [
            'general_settings' => [
                'website_name' => $settings->website_name,
                'tagline' => $settings->tagline,
                'contact_email' => $settings->contact_email,
                'membership' => (bool) $settings->membership,
                'email_verification' => (bool) $settings->email_verification,
                'active_template_name' => $settings->active_template_name,
                'booking_url' => $settings->booking_url,
                'posts_per_page' => (int) $settings->posts_per_page,
                'comments_per_page' => (int) $settings->comments_per_page,
            ],
            'templates' => array_values(get_front_templates_array()),
        ]);
    }

    public function store(ValidateGeneralSettings $request)
    {
        $this->service->update(1, $request);

        return back()->with('success', __('cpanel/settings.general_settings_updates_success'));
    }
}
