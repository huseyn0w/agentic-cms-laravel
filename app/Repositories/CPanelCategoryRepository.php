<?php

/**
 * AgenticCms-Laravel
 * File: PageRepository.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 24.10.2019
 */

namespace App\Repositories;

use App\Http\Models\Category;
use App\Http\Models\CategoryTranslation;
use Illuminate\Support\Collection;

class CPanelCategoryRepository extends BaseRepository
{
    protected $main_table = 'categories';

    protected $translated_table = 'category_translations';

    protected $translated_table_join_column = 'category_id';

    protected $select_fields = [
        'id',
        'author_id',
        'title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'meta_noindex',
        'description',
        'slug',
    ];

    public function __construct(Category $model)
    {
        parent::__construct();
        $this->model = $model;
        $this->translated_model = new CategoryTranslation;
    }

    /**
     * Current-locale category rows (id/title/parent), the raw material for the
     * parent-picker tree. A category that points at a missing parent is treated
     * as a root so it never disappears.
     *
     * @return Collection<int, CategoryTranslation>
     */
    private function localeCategories()
    {
        return CategoryTranslation::where('locale', get_current_lang())
            ->orderBy('title')
            ->get(['category_id', 'title', 'parent_category_id']);
    }

    /**
     * @param  Collection  $rows
     * @return array<int, array<int, CategoryTranslation>> children keyed by parent id (roots under 0)
     */
    private function childrenByParent($rows): array
    {
        $ids = $rows->pluck('category_id')->map(fn ($v) => (int) $v)->all();
        $children = [];

        foreach ($rows as $row) {
            $parent = (int) ($row->parent_category_id ?? 0);
            // Orphan (parent missing in this locale) -> treat as a root.
            if ($parent !== 0 && ! in_array($parent, $ids, true)) {
                $parent = 0;
            }
            $children[$parent][] = $row;
        }

        return $children;
    }

    /**
     * All descendant category ids of $categoryId in the current locale (cycle-safe).
     *
     * @return array<int, int>
     */
    public function descendantIds(int $categoryId): array
    {
        $children = $this->childrenByParent($this->localeCategories());

        $descendants = [];
        $stack = array_map(fn ($r) => (int) $r->category_id, $children[$categoryId] ?? []);

        while ($stack) {
            $id = array_pop($stack);
            if (in_array($id, $descendants, true)) {
                continue; // guard against pre-existing cycles in the data
            }
            $descendants[] = $id;
            foreach ($children[$id] ?? [] as $child) {
                $stack[] = (int) $child->category_id;
            }
        }

        return $descendants;
    }

    /**
     * Tree-ordered parent options for the picker: each entry carries
     * category_id, title and depth (for indentation). When $excludeId is given
     * (the category being edited) it and its whole subtree are omitted so a
     * parent choice can never create a cycle.
     *
     * @return array<int, object{category_id: int, title: string, depth: int}>
     */
    public function parentOptions(?int $excludeId = null): array
    {
        $children = $this->childrenByParent($this->localeCategories());

        $forbidden = $excludeId
            ? array_merge([$excludeId], $this->descendantIds($excludeId))
            : [];

        $options = [];

        $walk = function (int $parentId, int $depth) use (&$walk, &$options, $children, $forbidden) {
            foreach ($children[$parentId] ?? [] as $row) {
                $id = (int) $row->category_id;
                if (in_array($id, $forbidden, true)) {
                    continue;
                }
                $options[] = (object) [
                    'category_id' => $id,
                    'title' => $row->title,
                    'depth' => $depth,
                ];
                $walk($id, $depth + 1);
            }
        };

        $walk(0, 0);

        return $options;
    }
}
