<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Phase 7 (SEO/GEO): validation for the admin global SEO settings form.
 *
 * Authorization additionally requires the manage_general_settings ability (the
 * route is already gated by that middleware); checkbox fields are normalised to
 * real booleans in prepareForValidation so unchecked boxes persist as false.
 */
class ValidateSeoSettings extends FormRequest
{
    public function authorize()
    {
        return Auth::check()
            && Auth::user()->can('manage_general_settings', 'App\Http\Models\UserRoles');
    }

    protected function prepareForValidation()
    {
        // AI-crawler toggles (AEO) are owned by the dedicated AEO settings tab,
        // so the SEO form no longer normalises or persists them here.
        $this->merge([
            'discourage_search_engines' => $this->boolean('discourage_search_engines'),
            'sitemap_enabled' => $this->boolean('sitemap_enabled'),
        ]);
    }

    public function rules()
    {
        return [
            'title_separator' => 'required|string|max:8',
            'default_meta_description' => 'nullable|string|max:300',
            'default_og_image' => 'nullable|url|max:255',
            'og_site_name' => 'nullable|string|max:255',
            'twitter_handle' => 'nullable|string|max:50',
            'google_site_verification' => 'nullable|string|max:255',
            'bing_site_verification' => 'nullable|string|max:255',
            'ga4_measurement_id' => 'nullable|string|max:50',
            'gtm_container_id' => 'nullable|string|max:50',
            'discourage_search_engines' => 'boolean',
            'sitemap_enabled' => 'boolean',
            'robots_extra' => 'nullable|string|max:2000',
        ];
    }
}
