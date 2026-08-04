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

        return Inertia::render('cpanel/pages/Form', [
            'entity' => $this->pageEntity($this->result),
            'templates' => $this->templateOptions(),
            'authors' => $this->authorOptions(),
            'categories_list' => $this->categoryOptions(),
            'translation_links' => get_entity_translation_links('pages', $id),
        ]);
    }

    /**
     * Shape a page (translatable model) into the flat prop the React form
     * consumes. Field names match ValidatePageData. custom_fields is decoded
     * back into the associative structure the builder + theme share.
     */
    private function pageEntity($p): array
    {
        return [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'content' => $p->content ?? '',
            'author_id' => $p->author_id,
            'meta_keywords' => $p->meta_keywords ?? '',
            'meta_description' => $p->meta_description ?? '',
            'canonical_url' => $p->canonical_url ?? '',
            'meta_noindex' => (bool) $p->meta_noindex,
            'status' => (int) $p->status,
            'template' => $p->template ?? '',
            'updated_at' => optional($p->updated_at)->format('Y-m-d H:i:s') ?? '',
            'custom_fields' => $this->decodeCustomFields($p->custom_fields),
        ];
    }

    /** @return array<string, mixed> */
    private function decodeCustomFields($raw): array
    {
        if (empty($raw)) {
            return [];
        }

        $decoded = is_array($raw) ? $raw : json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function templateOptions(): array
    {
        $templates = $this->page_templates ?: [];

        return collect($templates)
            ->map(fn ($label, $file) => ['value' => $file, 'label' => $label])
            ->values()
            ->all();
    }

    private function authorOptions(): array
    {
        return collect($this->users_list)
            ->map(fn ($u) => ['id' => $u->id, 'username' => $u->username])
            ->values()
            ->all();
    }

    private function categoryOptions(): array
    {
        return collect(get_post_categories_list(['category_id', 'title']))
            ->map(fn ($c) => ['category_id' => $c->category_id, 'title' => $c->title])
            ->values()
            ->all();
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
        $revisions = $data['revisions'];

        // Version labels count down from the newest across the whole history,
        // so they stay stable per revision regardless of the current page.
        $offset = $revisions->total() - ($revisions->firstItem() - 1);
        $revisions->getCollection()->transform(fn ($r, $i) => [
            'id' => $r->id,
            'version' => $offset - $i,
            'author' => $r->author?->username,
            'created_at' => Carbon::parse($r->created_at)->format('d.m.Y H:i'),
        ]);

        return Inertia::render('cpanel/pages/Revisions', [
            'entity_id' => (int) $id,
            'lang' => $lang,
            'revisions' => $revisions,
        ]);
    }

    public function revisionDiff($id, $revision, $lang)
    {
        $data = $this->service->revisionDiff((int) $id, $lang, (int) $revision);

        if (is_null($data)) {
            abort(404);
        }

        return Inertia::render('cpanel/pages/RevisionDiff', [
            'entity_id' => (int) $id,
            'lang' => $lang,
            'revision' => [
                'id' => $data['revision']->id,
                'created_at' => Carbon::parse($data['revision']->created_at)->format('d.m.Y H:i'),
            ],
            'fields' => $data['fields'],
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
        return Inertia::render('cpanel/pages/Form', [
            'entity' => null,
            'templates' => $this->templateOptions(),
            'authors' => $this->authorOptions(),
            'categories_list' => $this->categoryOptions(),
            'translation_links' => request()->route('lang')
                ? get_entity_translation_links('pages', request()->id)
                : [],
        ]);
    }
}
