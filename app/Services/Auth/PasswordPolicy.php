<?php

namespace App\Services\Auth;

use Illuminate\Validation\Rules\Password;

/**
 * Builds a Laravel password-strength rule from the security_settings singleton.
 * A fresh Password instance is returned on every call because the rule is
 * mutable (its builder methods mutate and return $this) — sharing one would
 * leak configuration across requests. Falls back to the shipped defaults
 * (min length 8, no other constraints, HIBP off) when the row/columns are
 * absent.
 */
class PasswordPolicy
{
    public function rule(): Password
    {
        $min = (int) (get_security_settings('password_min_length') ?? 8);
        $rule = Password::min(max(1, $min));

        if (get_security_settings('password_require_mixed_case')) {
            $rule->mixedCase();
        }

        if (get_security_settings('password_require_numbers')) {
            $rule->numbers();
        }

        if (get_security_settings('password_require_symbols')) {
            $rule->symbols();
        }

        if (get_security_settings('password_check_hibp')) {
            $rule->uncompromised();
        }

        return $rule;
    }
}
