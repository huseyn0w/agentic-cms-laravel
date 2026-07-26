<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\CategoryListRequest;
use App\Http\Requests\CategoryRequest;
use App\Services\CPanel\CPanelCategoryService;
use Inertia\Inertia;

class CPanelCategoryController extends CPanelBaseController
{
    public function __construct(CPanelCategoryService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function createCategory(CategoryRequest $request)
    {
        $this->service->create($request);

        return redirect()->route('cpanel_category_list')
            ->with('category_added', ' ')
            ->with('success', __('cpanel/categories.created'));

    }

    public function edit($id)
    {
        $this->result = $this->service->getById($id);

        if (is_null($this->result)) {
            return $this->addCategory();
        }

        return Inertia::render('cpanel/categories/Form', [
            'entity' => [
                'id' => $this->result->id,
                'title' => $this->result->title,
                'slug' => $this->result->slug,
                'description' => $this->result->description,
                'parent_category_id' => $this->result->parent_category_id,
                'meta_description' => $this->result->meta_description ?? null,
                'meta_keywords' => $this->result->meta_keywords ?? null,
            ],
            'parent_options' => $this->service->parentOptions((int) $id),
            'translation_links' => get_entity_translation_links('categories', $id),
        ]);
    }

    public function index()
    {
        $categories_list = $this->service->list($this->per_page);

        // The paginator rows are Category models selected with only
        // id/author_id/title/meta_description/meta_keywords/canonical_url/
        // meta_noindex/description/slug (see CPanelCategoryRepository::$select_fields);
        // there is no parent_title on the row and no parent relation on the
        // Category model, but parent_category_id IS reachable via the
        // Translatable trait's accessor. Resolve each row's parent NAME via
        // the same tree-wide parentOptions() lookup already used by
        // addCategory()/edit() in this controller (id -> title), which keeps
        // this a Controller -> Service call and avoids touching the
        // repository/service layer for this presentation-only mapping.
        $parentNames = collect($this->service->parentOptions())->keyBy('category_id');

        $categories_list->getCollection()->transform(fn ($c) => [
            'id' => $c->id,
            'title' => $c->title,
            'slug' => $c->slug,
            'parent_title' => $c->parent_category_id !== null
                ? $parentNames->get($c->parent_category_id)?->title
                : null,
        ]);

        return Inertia::render('cpanel/categories/List', [
            'categories_list' => $categories_list,
        ]);
    }

    public function addCategory()
    {
        $props = [
            'entity' => null,
            'parent_options' => $this->service->parentOptions(),
            'translation_links' => request()->route('lang')
                ? get_entity_translation_links('categories', request()->id)
                : [],
        ];

        return Inertia::render('cpanel/categories/Form', $props);
    }

    public function multipleDelete(CategoryListRequest $request)
    {
        $result = $this->service->delete($request->categories);

        return back()
            ->with('message', $result)
            ->with('success', __('cpanel/categories.deleted'));
    }

    public function updateCategory($id, CategoryRequest $request)
    {
        return parent::update($id, $request)->with('success', __('cpanel/categories.updated'));
    }
}
