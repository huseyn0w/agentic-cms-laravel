<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a public newsletter subscribe. The captcha rule is a no-op when no
 * reCAPTCHA keys are configured (dev/tests). The `website` honeypot is validated
 * loosely here; the controller treats a filled honeypot as a silent bot accept.
 */
class SubscribeNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'g-recaptcha-response' => ['nullable', 'captcha'],
            'email' => ['required', 'email'],
            'website' => ['nullable', 'string'], // honeypot; bots fill it
        ];
    }
}
