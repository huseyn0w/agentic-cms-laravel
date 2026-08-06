<?php

namespace App\Services\Auth;

use App\Http\Models\User;
use App\Repositories\PasswordHistoryRepository;
use Illuminate\Support\Facades\Hash;

/**
 * Decides whether a candidate password reuses one of a user's recent passwords,
 * per the password_history_count security setting. Disabled (returns false)
 * when the setting is 0, so existing installs are unaffected.
 */
class PasswordHistoryService
{
    public function __construct(private PasswordHistoryRepository $repo) {}

    /**
     * True when $plaintext matches the user's current password or one of the
     * last N recorded hashes (N = password_history_count).
     */
    public function isReused(User $user, string $plaintext): bool
    {
        $count = (int) get_security_settings('password_history_count');

        if ($count < 1 || $plaintext === '') {
            return false;
        }

        $hashes = $this->repo->recentHashes($user->id, $count);

        // The current password may not yet be in history on the first change;
        // include it defensively so "change" to the same value is still blocked.
        if (! empty($user->password)) {
            $hashes[] = $user->password;
        }

        foreach ($hashes as $hash) {
            if ($hash !== '' && Hash::check($plaintext, $hash)) {
                return true;
            }
        }

        return false;
    }
}
