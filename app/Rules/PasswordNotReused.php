<?php

namespace App\Rules;

use App\Http\Models\User;
use App\Services\Auth\PasswordHistoryService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects a password that reuses one of the user's recent passwords, per the
 * password_history_count security setting. A thin adapter over
 * PasswordHistoryService (the rule never touches the data layer directly).
 *
 * Passes silently when no user is resolved (e.g. an unknown reset email) — the
 * surrounding request handles that case; there is simply no history to check.
 */
class PasswordNotReused implements ValidationRule
{
    public function __construct(private ?User $user) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->user === null || ! is_string($value) || $value === '') {
            return;
        }

        if (app(PasswordHistoryService::class)->isReused($this->user, $value)) {
            $fail(trans('cpanel/security.password_reused'));
        }
    }
}
