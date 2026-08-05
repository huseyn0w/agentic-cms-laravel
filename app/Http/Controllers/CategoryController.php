<?php

namespace App\Http\Controllers;

use App\Services\Front\CategoryViewService;
use App\Services\Front\PublicArchive;
use App\Services\Front\PublicShell;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CategoryController extends BaseController
{
    public function __construct(
        CategoryViewService $service,
        private PublicShell $shell,
        private PublicArchive $archive,
    ) {
        parent::__construct();
        $this->service = $service;
    }

    public function index(string $category_slug, ?string $locale = null, int $page = 1): Response|HttpResponse
    {
        $result = parent::index($category_slug, $locale);

        if (is_object($result)) {
            return $result;
        }

        $this->data->posts = $this->service->postsFor($this->data->id, $page);

        return Inertia::render('public/Archive', [
            'shell' => $this->shell->build(),
            'archive' => $this->archive->build(
                title: $this->data->title,
                posts: $this->data->posts,
                pageBaseUrl: 'category/'.$this->data->slug,
                emptyText: __('default/category.not_found'),
            ),
        ])
            ->rootView('app-public')
            ->withViewData(['data' => $this->data]);
    }
}
