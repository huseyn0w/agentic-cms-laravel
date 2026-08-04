<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\PageListRequest;
use App\Http\Requests\ValidatePageData;
use App\Services\CPanel\CPanelPageService;
use Carbon\Carbon;
use Inertia\Inertia;

class CPanelPageController extends CPanelBaseController
{
    private $users_list;

    private $page_templates;

    public function __construct(CPanelPageService $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->users_list = get_authors_list();
        $this->page_templates = get_page_templates_list();
    }

    public function index()
    {
        return $this->renderList($this->service->list($this->per_page), false);
    }

    public function trashedPages()
    {
        return $this->renderList($this->service->trashed($this->per_page), true);
    }

    /**
     * Shape the page paginator into plain rows and render the Inertia list.
     * Presentation-only mapping — the service/repository/observers are untouched.
     */
    private function renderList($pages_list, bool $trashed)
    {
        $pages_list->getCollection()->transform(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'author' => $p->author?->username,
            'created_at' => Carbon::parse($p->created_at)->format('d.m.Y'),
            'status' => (int) $p->status,
        ]);

        return Inertia::render('cpanel/pages/List', [
            'pages_list' => $pages_list,
            'trashed' => $trashed,
        ]);
    }

    public function multipleDelete(PageListRequest $request)
    {
        $this->service->delete($request->pages);

        return back()->with('deleted', true);
    }

    public function multipleActions(PageListRequest $request)
    {
        $action = $request->pages_action;

        switch ($action) {
            case 'restore':
                $this->service->runBulkAction($action, $request->pages);

                return back()->with('restored', true);
            case 'destroy':
                $this->service->runBulkAction($action, $request->pages);

                return back()->with('destroyed', true);
            default:
                return redirect()->back();
        }
    }

    public function restore($id)
    {
        $this->service->restore($id);

        return back()->with('restored', true);
    }

    public function editPage($id)
    {
        $this->result = $this->service->getById($id);

        if (is_null($this->result)) {
            return $this->addPage();
        }

        return view('cpanel.pages.edit_page',
            [
                'entity' => $this->result,
                'users_list' => $this->users_list,
                'page_templates' => $this->page_templates,
                'categories_list' => get_post_categories_list(['category_id', 'title']),
                'translation_links' => get_entity_translation_links('pages', $id),
            ]
        );
    }

    public function createPage(ValidatePageData $request)
    {
        parent::create($request);

        return redirect()->route('cpanel_pages_list')->with('page_added', ' ');

    }

    public function updatePage($id, ValidatePageData $request)
    {
        return parent::update($id, $request);
    }

    public function revisions($id, $lang)
    {
        $data = $this->service->revisionsFor((int) $id, $lang);

        return view('cpanel.revisions.list', [
            'entity_id' => (int) $id,
            'lang' => $lang,
            'current' => $data['current'],
            'revisions' => $data['revisions'],
            'edit_route' => 'cpanel_edit_page',
            'diff_route' => 'cpanel_page_revision_diff',
            'restore_route' => 'cpanel_restore_page_revision',
        ]);
    }

    public function revisionDiff($id, $revision, $lang)
    {
        $data = $this->service->revisionDiff((int) $id, $lang, (int) $revision);

        if (is_null($data)) {
            abort(404);
        }

        return view('cpanel.revisions.diff', [
            'entity_id' => (int) $id,
            'lang' => $lang,
            'current' => $data['current'],
            'revision' => $data['revision'],
            'fields' => $data['fields'],
            'list_route' => 'cpanel_page_revisions',
            'restore_route' => 'cpanel_restore_page_revision',
        ]);
    }

    public function restoreRevision($id, $revision, $lang)
    {
        $restored = $this->service->restoreRevision((int) $id, $lang, (int) $revision);

        if (! $restored) {
            abort(404);
        }

        return redirect()
            ->route('cpanel_edit_page', ['id' => $id, 'lang' => $lang])
            ->with('revision_restored', true);
    }

    public function addPage()
    {
        $array = [
            'users_list' => $this->users_list,
            'page_templates' => $this->page_templates,
            'categories_list' => get_post_categories_list(['category_id', 'title']),
        ];

        if (request()->route('lang')) {
            $array['translation_links'] = get_entity_translation_links('pages', request()->id);
        }

        return view('cpanel.pages.new_page', $array);
    }
}
