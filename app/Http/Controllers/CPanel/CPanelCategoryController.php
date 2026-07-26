<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\CategoryListRequest;
use App\Http\Requests\CategoryRequest;
use App\Services\CPanel\CPanelCategoryService;

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

        return redirect()->route('cpanel_category_list')->with('category_added', ' ');

    }

    public function edit($id)
    {
        $this->result = $this->service->getById($id);

        if (is_null($this->result)) {
            return $this->addCategory();
        }

        return view('cpanel.post_categories.edit_category',
            [
                'entity' => $this->result,
                'parent_options' => $this->service->parentOptions((int) $id),
                'translation_links' => get_entity_translation_links('categories', $id),
            ]
        );
    }

    public function index()
    {
        $categories_list = $this->service->list($this->per_page);

        // The paginator rows are Category models selected with only
        // id/author_id/title/meta_description/meta_keywords/canonical_url/
        // meta_noindex/description/slug (see CPanelCategoryRepository::$select_fields);
        // there is no parent_title on the row and no parent relation on the
        // Category model. parent_category_id IS reachable via the Translatable
        // trait's accessor (confirmed empirically: all seeded rows are roots,
        // parent_category_id resolves to null; isset($row->parent_title) is
        // false). Resolving the parent's title would require an extra
        // lookup outside the repository, so — per the presentation-only
        // mapping allowance — we surface parent_category_id itself and let
        // the frontend render a dash when it's null.
        $categories_list->getCollection()->transform(fn ($c) => [
            'id' => $c->id,
            'title' => $c->title,
            'slug' => $c->slug,
            'parent_title' => $c->parent_category_id !== null ? (string) $c->parent_category_id : null,
        ]);

        return \Inertia\Inertia::render('cpanel/categories/List', [
            'categories_list' => $categories_list,
        ]);
    }

    public function addCategory()
    {
        $array = [
            'parent_options' => $this->service->parentOptions(),
        ];

        if (request()->route('lang')) {
            $array['translation_links'] = get_entity_translation_links('categories', request()->id);
        }

        return view('cpanel.post_categories.new_category', $array);
    }

    public function multipleDelete(CategoryListRequest $request)
    {
        $result = $this->service->delete($request->categories);

        return back()->with('message', $result);
    }

    public function updateCategory($id, CategoryRequest $request)
    {
        return parent::update($id, $request);
    }
}
