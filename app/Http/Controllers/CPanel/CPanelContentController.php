<?php

namespace App\Http\Controllers\CPanel;

use App\Services\CPanel\ContentService;
use App\Support\Content\ContentType;
use App\Support\Content\ContentTypeRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Generic admin CRUD for any plugin-declared content type. One controller serves
 * every registered type by slug (resolved from the registry); the schema drives
 * the React list + form and the validation. Gated by manage_content on the route
 * group (per-type permission also enforced here).
 */
class CPanelContentController extends CPanelBaseController
{
    public function __construct(
        private ContentTypeRegistry $registry,
        private ContentService $content,
    ) {
        parent::__construct();
    }

    private function resolve(string $slug): ContentType
    {
        $type = $this->registry->get($slug);

        abort_if($type === null, 404);

        // A type may declare a stricter permission than the group's manage_content.
        abort_if(
            auth()->user()?->cannot($type->permission, 'App\Http\Models\UserRoles') ?? true,
            403,
        );

        return $type;
    }

    public function index(string $type, Request $request)
    {
        $contentType = $this->resolve($type);

        $search = $request->query('search');
        $search = is_string($search) ? $search : null;

        $records = $this->content->list($contentType, $search, $this->per_page);

        $columns = $contentType->listColumns();
        $records->getCollection()->transform(function ($r) use ($columns) {
            $row = ['id' => $r->id];
            foreach ($columns as $col) {
                $row[$col] = $r->{$col};
            }

            return $row;
        });

        return Inertia::render('cpanel/content/Index', [
            'type' => $contentType->toArray(),
            'records' => $records,
            'filters' => ['search' => $search],
        ]);
    }

    public function createForm(string $type)
    {
        $contentType = $this->resolve($type);

        return Inertia::render('cpanel/content/Form', [
            'type' => $contentType->toArray(),
            'record' => null,
        ]);
    }

    public function store(string $type, Request $request)
    {
        $contentType = $this->resolve($type);

        $data = $request->validate($this->content->rules($contentType));
        $this->content->create($contentType, $data + $request->all());

        return redirect()
            ->route('cpanel_content_index', $contentType->slug)
            ->with('success', __('cpanel/content.saved'));
    }

    public function editForm(string $type, int $id)
    {
        $contentType = $this->resolve($type);
        $record = $this->content->find($contentType, $id);

        abort_if($record === null, 404);

        return Inertia::render('cpanel/content/Form', [
            'type' => $contentType->toArray(),
            'record' => $record->toArray(),
        ]);
    }

    public function updateItem(string $type, int $id, Request $request)
    {
        $contentType = $this->resolve($type);
        $record = $this->content->find($contentType, $id);

        abort_if($record === null, 404);

        $data = $request->validate($this->content->rules($contentType));
        $this->content->update($contentType, $record, $data + $request->all());

        return redirect()
            ->route('cpanel_content_index', $contentType->slug)
            ->with('success', __('cpanel/content.saved'));
    }

    public function destroy(string $type, int $id)
    {
        $contentType = $this->resolve($type);
        $this->content->delete($contentType, $id);

        return redirect()
            ->route('cpanel_content_index', $contentType->slug)
            ->with('success', __('cpanel/content.deleted'));
    }
}
