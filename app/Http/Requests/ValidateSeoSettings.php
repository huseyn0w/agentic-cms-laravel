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
        // Normalise the AI-crawler toggles to a full boolean map over the known
        // bot keys (config/ai_crawlers.php), so unknown keys are dropped and a
        // missing toggle defaults to allowed (true).
        $raw = (array) $this->input('ai_crawlers', []);
        $crawlers = [];
        foreach (array_keys(config('ai_crawlers', [])) as $key) {
            $crawlers[$key] = filter_var($raw[$key] ?? true, FILTER_VALIDATE_BOOLEAN);
        }

        $this->merge([
            'discourage_search_engines' => $this->boolean('discourage_search_engines'),
            'sitemap_enabled' => $this->boolean('sitemap_enabled'),
            'ai_crawlers' => $crawlers,
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
            'ai_crawlers' => 'array',
            'ai_crawlers.*' => 'boolean',
        ];
    }
}
