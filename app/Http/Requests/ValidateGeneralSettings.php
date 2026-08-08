<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ValidateGeneralSettings extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    protected function prepareForValidation()
    {
        // Normalise the two toggles to real booleans before validation. This
        // accepts the legacy Blade "on"/absent as well as the JSON true/false
        // the Inertia form sends (the old `=== 'on'` check silently persisted
        // every Inertia-ticked box as 0).
        $this->merge([
            'membership' => $this->boolean('membership'),
            'email_verification' => $this->boolean('email_verification'),
            // An empty booking field means "no booking link" — store NULL, not
            // an empty string, so the theme's `bookingUrl ? …` check is clean.
            'booking_url' => trim((string) $this->input('booking_url')) ?: null,
        ]);
    }

    public function rules()
    {
        return [
            'website_name' => 'required|string',
            'tagline' => 'required|string',
            'posts_per_page' => 'required|integer',
            'comments_per_page' => 'required|integer',
            'contact_email' => 'required|email',
            'membership' => 'boolean',
            'email_verification' => 'boolean',
            'active_template_name' => 'nullable|string',
            'booking_url' => 'nullable|url|max:2000',
        ];
    }
}
