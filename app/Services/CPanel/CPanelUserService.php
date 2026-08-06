<?php

namespace App\Services\CPanel;

use App\Http\Controllers\CPanel\CPanelUserController;
use App\Http\Models\User;
use App\Repositories\CPanelUserRepository;
use App\Services\BaseCrudService;

/**
 * Domain service for admin user management.
 *
 * Owns all data access for {@see CPanelUserController};
 * the controller never touches the repository directly.
 */
class CPanelUserService extends BaseCrudService
{
    public function __construct(private CPanelUserRepository $repo)
    {
        parent::__construct($repo);
    }

    public function startTwoFactorEnrollment(User $user, string $secret): void
    {
        $this->repo->startTwoFactorEnrollment($user, $secret);
    }

    /** @param  array<int, string>  $codes */
    public function confirmTwoFactor(User $user, array $codes): void
    {
        $this->repo->confirmTwoFactorEnrollment($user, $codes);
    }

    public function disableTwoFactor(User $user): void
    {
        $this->repo->disableTwoFactor($user);
    }

    /** @param  array<int, string>  $codes */
    public function replaceTwoFactorRecoveryCodes(User $user, array $codes): void
    {
        $this->repo->replaceTwoFactorRecoveryCodes($user, $codes);
    }

    public function consumeTwoFactorRecoveryCode(User $user, string $code): bool
    {
        return $this->repo->consumeTwoFactorRecoveryCode($user, $code);
    }
}
