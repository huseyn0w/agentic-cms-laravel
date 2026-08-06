<?php

/**
 * AgenticCms-Laravel
 * File: CPanelUserRepository.phpCreated by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 09.08.2019
 */

namespace App\Repositories;

use App\Http\Models\Comments;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CPanelCommentRepository extends BaseRepository
{
    public function __construct(Comments $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    /**
     * Latest N comments (comment body only) for the admin dashboard.
     */
    public function latestComments($count)
    {
        return $this->model->select('comment')->orderBy('id', 'desc')->take($count)->get();
    }

    /** Total number of comments. */
    public function countAll(): int
    {
        return (int) $this->model->count();
    }

    /** Comments awaiting moderation (status != approved). */
    public function countPending(): int
    {
        return (int) $this->model->where('status', '!=', 1)->count();
    }

    /**
     * Paginated list of all comments (newest first), for MCP tooling.
     *
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 50, int $page = 1)
    {
        return $this->model::orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find a single comment by id; returns null when not found.
     */
    public function find(int $id): ?Comments
    {
        return $this->model::find($id);
    }

    public function approve(int $id): bool
    {
        $comment = $this->model::find($id);

        if (! $comment) {
            return false;
        }

        return (bool) $comment->update(['status' => '1']);
    }

    public function unApprove(int $id): bool
    {
        $comment = $this->model::find($id);

        if (! $comment) {
            return false;
        }

        return (bool) $comment->update(['status' => '0']);
    }
}
