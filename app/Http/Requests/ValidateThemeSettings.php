<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Validation for the admin global theme settings form.
 *
 * The route is already gated by manage_general_settings; we re-assert it here.
 * accent_color is constrained to a hex colour and font_family to a safe CSS
 * font-stack charset, because both are injected verbatim into an inline <style>
 * on the public root Blade — validation is the injection guard.
 */
class ValidateThemeSettings extends FormRequest
{
    public function authorize()
    {
        return Auth::check()
            && Auth::user()->can('manage_general_settings', 'App\Http\Models\UserRoles');
    }

    protected function prepareForValidation()
    {
        // Normalise an empty string to null so a cleared field resets to the
        // shipped default rather than persisting an empty override.
        $this->merge([
            'site_title' => $this->filled('site_title') ? $this->input('site_title') : null,
            'accent_color' => $this->filled('accent_color') ? strtolower(trim((string) $this->input('accent_color'))) : null,
            'font_family' => $this->filled('font_family') ? trim((string) $this->input('font_family')) : null,
            'radius' => $this->filled('radius') ? $this->input('radius') : null,
        ]);
    }

    public function rules()
    {
        return [
            'site_title' => 'nullable|string|max:255',
            // #rgb or #rrggbb only — this value goes straight into CSS.
            'accent_color' => ['nullable', 'string', 'regex:/^#([0-9a-f]{3}|[0-9a-f]{6})$/'],
            // Letters, digits, spaces, and the handful of punctuation a CSS
            // font-family stack needs. No braces/semicolons/parens — no escape.
            'font_family' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9 ,\'"\-]+$/'],
            'radius' => 'nullable|integer|min:0|max:40',
        ];
    }
}
