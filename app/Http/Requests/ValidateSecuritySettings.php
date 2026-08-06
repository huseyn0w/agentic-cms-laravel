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
            'password_require_mixed_case' => $this->boolean('password_require_mixed_case'),
            'password_require_numbers' => $this->boolean('password_require_numbers'),
            'password_require_symbols' => $this->boolean('password_require_symbols'),
            'password_check_hibp' => $this->boolean('password_check_hibp'),
            'hsts_enabled' => $this->boolean('hsts_enabled'),
            'csp_report_only' => $this->boolean('csp_report_only'),
            'site_lockdown_enabled' => $this->boolean('site_lockdown_enabled'),
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
            'password_min_length' => 'sometimes|integer|min:6|max:255',
            'password_require_mixed_case' => 'boolean',
            'password_require_numbers' => 'boolean',
            'password_require_symbols' => 'boolean',
            'password_check_hibp' => 'boolean',
            'hsts_enabled' => 'boolean',
            'hsts_max_age' => 'sometimes|integer|min:0|max:63072000',
            'csp' => 'sometimes|nullable|string|max:4000',
            'csp_report_only' => 'boolean',
            'admin_ip_allowlist' => 'sometimes|nullable|string|max:4000',
            'site_lockdown_enabled' => 'boolean',
            'password_history_count' => 'sometimes|integer|min:0|max:24',
        ];
    }
}
