<?php

/**
 * AgenticCms-Laravel
 * File: PostCommentsRepository.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 24.10.2019
 */

namespace App\Repositories;

use App\Http\Models\Comments;
use Doctrine\DBAL\Driver\PDOException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PostCommentsRepository extends BaseRepository
{
    public function __construct(Comments $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    /**
     * Persist a new comment for the authenticated user. The owner and the
     * approval status are derived server-side and never taken from request
     * input, so only an explicit, whitelisted payload reaches the model.
     */
    public function create($request)
    {
        if (! is_logged_in()) {
            return false;
        }

        $user = Auth::user();

        $data = [
            'post_id' => (int) $request->input('post_id'),
            'parent_id' => $request->input('parent_id'),
            'comment' => $request->input('comment'),
            'user_id' => $user->id,
            'status' => $this->isAdmin($user) ? 1 : 0,
        ];

        return parent::create($data);
    }

    public function delete($request)
    {
        if (! is_logged_in()) {
            return false;
        }

        $user = Auth::user();

        $comment_id = (int) $request['commentId'];

        $comment = $this->model::find($comment_id);
        if (! $comment) {
            return false;
        }

        // Authorize by the comment's REAL owner (or an admin) — never a
        // client-supplied username, which an attacker could set to their own
        // to delete a comment they do not own.
        if (! $this->isAdmin($user) && (int) $comment->user_id !== (int) $user->id) {
            return false;
        }

        $comment_deleted = parent::delete($comment_id);

        return $comment_deleted ? 'Comment has been deleted' : 'Some problem occured';
    }

    /**
     * Update a comment. An administrator may edit any comment; a regular user
     * may edit only comments they own. Everybody else is rejected.
     */
    public function update($request, $id = null)
    {
        if (! is_logged_in()) {
            throwAbort();
        }

        $user = Auth::user();
        $comment_id = (int) $request->input('updated_comment_id');

        try {
            $comment = $this->model::findOrFail($comment_id);

            if (! $this->isAdmin($user) && (int) $comment->user_id !== (int) $user->id) {
                return throwAbort();
            }

            return (bool) $comment->update([
                'comment' => $request->input('comment'),
            ]);
        } catch (QueryException|PDOException|\Error $e) {
            Log::error('Comment update failed', [
                'comment_id' => $comment_id,
                'exception' => $e->getMessage(),
            ]);

            return throwAbort();
        }
    }

    private function isAdmin($user): bool
    {
        return (int) optional(optional($user)->role)->id === 1;
    }
}
