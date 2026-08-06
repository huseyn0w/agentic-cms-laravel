<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class ValidateUserSettings extends FormRequest
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
        return $this->getRules();
    }

    /**
     * Return rules depends on user update or add new user page
     */
    private function getRules(): array
    {
        $userId = (int) $this->route('id');

        $route_name = Route::currentRouteName();

        $rules = [
            'name' => 'nullable|string',
            'surname' => 'nullable|string',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
            'about_me' => 'nullable|string',
            'facebook_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'google_url' => 'nullable|url',
            'linkedin_url' => 'nullable|url',
            'xing_url' => 'nullable|url',
            'role_id' => 'numeric',
            'gender' => 'nullable|in:male,female',
            'avatar' => 'nullable|string',
            'password' => ['sometimes', 'nullable', 'string', password_policy_rule(), 'same:password_confirmation'],
            'password_confirmation' => ['sometimes', 'nullable', 'string'],
        ];

        if ($route_name === 'cpanel_update_user_profile') {
            $email = ['required',
                'string',
                'email',
                'max:100',
                Rule::unique('users')->ignore($userId),
            ];

            $username = [
                'string',
                'min:5',
                'max:20',
                Rule::unique('users')->ignore($userId),
            ];

        } else {
            $email = 'email|required|max:100|unique:users';
            $username = 'string|min:5|max:20|unique:users';
        }

        $rules['email'] = $email;
        $rules['username'] = $username;

        return $rules;
    }
}
