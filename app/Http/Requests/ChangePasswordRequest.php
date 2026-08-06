<?php

namespace App\Http\Requests;

use App\Rules\PasswordNotReused;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ChangePasswordRequest extends FormRequest
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
    public function rules()
    {
        return [
            'g-recaptcha-response' => ['nullable', 'captcha'],
            'password' => ['string', password_policy_rule(), new PasswordNotReused(Auth::user()), 'same:password_confirmation'],
            'password_confirmation' => ['string'],
        ];
    }
}
