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
        $array = [
            'terms_list' => $this->getTermsListForMenu(),
        ];

        if (request()->route('lang')) {
            $array['translation_links'] = get_entity_translation_links('menus', request()->id);
        }

        return view('cpanel.menus.new_menu', $array);
    }

    public function createMenu(MenuRequest $request)
    {
        $this->service->createFromRequest($request);

        return redirect()->route('cpanel_menu_list')->with('menu_added', ' ');
    }

    public function editMenu($id)
    {
        $this->result = $this->service->getById($id);

        if (is_null($this->result)) {
            return $this->addMenu();
        }

        return view('cpanel.menus.edit_menu', [
            'entity' => $this->result,
            'terms_list' => $this->getTermsListForMenu(),
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

    public function updateMenu($id, MenuRequest $request)
    {
        return parent::update($id, $request);
    }
}
