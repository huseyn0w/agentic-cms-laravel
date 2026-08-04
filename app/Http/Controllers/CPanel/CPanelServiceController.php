<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\ServiceListRequest;
use App\Http\Requests\ValidateServiceData;
use App\Services\CPanel\CPanelServiceService;
use Inertia\Inertia;

class CPanelServiceController extends CPanelBaseController
{
    public function __construct(CPanelServiceService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index()
    {
        return $this->renderList($this->service->list($this->per_page), false);
    }

    public function trashedServices()
    {
        return $this->renderList($this->service->trashed($this->per_page), true);
    }

    /**
     * Shape the service paginator into plain rows and render the Inertia list.
     * Presentation-only mapping — the service/repository/observers are untouched.
     */
    private function renderList($services_list, bool $trashed)
    {
        $services_list->getCollection()->transform(fn ($s) => [
            'id' => $s->id,
            'title' => $s->title,
            'sort_order' => (int) $s->sort_order,
            'status' => (int) $s->status,
        ]);

        return Inertia::render('cpanel/services/List', [
            'services_list' => $services_list,
            'trashed' => $trashed,
        ]);
    }

    public function multipleActions(ServiceListRequest $request)
    {
        $action = $request->services_action;

        switch ($action) {
            case 'restore':
                $this->service->runBulkAction($action, $request->services);

                return back()->with('restored', true);
            case 'destroy':
                $this->service->runBulkAction($action, $request->services);

                return back()->with('destroyed', true);
            case 'delete':
                $this->service->runBulkAction($action, $request->services);

                return back()->with('deleted', true);
            default:
                return redirect()->back();
        }
    }

    public function restore($id)
    {
        $this->service->restore($id);

        return back()->with('restored', true);
    }

    public function editService($id)
    {
        $this->result = $this->service->getById($id);

        if (is_null($this->result)) {
            return $this->addService();
        }

        return Inertia::render('cpanel/services/Form', [
            'entity' => $this->serviceEntity($this->result),
            'translation_links' => get_entity_translation_links('services', $id),
        ]);
    }

    /**
     * Shape a service (translatable model) into the flat prop the React form
     * consumes. Field names match ValidateServiceData. Presentation-only.
     */
    private function serviceEntity($s): array
    {
        return [
            'id' => $s->id,
            'title' => $s->title,
            'slug' => $s->slug,
            'icon' => $s->icon ?? '',
            'excerpt' => $s->excerpt ?? '',
            'content' => $s->content ?? '',
            'thumbnail' => $s->thumbnail ?? '',
            'meta_keywords' => $s->meta_keywords ?? '',
            'meta_description' => $s->meta_description ?? '',
            'canonical_url' => $s->canonical_url ?? '',
            'meta_noindex' => (bool) $s->meta_noindex,
            'sort_order' => (int) $s->sort_order,
            'status' => (int) $s->status,
        ];
    }

    public function createService(ValidateServiceData $request)
    {
        parent::create($request);

        return redirect()->route('cpanel_services_list')->with('service_added', ' ');
    }

    public function updateService($id, ValidateServiceData $request)
    {
        return parent::update($id, $request);
    }

    public function addService()
    {
        return Inertia::render('cpanel/services/Form', [
            'entity' => null,
            'translation_links' => request()->route('lang')
                ? get_entity_translation_links('services', request()->id)
                : [],
        ]);
    }
}
