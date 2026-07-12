<?php

/**
 * AgenticCms-Laravel
 * File: CPanelPageRepository.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 09.08.2019
 */

namespace App\Repositories;

use App\Http\Models\Page;
use App\Http\Models\PageTranslation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class CPanelPageRepository extends BaseRepository
{
    protected $main_table = 'pages';

    protected $translated_table = 'page_translations';

    protected $translated_table_join_column = 'page_id';

    protected $select_fields = [
        'id',
        'author_id',
        'title',
        'slug',
        'canonical_url',
        'meta_noindex',
        'status',
        'created_at',
        'updated_at',
    ];

    public function __construct(Page $model)
    {
        parent::__construct();
        $this->model = $model;
        $this->translated_model = new PageTranslation;
    }

    /**
     * Paginated list of soft-deleted pages (current locale) for the trash tab,
     * mirroring CPanelPostRepository::trashedPosts.
     */
    public function trashedPages($count)
    {
        try {
            $this->locale = get_current_lang();
            $this->select_fields_ready_array = $this->generateSelectFieldsArray($this->select_fields);

            $data = $this->model::join($this->translated_table, $this->main_table.'.id', '=', $this->translated_table.'.'.$this->translated_table_join_column)
                ->select($this->select_fields_ready_array)
                ->where($this->translated_table.'.locale', $this->locale)
                ->with('author')->onlyTrashed()->paginate($count);

        } catch (QueryException|\Error $e) {
            Log::error('Fetching trashed pages failed', [
                'exception' => $e->getMessage(),
            ]);

            return throwAbort();
        }

        return $data;
    }

    public function delete($id)
    {
        $result = false;

        if (is_array($id)) {
            foreach ($id as $page_id) {
                $result = $this->deletePage($page_id);
            }
        } else {
            $result = $this->deletePage($id);
        }

        if (! $result) {
            throwAbort();
        }

        return $result;
    }

    private function deletePage($id)
    {
        $result = false;
        $page = $this->model::findOrFail($id);
        if ($page->delete()) {
            $result = true;
        }

        return $result;
    }

    public function destroy($id)
    {
        $result = false;

        if (is_array($id)) {
            foreach ($id as $page_id) {
                $result = $this->destroyPage($page_id);
            }
        } else {
            $result = $this->destroyPage($id);
        }

        if (! $result) {
            throwAbort();
        }

        return $result;
    }

    private function destroyPage($id)
    {
        $result = false;
        // onlyTrashed: permanent-delete may ONLY hit an already-trashed page, so
        // the destroy endpoint can never nuke a live page in one step.
        $page = Page::onlyTrashed()->find($id);
        // forceDelete cascades to page_translations via the FK onDelete(cascade).
        if ($page && $page->forceDelete()) {
            $result = true;
        }

        return $result;
    }

    public function restore($id)
    {
        $result = false;

        if (is_array($id)) {
            foreach ($id as $page_id) {
                $result = $this->restorePage($page_id);
            }
        } else {
            $result = $this->restorePage($id);
        }

        if (! $result) {
            throwAbort();
        }

        return $result;
    }

    private function restorePage($id)
    {
        $result = false;
        if ($this->model::withTrashed()->where('id', $id)->restore()) {
            $result = true;
        }

        return $result;
    }
}
