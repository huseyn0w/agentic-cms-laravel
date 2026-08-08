<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Validation for the admin AEO settings form (AI-crawler allow/deny toggles,
 * stored on the SEO settings singleton's ai_crawlers field). The toggles are
 * normalised to a full boolean map over the known bot keys (config/ai_crawlers)
 * so unknown keys are dropped and a missing toggle defaults to allowed (true).
 */
class ValidateAeoSettings extends FormRequest
{
    public function authorize()
    {
        return Auth::check()
            && Auth::user()->can('manage_general_settings', 'App\Http\Models\UserRoles');
    }

    protected function prepareForValidation()
    {
        $raw = (array) $this->input('ai_crawlers', []);
        $crawlers = [];
        foreach (array_keys(config('ai_crawlers', [])) as $key) {
            $crawlers[$key] = filter_var($raw[$key] ?? true, FILTER_VALIDATE_BOOLEAN);
        }

        $this->merge(['ai_crawlers' => $crawlers]);
    }

    public function rules()
    {
        return [
            'ai_crawlers' => 'array',
            'ai_crawlers.*' => 'boolean',
        ];
    }
}
