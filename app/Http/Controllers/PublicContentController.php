<?php

namespace App\Http\Controllers;

use App\Services\Front\PublicContentService;
use App\Support\Content\ContentType;
use App\Support\Content\Field;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Public front-end for plugin content types, rendered generically from the
 * type's schema. One controller serves every public type by slug (bound via the
 * route default), so a new public content type ships with no bespoke controller.
 * SEO head stays server-rendered by Blade (app-public + seo-meta), like the rest
 * of the public site.
 */
class PublicContentController extends Controller
{
    public function __construct(private PublicContentService $content) {}

    /** Index grid for a public content type (e.g. /projects). */
    public function index(Request $request, ?string $locale = null): Response|HttpResponse
    {
        $ct = $this->content->publicType($this->routeType($request));
        if ($ct === null) {
            abort(404);
        }

        $label = $ct->label();

        return Inertia::render('public/ContentIndex', [
            'heading' => $label,
            'slug' => $ct->slug,
            'fields' => array_map(fn (Field $f) => $f->toArray(), $ct->fields),
            'items' => $this->content->listItems($ct),
            'hasDetail' => $ct->hasDetail(),
            'detailBase' => url('/'.$ct->slug),
            'emptyText' => __('content.empty'),
        ])
            ->rootView('app-public')
            ->withViewData(['data' => $this->seoEntity($label, url('/'.$ct->slug))]);
    }

    /** Detail page for one row (e.g. /projects/12). */
    public function show(Request $request, string $id, ?string $locale = null): Response|HttpResponse
    {
        $ct = $this->content->publicType($this->routeType($request));
        if ($ct === null || ! $ct->hasDetail()) {
            abort(404);
        }

        $item = $this->content->findItem($ct, (int) $id);
        if ($item === null) {
            abort(404);
        }

        $title = $this->itemTitle($ct, $item);

        return Inertia::render('public/ContentDetail', [
            'slug' => $ct->slug,
            'title' => $title,
            'fields' => array_map(fn (Field $f) => $f->toArray(), $ct->fields),
            'item' => $item,
            'indexUrl' => url('/'.$ct->slug),
            'indexLabel' => $ct->label(),
        ])
            ->rootView('app-public')
            ->withViewData(['data' => $this->seoEntity($title, url('/'.$ct->slug.'/'.$id))]);
    }

    /**
     * The content-type slug carried on the matched route as a default (routes are
     * literal per-slug URIs, so the type is not a URL segment).
     */
    private function routeType(Request $request): string
    {
        return (string) ($request->route()?->defaults['type'] ?? '');
    }

    /**
     * Best title for SEO/breadcrumb: the first non-empty TEXT field, else the
     * type label.
     *
     * @param  array<string, mixed>  $item
     */
    private function itemTitle(ContentType $ct, array $item): string
    {
        foreach ($ct->fields as $field) {
            if ($field->type === Field::TEXT && ! empty($item[$field->name])) {
                return (string) $item[$field->name];
            }
        }

        return $ct->label();
    }

    /** Synthetic entity for the seo-meta partial (title + canonical). */
    private function seoEntity(string $title, string $canonical): object
    {
        return (object) [
            'title' => $title,
            'meta_description' => null,
            'meta_keywords' => null,
            'canonical_url' => $canonical,
            'meta_noindex' => false,
            'thumbnail' => null,
        ];
    }
}
