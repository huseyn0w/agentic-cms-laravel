<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Validation for the admin login-protection form on the Security screen.
 *
 * The route is already gated by manage_general_settings; we re-assert it here.
 * Checkbox toggles are normalised to real booleans so unchecked boxes persist
 * as false. The auto-block threshold must sit at or above the short-throttle
 * limit, otherwise the block tier would trip first and the short throttle would
 * be dead configuration.
 */
class ValidateSecuritySettings extends FormRequest
{
    public function authorize()
    {
        return Auth::check()
            && Auth::user()->can('manage_general_settings', 'App\Http\Models\UserRoles');
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'login_throttle_enabled' => $this->boolean('login_throttle_enabled'),
            'login_block_enabled' => $this->boolean('login_block_enabled'),
            'require_2fa_for_admins' => $this->boolean('require_2fa_for_admins'),
        ]);
    }

    public function rules()
    {
        return [
            'login_throttle_enabled' => 'boolean',
            'login_max_attempts' => 'required|integer|min:1|max:100',
            'login_decay_minutes' => 'required|integer|min:1|max:1440',
            'login_block_enabled' => 'boolean',
            'login_block_threshold' => 'required|integer|min:1|max:1000|gte:login_max_attempts',
            'login_block_minutes' => 'required|integer|min:1|max:43200',
            'require_2fa_for_admins' => 'boolean',
        ];
    }
}
