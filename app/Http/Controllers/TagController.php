<?php

namespace App\Http\Controllers;

use App\Services\Front\PublicArchive;
use App\Services\Front\PublicShell;
use App\Services\Front\TagViewService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TagController extends BaseController
{
    public function __construct(
        TagViewService $service,
        private PublicShell $shell,
        private PublicArchive $archive,
    ) {
        parent::__construct();
        $this->service = $service;
    }

    public function index(string $tag_slug, ?string $locale = null, int $page = 1): Response|HttpResponse
    {
        $result = parent::index($tag_slug, $locale);

        if (is_object($result)) {
            return $result;
        }

        $this->data->posts = $this->service->postsFor($this->data->id, $page);

        return Inertia::render('public/Archive', [
            'shell' => $this->shell->build(),
            'archive' => $this->archive->build(
                title: $this->data->name,
                posts: $this->data->posts,
                pageBaseUrl: 'tag/'.$this->data->slug,
                emptyText: __('default/category.not_found'),
            ),
        ])
            ->rootView('app-public')
            ->withViewData(['data' => $this->data]);
    }
}
