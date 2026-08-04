<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\MenuRequest;
use App\Services\CPanel\CPanelMenuService;
use Inertia\Inertia;

class CPanelMenuController extends CPanelBaseController
{
    private $post_fields;

    private $categories_fields;

    private $pages_fields;

    public function __construct(CPanelMenuService $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->post_fields = ['posts.id', 'post_translations.title', 'post_translations.slug'];
        $this->pages_fields = ['pages.id', 'page_translations.title', 'page_translations.slug'];
        $this->categories_fields = ['category_translations.category_id', 'category_translations.title', 'category_translations.slug'];
    }

    public function index()
    {
        $menus_list = $this->service->list($this->per_page);

        $menus_list->getCollection()->transform(fn ($m) => [
            'id' => $m->id,
            'title' => $m->title,
        ]);

        return Inertia::render('cpanel/menus/List', [
            'menus_list' => $menus_list,
        ]);
    }

    public function deleteMenu($id)
    {
        $this->service->delete($id);

        return back()->with('success', __('cpanel/menus.js_delete'));
    }

    public function addMenu()
    {
        return Inertia::render('cpanel/menus/Form', [
            'entity' => null,
            'terms_list' => $this->termsListForInertia(),
            'translation_links' => request()->route('lang')
                ? get_entity_translation_links('menus', request()->id)
                : (object) [],
        ]);
    }

    public function createMenu(MenuRequest $request)
    {
        $this->service->createFromRequest($request);

        return redirect()->route('cpanel_menu_list')->with('success', __('cpanel/menus.menu_added'));
    }

    public function editMenu($id)
    {
        $this->result = $this->service->getById($id);

        if (is_null($this->result)) {
            return $this->addMenu();
        }

        return Inertia::render('cpanel/menus/Form', [
            'entity' => [
                'id' => $this->result->id,
                'title' => $this->result->title,
                'slug' => $this->result->slug,
                'items' => json_decode($this->result->content, true) ?: [],
            ],
            'terms_list' => $this->termsListForInertia(),
            'translation_links' => get_entity_translation_links('menus', $id),
        ]);
    }

    private function getTermsListForMenu()
    {
        return [
            'posts' => get_post_list($this->post_fields),
            'pages' => get_pages_list($this->pages_fields),
            'categories' => get_post_categories_list($this->categories_fields),
        ];
    }

    /**
     * Shape the post/page/category term sources into {title, slug} option lists
     * the React menu builder consumes.
     */
    private function termsListForInertia(): array
    {
        $terms = $this->getTermsListForMenu();

        $shape = fn ($collection) => collect($collection)
            ->map(fn ($t) => ['title' => $t->title, 'slug' => $t->slug])
            ->values()
            ->all();

        return [
            'posts' => $shape($terms['posts']),
            'pages' => $shape($terms['pages']),
            'categories' => $shape($terms['categories']),
        ];
    }

    public function updateMenu($id, MenuRequest $request)
    {
        parent::update($id, $request);

        return back()->with('success', __('cpanel/menus.menu_updated'));
    }
}
