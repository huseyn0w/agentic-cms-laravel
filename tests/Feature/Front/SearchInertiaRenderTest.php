<?php

namespace Tests\Feature\Front;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\Post;
use App\Repositories\TagRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Public search is on the React public/Search page. The GET landing shows the
 * form only; POST and the pretty paginated GET render shaped results. The SEO
 * head (noindex) stays server-rendered by Blade via the app-public root.
 */
class SearchInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
        app()->setLocale('en');
    }

    public function test_get_search_page_renders_the_form_only(): void
    {
        $this->get('/search')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('public/Search')
                ->has('shell.menu')
                ->has('action')
                ->has('csrfToken')
                ->where('results', null)
        );
    }

    public function test_post_search_renders_shaped_results(): void
    {
        $post = Post::findOrFail(1);
        app(TagRepository::class)->syncToPost($post, ['Laravel']);

        $this->post('/search', ['query' => 'Laravel', 'filter' => 'tag'])
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('public/Search')
                    ->where('results.type', 'tag')
                    ->where('results.query', 'Laravel')
                    ->where('results.items.0.url', route('tags_first_page', ['slug' => 'laravel']))
            );
    }

    public function test_paginated_search_renders_results(): void
    {
        $this->get('/search/query/post/filter/post/page/1')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('public/Search')
                    ->where('results.type', 'post')
                    ->where('results.currentPage', 1)
            );
    }

    public function test_search_page_is_noindex(): void
    {
        $this->get('/search')->assertOk()
            ->assertSee('noindex', false);
    }
}
