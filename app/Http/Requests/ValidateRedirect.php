<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Validation for creating/updating a managed redirect. Gated by
 * manage_general_settings (also enforced on the route group). A target may be a
 * site-relative path or an absolute URL; the status is limited to 301/302.
 */
class ValidateRedirect extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check()
            && Auth::user()->can('manage_general_settings', 'App\Http\Models\UserRoles');
    }

    public function rules(): array
    {
        return [
            'source_path' => ['required', 'string', 'max:2000'],
            'target' => ['required', 'string', 'max:2000'],
            'status_code' => ['nullable', 'integer', 'in:301,302'],
        ];
    }
}
