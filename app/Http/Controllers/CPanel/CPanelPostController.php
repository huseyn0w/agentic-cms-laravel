<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\PostListRequest;
use App\Http\Requests\ValidatePostData;
use App\Services\CPanel\CPanelPostService;
use Carbon\Carbon;
use Inertia\Inertia;

class CPanelPostController extends CPanelBaseController
{
    private $users_list;

    public function __construct(CPanelPostService $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->users_list = get_authors_list();
    }

    public function index()
    {
        return $this->renderList($this->service->list($this->per_page), false);
    }

    public function trashedPosts()
    {
        return $this->renderList($this->service->trashed($this->per_page), true);
    }

    /**
     * Shape the post paginator into plain rows and render the Inertia list.
     * Presentation-only mapping — the service/repository/observers are untouched.
     */
    private function renderList($posts_list, bool $trashed)
    {
        $posts_list->getCollection()->transform(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'author' => $p->author?->username,
            'created_at' => Carbon::parse($p->created_at)->format('d.m.Y'),
            'status' => (int) $p->status,
        ]);

        return Inertia::render('cpanel/posts/List', [
            'posts_list' => $posts_list,
            'trashed' => $trashed,
        ]);
    }

    public function multipleDelete(PostListRequest $request)
    {
        $this->service->delete($request->posts);

        return back()->with('deleted', true);
    }

    public function multipleDestroy(PostListRequest $request)
    {
        $this->service->destroy($request->posts);

        return back()->with('destroyed', true);
    }

    public function multipleRestore(PostListRequest $request)
    {

        $this->service->restore($request->posts);

        return back()->with('restored', true);
    }

    public function multipleActions(PostListRequest $request)
    {
        $action = $request->posts_action;

        switch ($action) {
            case 'restore':
                $this->service->runBulkAction($action, $request->posts);

                return back()->with('restored', true);
            case 'destroy':
                $this->service->runBulkAction($action, $request->posts);

                return back()->with('destroyed', true);
            default:
                return redirect()->back();
        }
    }

    public function editPost($id)
    {
        $this->result = $this->service->getById($id);

        if (is_null($this->result)) {
            return $this->addPost();
        }

        return Inertia::render('cpanel/posts/Form', [
            'entity' => $this->postEntity($this->result),
            'categories_list' => $this->postCategoryOptions(),
            'authors' => $this->authorOptions(),
            'translation_links' => get_entity_translation_links('posts', $id),
        ]);
    }

    /**
     * Shape a post (translatable model) into the flat prop the React form
     * consumes. Field names match ValidatePostData. Presentation-only.
     */
    private function postEntity($p): array
    {
        return [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'content' => $p->content ?? '',
            'preview' => $p->preview ?? '',
            'author_id' => $p->author_id,
            'meta_keywords' => $p->meta_keywords ?? '',
            'meta_description' => $p->meta_description ?? '',
            'canonical_url' => $p->canonical_url ?? '',
            'meta_noindex' => (bool) $p->meta_noindex,
            'status' => (int) $p->status,
            'thumbnail' => $p->thumbnail ?? '',
            'updated_at' => optional($p->updated_at)->format('Y-m-d H:i:s') ?? '',
            'scheduled_at' => optional($p->scheduled_at)->format('Y-m-d\TH:i') ?? '',
            'category' => $p->categories->pluck('id')->all(),
            'tags' => $p->tags->pluck('name')->implode(', '),
        ];
    }

    private function postCategoryOptions(): array
    {
        return collect(get_post_categories_list())
            ->map(fn ($c) => ['category_id' => $c->category_id, 'title' => $c->title])
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

    public function createPost(ValidatePostData $request)
    {
        $this->service->create($request);

        return redirect()->route('cpanel_posts_list')
            ->with('post_added', true)
            ->with('success', __('cpanel/posts.created'));
    }

    public function updatePost($id, ValidatePostData $request)
    {
        return parent::update($id, $request)->with('success', __('cpanel/posts.updated'));
    }

    public function revisions($id, $lang)
    {
        // A trashed post is not editable (getById excludes soft-deleted), so its
        // revision history must be unreachable too — stay consistent with editPost.
        if (is_null($this->service->getById((int) $id))) {
            abort(404);
        }

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

        return Inertia::render('cpanel/posts/Revisions', [
            'entity_id' => (int) $id,
            'lang' => $lang,
            'revisions' => $revisions,
        ]);
    }

    public function revisionDiff($id, $revision, $lang)
    {
        if (is_null($this->service->getById((int) $id))) {
            abort(404);
        }

        $data = $this->service->revisionDiff((int) $id, $lang, (int) $revision);

        if (is_null($data)) {
            abort(404);
        }

        return Inertia::render('cpanel/posts/RevisionDiff', [
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
        if (is_null($this->service->getById((int) $id))) {
            abort(404);
        }

        $restored = $this->service->restoreRevision((int) $id, $lang, (int) $revision);

        if (! $restored) {
            abort(404);
        }

        return redirect()
            ->route('cpanel_edit_post', ['id' => $id, 'lang' => $lang])
            ->with('revision_restored', true)
            ->with('success', __('cpanel/revisions.restored_success'));
    }

    public function addPost()
    {
        return Inertia::render('cpanel/posts/Form', [
            'entity' => null,
            'categories_list' => $this->postCategoryOptions(),
            'authors' => $this->authorOptions(),
            'translation_links' => request()->route('lang')
                ? get_entity_translation_links('posts', request()->id)
                : [],
        ]);
    }
}
