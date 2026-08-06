<?php

namespace App\Repositories;

use App\Http\Models\PasswordHistory;

/**
 * Persistence for per-user password history. Rows are append-only and pruned to
 * a hard cap so the table stays bounded regardless of the configured policy.
 */
class PasswordHistoryRepository extends BaseRepository
{
    /**
     * Upper bound on rows kept per user. The reuse policy reads at most this
     * many, so keeping more would never be consulted.
     */
    private const CAP = 24;

    public function __construct(PasswordHistory $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    /**
     * Append a hashed password for the user, then prune older rows past the cap.
     */
    public function record(int $userId, string $hash): void
    {
        $this->model::create(['user_id' => $userId, 'password' => $hash]);
        $this->prune($userId);
    }

    /**
     * The user's most-recent password hashes, newest first, up to $limit.
     *
     * @return array<int, string>
     */
    public function recentHashes(int $userId, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        return $this->model::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('password')
            ->all();
    }

    /**
     * Drop rows beyond the hard cap for the user (keeps the newest CAP rows).
     */
    private function prune(int $userId): void
    {
        $keep = $this->model::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit(self::CAP)
            ->pluck('id');

        $this->model::query()
            ->where('user_id', $userId)
            ->whereNotIn('id', $keep)
            ->delete();
    }
}
