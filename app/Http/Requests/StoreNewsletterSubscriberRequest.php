<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a manual (admin) newsletter subscriber add. Email must be unique so
 * the admin never creates a duplicate of an existing subscriber.
 */
class StoreNewsletterSubscriberRequest extends FormRequest
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
            'email' => ['required', 'email', 'unique:newsletter_subscribers,email'],
        ];
    }
}
