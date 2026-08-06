<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMail as ContactRequest;
use App\Http\Requests\SearchRequest;
use App\Services\Front\ContactService;
use App\Services\Front\PageViewService;
use App\Services\Front\PublicShell;
use App\Services\Front\SearchService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends BaseController
{
    public function __construct(
        PageViewService $service,
        private SearchService $searchService,
        private ContactService $contactService,
        private PublicShell $shell,
    ) {
        parent::__construct();
        $this->service = $service;
    }

    public function index($slug = '/', ?string $locale = null)
    {
        $result = parent::index($slug, $locale);

        if (is_object($result)) {
            return $result;
        }

        $customFields = ! empty($this->data->custom_fields)
            ? (json_decode($this->data->custom_fields, true) ?: [])
            : [];

        // All page templates are on Inertia (Phase 4).
        return match ($this->data->template) {
            'home' => $this->renderHome($customFields),
            'contacts' => $this->renderContact(),
            default => $this->renderPage(),
        };
    }

    /** A standard content page (title + prose body). */
    private function renderPage(): Response
    {
        return Inertia::render('public/Page', [
            'shell' => $this->shell->build(),
            'page' => [
                'title' => $this->data->title,
                'lead' => $this->data->meta_description ?: null,
                'content' => $this->data->content ?: null,
            ],
            'crumbs' => $this->homeCrumbs($this->data->title),
        ])
            ->rootView('app-public')
            ->withViewData(['data' => $this->data]);
    }

    /** The contact page: a form that posts to sendMail. */
    private function renderContact(): Response
    {
        $user = Auth::user();

        return Inertia::render('public/Contact', [
            'shell' => $this->shell->build(),
            'title' => $this->data->title,
            'crumbs' => $this->homeCrumbs($this->data->title),
            'action' => route('sendform'),
            'csrfToken' => csrf_token(),
            'captchaHtml' => app('captcha')->render(),
            'prefill' => $user ? [
                'first_name' => $user->name,
                'last_name' => $user->surname,
                'email' => $user->email,
            ] : null,
        ])
            ->rootView('app-public')
            ->withViewData(['data' => $this->data]);
    }

    /** Home -> current title breadcrumb, matching the Blade banner. */
    private function homeCrumbs(string $title): array
    {
        return [
            ['label' => get_general_settings('website_name') ?: config('app.name'), 'url' => rtrim(config('app.url'), '/')],
            ['label' => $title, 'url' => null],
        ];
    }

    /**
     * Render the homepage as an Inertia page. SEO stays in Blade: the entity is
     * handed to the seo-meta partial via withViewData on the app-public root, so
     * the server-rendered <head> is unchanged from the Blade era.
     */
    private function renderHome(array $customFields): Response
    {
        $shell = $this->shell->build();

        return Inertia::render('public/Home', [
            'shell' => $shell,
            'page' => [
                'title' => $this->data->title ?? $shell['general']['websiteName'],
            ],
            'hero' => [
                'headline' => get_field('headline', $customFields) ?: null,
                'background' => get_field('headline-image', $customFields) ?: null,
            ],
            'postsSection' => $this->homePostsSection($customFields),
            'about' => $this->homeAbout($customFields),
        ])
            ->rootView('app-public')
            ->withViewData([
                'data' => $this->data,
                'author' => $this->entityAuthor(),
            ]);
    }

    /** @return array<string, mixed> */
    private function homePostsSection(array $customFields): array
    {
        $categoryId = get_field('posts-from-category-cat-id', $customFields);

        $posts = [];
        if ($categoryId) {
            $rows = get_category_posts([
                'fields' => [
                    'post_translations.title',
                    'post_translations.slug',
                    'post_translations.preview',
                    'post_translations.updated_at',
                    'post_translations.thumbnail',
                ],
                'category_id' => $categoryId,
                'count' => 4,
            ]);

            foreach ($rows ?: [] as $post) {
                $posts[] = [
                    'title' => $post->title,
                    'url' => rtrim(config('app.url'), '/').'/posts/'.$post->slug,
                    'image' => image_src($post->thumbnail),
                    'excerpt' => strip_tags((string) $post->preview),
                    'date' => Carbon::parse($post->updated_at)->format('d M Y'),
                ];
            }
        }

        return [
            'headline' => get_field('posts-from-category-headline', $customFields) ?: null,
            'description' => get_field('posts-from-category-description', $customFields) ?: null,
            'posts' => $posts,
        ];
    }

    /** @return array<string, mixed> */
    private function homeAbout(array $customFields): array
    {
        $authors = [];
        $rawAuthors = get_field('authors', $customFields);

        foreach (is_array($rawAuthors) ? $rawAuthors : [] as $author) {
            $linkedin = get_field('author-linkedin', $author);

            $authors[] = [
                'name' => get_field('author-name', $author) ?: '',
                'image' => image_src(get_field('author-image', $author) ?: null, true),
                'position' => get_field('author-position', $author) ?: null,
                'linkedinUrl' => is_array($linkedin) ? ($linkedin['url'] ?? null) : null,
                'linkedinBlank' => is_array($linkedin) && ($linkedin['target'] ?? null) === '1',
            ];
        }

        return [
            'headline' => get_field('about-headline', $customFields) ?: null,
            'description' => get_field('about-description', $customFields) ?: null,
            'body' => get_field('about-big-text', $customFields) ?: null,
            'authors' => $authors,
        ];
    }

    private function entityAuthor(): ?string
    {
        if (! isset($this->data->author)) {
            return null;
        }

        return trim(($this->data->author->name ?? '').' '.($this->data->author->surname ?? '')) ?: null;
    }

    public function sendMail(ContactRequest $request)
    {
        $this->contactService->send($request);

        return back()->with('success', \Lang::get('default/page.contact_message_success'));
    }

    public function search(): Response
    {
        return $this->renderSearch(null);
    }

    public function searchResult(SearchRequest $request, $page = 1, $count = 10): Response
    {
        $result = $this->searchService->search($request, $page, $count);

        return $this->renderSearch(
            $this->shapeResults($request->get('query'), $request->get('filter'), $result),
        );
    }

    public function paginatedResult($string, $filter, $page): Response
    {
        $result = $this->searchService->paginate($string, $filter, $page);

        return $this->renderSearch($this->shapeResults($string, $filter, $result));
    }

    /**
     * The search page: a native POST form (captcha + CSRF) plus, once a query
     * has run, the shaped result set. Search pages are noindex, so the synthetic
     * SEO entity carries meta_noindex; the body is React, the head stays Blade.
     *
     * @param  array<string, mixed>|null  $results
     */
    private function renderSearch(?array $results): Response
    {
        $title = __('default/header.searchpage_title');

        $seoEntity = (object) [
            'title' => $title,
            'meta_description' => null,
            'meta_keywords' => null,
            'canonical_url' => route('get_search_page'),
            'meta_noindex' => true,
            'thumbnail' => null,
        ];

        return Inertia::render('public/Search', [
            'shell' => $this->shell->build(),
            'title' => $title,
            'action' => route('get_search_result'),
            'csrfToken' => csrf_token(),
            'captchaHtml' => app('captcha')->render(),
            'results' => $results,
        ])
            ->rootView('app-public')
            ->withViewData(['data' => $seoEntity]);
    }

    /**
     * Shape a search paginator into serialisable props: each row becomes a
     * {label, url} pair (URLs via named routes, so they stay locale-correct),
     * plus the pagination metadata the React page needs.
     *
     * @param  LengthAwarePaginator  $paginator
     * @return array<string, mixed>
     */
    private function shapeResults(string $query, string $type, $paginator): array
    {
        $items = collect($paginator->items())->map(fn ($item) => match ($type) {
            'post' => ['label' => $item->title, 'url' => route('posts', ['slug' => $item->slug])],
            'page' => ['label' => $item->title, 'url' => route('front_pages', ['slug' => $item->slug])],
            'user' => ['label' => $item->username, 'url' => route('show_user', ['username' => $item->username])],
            'category' => ['label' => $item->title, 'url' => route('categories_first_page', ['slug' => $item->slug])],
            'tag' => ['label' => $item->name, 'url' => route('tags_first_page', ['slug' => $item->slug])],
        })->all();

        return [
            'query' => $query,
            'type' => $type,
            'total' => $paginator->total(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'items' => $items,
            'pageBaseUrl' => rtrim(config('app.url'), '/').'/search/query/'.rawurlencode($query).'/filter/'.$type,
        ];
    }
}
