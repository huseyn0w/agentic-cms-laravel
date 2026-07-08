<?php

namespace Tests\Feature\Front;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\Post;
use App\Repositories\TagRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public search: filter=tag returns matching tags with links to their archive.
 * Mirrors the pattern in SearchContactLanguageTest / TagArchiveTest.
 */
class SearchTagsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        app()->setLocale('en');
    }

    /** Searching for an existing tag name with filter=tag returns 200 and a link to /tag/{slug}. */
    public function test_search_with_tag_filter_returns_matching_tag(): void
    {
        // Attach a known tag to seeded post 1 so the tag exists in tag_translations.
        $post = Post::findOrFail(1);
        app(TagRepository::class)->syncToPost($post, ['Laravel']);

        $response = $this->post('/search', [
            'query' => 'Laravel',
            'filter' => 'tag',
        ]);

        $response->assertStatus(200);
        // The result list must link to the tag archive.
        $response->assertSee('tag/laravel', false);
    }

    /** An unrelated query produces a zero-result page (no crash, no 5xx). */
    public function test_search_with_tag_filter_and_no_match_shows_empty_state(): void
    {
        $response = $this->post('/search', [
            'query' => 'nonexistent-xyz-tag-abc',
            'filter' => 'tag',
        ]);

        $response->assertStatus(200);
        // Nothing found — the empty-state component should render the translated
        // headline (previously this asserted the raw lang KEY, which only appeared
        // because `@lang()` in a component attribute leaked its literal directive).
        $response->assertSee(__('default/page.search_nothing_found'));
    }

    /** Paginated search route also works for filter=tag. */
    public function test_paginated_tag_search_renders(): void
    {
        $this->get('/search/query/Laravel/filter/tag/page/1')
            ->assertStatus(200);
    }
}
