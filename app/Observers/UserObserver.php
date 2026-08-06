<?php

namespace App\Observers;

use App\Http\Models\User;
use App\Repositories\PasswordHistoryRepository;

/**
 * Records a user's password hash into password_histories whenever it is set or
 * changed, so the password-reuse policy can reject a later password matching a
 * recent one. Covers every write path (register, reset, self-service change,
 * admin update) uniformly because they all persist through the User model.
 */
class UserObserver
{
    public function created(User $user): void
    {
        $this->record($user);
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('password')) {
            $this->record($user);
        }
    }

    /**
     * Snapshot the already-hashed password (the setPasswordAttribute mutator
     * hashes on assignment). Skips accounts with no password (e.g. social-only).
     */
    private function record(User $user): void
    {
        if (empty($user->password)) {
            return;
        }

        app(PasswordHistoryRepository::class)->record($user->id, $user->password);
    }
}
