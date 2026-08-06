<?php

/**
 * AgenticCms-Laravel
 * File: CPanelUserRepository.phpCreated by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 09.08.2019
 */

namespace App\Repositories;

use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Doctrine\DBAL\Driver\PDOException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CPanelUserRepository extends BaseRepository
{
    protected $select_fields = [
        'id',
        'email',
        'username',
        'name',
        'surname',
        'gender',
        'country',
        'city',
        'role_id',
        'facebook_url',
        'twitter_url',
        'google_url',
        'instagram_url',
        'linkedin_url',
        'xing_url',
        'about_me',
        'created_at',
        'avatar',
    ];

    public function __construct(User $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    /** Total number of registered users. */
    public function countAll(): int
    {
        return (int) $this->model->count();
    }

    /**
     * Latest N usernames for the admin dashboard.
     */
    public function latestUsernames($count)
    {
        return $this->model->select('username')->orderBy('id', 'desc')->take($count)->get();
    }

    public function translatedOnlyOnly($count)
    {
        $fields = [
            'id',
            'username',
            'email',
            'name',
            'surname',
            'country',
            'city',
            'role_id',
        ];

        try {
            $data = ! empty($fields) ?
                $data = $this->model::select($fields)
                    ->with('role')
                    ->paginate($count)
                                    : false;
        } catch (QueryException $e) {
            throwAbort();
        } catch (PDOException $e) {
            throwAbort();
        } catch (\Error $e) {
            throwAbort();
        }

        return $data;
    }

    /**
     * Update a user from a validated request.
     *
     * Privileged columns (role_id, provider, provider_id) are stripped unless
     * the acting user is explicitly allowed to manage users, so they can never
     * be set through a self-service profile update.
     *
     * @param  int  $id
     * @param  FormRequest|array  $updatedRequest
     * @return bool
     */
    public function update($id, $updatedRequest)
    {
        $data = is_array($updatedRequest) ? $updatedRequest : $updatedRequest->validated();

        unset($data['password_confirmation']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (! $this->canManageUsers()) {
            unset($data['role_id']);
        }

        // Provider fields are owned by the social-login flow only.
        unset($data['provider'], $data['provider_id']);

        return parent::update($id, $data);
    }

    private function canManageUsers(): bool
    {
        $user = Auth::user();

        return $user && $user->can('manage_users', UserRoles::class);
    }

    public function startTwoFactorEnrollment(User $user, string $secret): void
    {
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /** @param  array<int, string>  $codes */
    public function confirmTwoFactorEnrollment(User $user, array $codes): void
    {
        $user->forceFill([
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();
    }

    public function disableTwoFactor(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /** @param  array<int, string>  $codes */
    public function replaceTwoFactorRecoveryCodes(User $user, array $codes): void
    {
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();
    }

    public function consumeTwoFactorRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        foreach ($codes as $i => $stored) {
            if (hash_equals((string) $stored, $code)) {
                unset($codes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }
}
