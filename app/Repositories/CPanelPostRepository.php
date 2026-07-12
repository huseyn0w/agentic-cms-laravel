<?php

/**
 * AgenticCms-Laravel
 * File: CPanelUserRepository.phpCreated by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 09.08.2019
 */

namespace App\Repositories;

use App\Http\Models\Category;
use App\Http\Models\Post;
use App\Http\Models\PostTranslation;
use Doctrine\DBAL\Driver\PDOException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class CPanelPostRepository extends BaseRepository
{
    /** Published status on post_translations (0 = private/draft). */
    private const STATUS_PUBLISHED = 1;

    protected $main_table = 'posts';

    protected $translated_table = 'post_translations';

    protected $translated_table_join_column = 'post_id';

    // `category`/`tags` are validated inputs but not posts/post_translations
    // columns; the PostObserver reads them off the request to sync the
    // category_post / post_tag pivots, so they must never reach
    // Post::create()/update() mass assignment.
    protected $non_persisted_fields = ['category', 'tags'];

    protected $select_fields = [
        'id',
        'author_id',
        'slug',
        'status',
        'created_at',
        'updated_at',
    ];

    public function __construct(Post $model)
    {
        parent::__construct();
        $this->model = $model;
        $this->translated_model = new PostTranslation;
    }

    /**
     * Latest N posts with their translated title, for the admin dashboard.
     * listsTranslations() joins post_translations, so `id` is qualified to
     * avoid an ambiguous-column error.
     */
    public function latestWithTitles($count)
    {
        return $this->model->listsTranslations('title')->orderBy('posts.id', 'desc')->take($count)->get();
    }

    public function trashedPosts($count)
    {
        try {
            $this->locale = get_current_lang();
            $this->select_fields_ready_array = $this->generateSelectFieldsArray($this->select_fields);

            $data = $this->model::join($this->translated_table, $this->main_table.'.id', '=', $this->translated_table.'.'.$this->translated_table_join_column)
                ->select($this->select_fields_ready_array)
                ->where($this->translated_table.'.locale', $this->locale)
                ->with('author')->onlyTrashed()->paginate($count);

        } catch (QueryException|PDOException|\Error $e) {
            Log::error('Fetching trashed posts failed', [
                'exception' => $e->getMessage(),
            ]);

            return throwAbort();
        }

        return $data;
    }

    /**
     * Publish every post translation whose schedule is due and which isn't
     * already published, clearing the schedule. Saves through the model so the
     * model-cache flush + observers fire (consistent with an admin edit).
     * Returns the number of translations published.
     */
    public function publishDue(): int
    {
        $due = PostTranslation::whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->where('status', '!=', self::STATUS_PUBLISHED)
            ->get();

        $count = 0;

        foreach ($due as $translation) {
            $translation->status = self::STATUS_PUBLISHED;
            $translation->scheduled_at = null;

            if ($translation->save()) {
                $count++;
            }
        }

        return $count;
    }

    public function delete($id)
    {

        if (is_array($id)) {
            foreach ($id as $post_id) {
                $result = $this->deletePost($post_id);
            }
        } else {
            $result = $this->deletePost($id);

        }

        if (! $result) {
            throwAbort();
        }

        return $result;

    }

    private function deletePost($id)
    {

        $result = false;
        $post = $this->model::findOrFail($id);
        if ($post->delete()) {
            $result = true;
        }

        return $result;

    }

    public function destroy($id)
    {
        if (is_array($id)) {
            foreach ($id as $post_id) {
                $result = $this->destroyPost($post_id);
            }
        } else {
            $result = $this->destroyPost($id);

        }

        if (! $result) {
            throwAbort();
        }

        return $result;

    }

    public function restore($id)
    {

        if (is_array($id)) {
            foreach ($id as $post_id) {
                $result = $this->restorePost($post_id);
            }
        } else {
            $result = $this->restorePost($id);

        }

        if (! $result) {
            throwAbort();
        }

        return $result;

    }

    public function restorePost($id)
    {

        if ($this->model::withTrashed()->where('id', $id)->restore()) {
            $result = true;
        }

        if (! $result) {
            return throwAbort();
        }

        return $result;
    }

    private function destroyPost($id)
    {
        $deleted_post = false;

        $result = false;
        // onlyTrashed: permanent-delete may ONLY hit an already-trashed post, so
        // the destroy endpoint can never nuke a live post in one step.
        $post = Post::onlyTrashed()->find($id);
        if ($post && $post->forceDelete()) {
            $deleted_post = true;
        }
        if ($deleted_post) {
            $result = true;
        }

        return $result;

    }
}
