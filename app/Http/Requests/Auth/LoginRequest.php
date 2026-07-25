<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        // The field is named `email` but accepts a username too; the controller's
        // credentials() decides which column to match. Keep validation permissive.
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
