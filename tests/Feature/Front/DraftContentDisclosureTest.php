<?php

namespace Tests\Feature\Front;

use App\Http\Models\Page;
use App\Http\Models\PageTranslation;
use App\Http\Models\Post;
use App\Http\Models\PostTranslation;
use App\Services\Front\PostViewService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for H1: content-disclosure bug on public single-record reads.
 *
 * Before the fix, the front applyFrontReadScope hook only filtered
 * future-scheduled drafts (via notScheduledForFuture), leaving plain draft
 * records (status=0, no scheduled_at) publicly reachable by slug for both
 * posts and pages. This test suite verifies the correct secure behaviour.
 */
class DraftContentDisclosureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    // ── POST tests ──────────────────────────────────────────────────────────

    /**
     * A plain DRAFT post (status=0, no scheduled_at) MUST NOT be publicly
     * accessible — the front route must return 404.
     */
    public function test_plain_draft_post_returns_404_for_guest(): void
    {
        $post = Post::create([]);
        PostTranslation::create([
            'post_id' => $post->id,
            'locale' => 'en',
            'title' => 'Draft Post',
            'slug' => 'draft-post-secret',
            'author_id' => 1,
            'status' => 0,          // draft
            'scheduled_at' => null,        // no schedule
            'preview' => 'Draft preview',
            'content' => 'Draft content that must NOT be public',
            'meta_keywords' => '',
            'meta_description' => '',
        ]);

        $this->get('/posts/draft-post-secret')->assertStatus(404);
    }

    /**
     * A PUBLISHED post (status=1) MUST be accessible (no regression).
     * Uses the seeded 'introducing-the-cms' fixture which has full data required by
     * the post template (categories, etc.) to avoid 500s from missing relations.
     */
    public function test_published_post_returns_200_for_guest(): void
    {
        $this->get('/posts/introducing-the-cms')->assertStatus(200);
    }

    /**
     * A PUBLISHED post with a FUTURE scheduled_at MUST still be resolvable by
     * the service — publishing overrides a lingering schedule. We test via the
     * service (not the full HTTP route) to avoid 500s from missing view data on
     * a minimal inline fixture. The HTTP behaviour is covered by test_public_route_*
     * in PostViewServiceTest for the seeded fixture.
     */
    public function test_published_post_with_future_schedule_is_resolvable(): void
    {
        $post = Post::create([]);
        PostTranslation::create([
            'post_id' => $post->id,
            'locale' => 'en',
            'title' => 'Published Scheduled',
            'slug' => 'published-scheduled-post',
            'author_id' => 1,
            'status' => 1,                    // published
            'scheduled_at' => now()->addDays(3),    // future schedule — ignored when published
            'preview' => '',
            'content' => 'Content',
            'meta_keywords' => '',
            'meta_description' => '',
        ]);

        $service = app(PostViewService::class);
        $result = $service->resolveBySlug('published-scheduled-post');

        $this->assertNotNull($result, 'A published post must be resolvable even with a future schedule.');
        $this->assertSame('published-scheduled-post', $result->slug);
    }

    /**
     * A DRAFT post with a FUTURE scheduled_at MUST NOT be visible —
     * it's a pending scheduled post (still unpublished). Preserves existing
     * notScheduledForFuture behaviour.
     */
    public function test_future_scheduled_draft_post_returns_404(): void
    {
        $post = Post::create([]);
        PostTranslation::create([
            'post_id' => $post->id,
            'locale' => 'en',
            'title' => 'Future Draft',
            'slug' => 'future-draft-post',
            'author_id' => 1,
            'status' => 0,                    // draft
            'scheduled_at' => now()->addDay(),      // pending schedule
            'preview' => '',
            'content' => '',
            'meta_keywords' => '',
            'meta_description' => '',
        ]);

        $this->get('/posts/future-draft-post')->assertStatus(404);
    }

    /**
     * A DRAFT post whose schedule has already PASSED (status=0, past scheduled_at)
     * MUST NOT be visible — it has not been published and must remain hidden
     * regardless of the past schedule.
     */
    public function test_past_scheduled_draft_post_returns_404(): void
    {
        $post = Post::create([]);
        PostTranslation::create([
            'post_id' => $post->id,
            'locale' => 'en',
            'title' => 'Past Scheduled Draft',
            'slug' => 'past-scheduled-draft-post',
            'author_id' => 1,
            'status' => 0,                    // draft — still not published
            'scheduled_at' => now()->subDay(),      // schedule already passed
            'preview' => '',
            'content' => '',
            'meta_keywords' => '',
            'meta_description' => '',
        ]);

        // The post has a past schedule but was never promoted to published (status=0).
        // It must NOT be publicly reachable.
        $this->get('/posts/past-scheduled-draft-post')->assertStatus(404);
    }

    // ── PAGE tests ──────────────────────────────────────────────────────────

    /**
     * A plain DRAFT page (status=0) MUST NOT be publicly accessible — 404.
     */
    public function test_plain_draft_page_returns_404_for_guest(): void
    {
        $page = Page::create([]);
        PageTranslation::create([
            'page_id' => $page->id,
            'locale' => 'en',
            'title' => 'Draft Page',
            'slug' => 'draft-page-secret',
            'author_id' => 1,
            'status' => 0,          // draft
            'template' => 'page',
            'content' => 'Draft page content that must NOT be public',
            'custom_fields' => json_encode([]),
            'meta_keywords' => '',
            'meta_description' => '',
        ]);

        $this->get('/draft-page-secret')->assertStatus(404);
    }

    /**
     * A PUBLISHED page (status=1) MUST remain accessible (no regression).
     * Uses the seeded 'contact' fixture which has full data required by the
     * contacts template to avoid 500s from missing relations.
     */
    public function test_published_page_returns_200_for_guest(): void
    {
        $this->get('/contact')->assertStatus(200);
    }
}
