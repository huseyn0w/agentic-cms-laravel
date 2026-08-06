<?php

namespace App\Repositories;

use App\Http\Models\CPanel\CPanelSession;
use Illuminate\Support\Collection;

/**
 * Data access for the database session store, always scoped to a single user so
 * a caller can only ever see or revoke their own sessions.
 */
class CPanelSessionRepository extends BaseRepository
{
    public function __construct(CPanelSession $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    /**
     * The user's sessions, most-recently-active first.
     *
     * @return Collection<int, CPanelSession>
     */
    public function forUser(int $userId): Collection
    {
        return $this->model::query()
            ->where('user_id', $userId)
            ->orderByDesc('last_activity')
            ->get();
    }

    /**
     * Delete one of the user's sessions by id. Returns the number of rows
     * removed (0 when the id is not this user's session).
     */
    public function revoke(int $userId, string $sessionId): int
    {
        return $this->model::query()
            ->where('user_id', $userId)
            ->where('id', $sessionId)
            ->delete();
    }

    /**
     * Delete all of the user's sessions except the current one. Returns the
     * number of rows removed.
     */
    public function revokeOthers(int $userId, string $currentId): int
    {
        return $this->model::query()
            ->where('user_id', $userId)
            ->where('id', '!=', $currentId)
            ->delete();
    }
}
