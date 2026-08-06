<?php

namespace App\Http\Controllers;

use App\Services\Front\ServiceViewService;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ServiceController extends BaseController
{
    public function __construct(ServiceViewService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Public services grid (published, ordered by sort_order). Honours an
     * optional locale prefix by switching the session locale (mirrors the
     * BaseController slug-locale handling).
     */
    public function listing(?string $locale = null): Response|HttpResponse
    {
        if (! is_null($locale) && in_array($locale, get_lang_prefixes()) && $locale !== get_current_lang()) {
            return $this->setLang($locale);
        }

        $services = $this->service->publishedOrdered();

        // Synthetic entity for the seo-meta partial (title/description/canonical
        // for the listing page); it has no slug so it is not treated as home.
        $seoEntity = (object) [
            'title' => __('services.meta_title'),
            'meta_description' => __('services.meta_description'),
            'meta_keywords' => null,
            'canonical_url' => route('services_index'),
            'meta_noindex' => false,
            'thumbnail' => null,
        ];

        return Inertia::render('public/ServiceIndex', [
            'heading' => __('services.index_heading'),
            'emptyText' => __('services.empty'),
            'services' => $services->values()->map(fn ($s) => [
                'title' => $s->title,
                'url' => route('services_show', ['slug' => $s->slug]),
                'icon' => $s->icon ?: null,
                'excerpt' => $s->excerpt ?: null,
            ])->all(),
        ])
            ->rootView('app-public')
            ->withViewData([
                'data' => $seoEntity,
                'jsonLd' => $services->isNotEmpty() ? [$this->serviceItemList($services)] : [],
            ]);
    }

    /**
     * Single published service by slug. Draft/private services resolve to null
     * (front read scope) and 404, identical to the post/page detail paths.
     */
    public function show(string $slug, ?string $locale = null): Response|HttpResponse
    {
        $result = parent::index($slug, $locale);

        if (is_object($result)) {
            return $result;
        }

        $service = $this->data;

        return Inertia::render('public/ServiceShow', [
            'indexUrl' => route('services_index'),
            'service' => [
                'title' => $service->title,
                'excerpt' => $service->excerpt ?: null,
                'content' => app('hooks')->filter('the_content', $service->content),
                'thumbnail' => $service->thumbnail ?: null,
            ],
            'crumbs' => [
                ['label' => config('app.name'), 'url' => rtrim(config('app.url'), '/')],
                ['label' => __('services.index_heading'), 'url' => route('services_index')],
                ['label' => $service->title, 'url' => null],
            ],
        ])
            ->rootView('app-public')
            ->withViewData(['data' => $service]);
    }

    /**
     * schema.org ItemList of Service nodes from the published records, rendered
     * server-side by the app-public root (M1: services are first-class
     * structured data). Mirrors the old services/index.blade.php block.
     *
     * @param  Collection<int, object>  $services
     * @return array<string, mixed>
     */
    private function serviceItemList($services): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $services->values()->map(fn ($s, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => array_filter([
                    '@type' => 'Service',
                    'name' => $s->title,
                    'description' => $s->excerpt,
                    'url' => route('services_show', ['slug' => $s->slug]),
                ]),
            ])->all(),
        ];
    }
}
