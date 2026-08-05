<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMail as ContactRequest;
use App\Http\Requests\SearchRequest;
use App\Services\Front\ContactService;
use App\Services\Front\PageViewService;
use App\Services\Front\PublicShell;
use App\Services\Front\SearchService;
use Carbon\Carbon;
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

        // The home template is on Inertia (Phase 4); the remaining page
        // templates stay on Blade until their own slices.
        if ($this->data->template === 'home') {
            return $this->renderHome($customFields);
        }

        $data = ['data' => $this->data];

        if (! empty($customFields)) {
            $data['custom_fields'] = $customFields;
        }

        return view('default.pages.'.$this->data->template, $data);
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

    public function search()
    {
        return view('default.pages.search');
    }

    public function searchResult(SearchRequest $request, $page = 1, $count = 10)
    {
        $searchData['query'] = $request->get('query');
        $searchData['type'] = $request->get('filter');
        $searchData['result'] = $this->searchService->search($request, $page, $count);

        return view('default.pages.search', compact('searchData'));
    }

    public function paginatedResult($string, $filter, $page)
    {
        $searchData['query'] = $string;
        $searchData['type'] = $filter;
        $searchData['result'] = $this->searchService->paginate($string, $filter, $page);

        return view('default.pages.search', compact('searchData'));
    }
}
