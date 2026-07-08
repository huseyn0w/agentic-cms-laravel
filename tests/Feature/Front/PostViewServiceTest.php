<?php

namespace Tests\Feature\Front;

use App\Http\Models\Post;
use App\Http\Models\PostTranslation;
use App\Services\Front\PostViewService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Direct tests for PostViewService (front read path).
 *
 * PostViewService delegates entirely to PostRepository::getBy('slug', $slug).
 * The repository joins post_translations, applies the locale filter, and — via
 * the applyFrontReadScope hook in PostRepository — applies the
 * notScheduledForFuture scope. We test via the real HTTP route so the seeded
 * locale session is handled correctly by the Localization middleware.
 *
 * We also drive the service directly for slug resolution to confirm
 * resolveBySlug returns the right data without going through a controller.
 */
class PostViewServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** resolveBySlug returns the published post translation for a known slug. */
    public function test_resolve_by_slug_returns_published_post(): void
    {
        $service = app(PostViewService::class);

        $result = $service->resolveBySlug('introducing-the-cms');

        $this->assertNotNull($result);
        $this->assertSame('introducing-the-cms', $result->slug);
    }

    /** Front route responds 200 for a published seeded post. */
    public function test_public_route_returns_200_for_published_post(): void
    {
        $this->get('/posts/introducing-the-cms')->assertOk();
    }

    /**
     * A draft post (status=0) is not returned by the front service.
     *
     * Note: the front PostRepository::getBy does NOT filter on status
     * — that gate is enforced by the controller (throwNotFound when $data is null)
     * combined with the notScheduledForFuture scope for scheduled items. Plain
     * drafts are accessible to holders of the URL on the front end (pre-existing
     * behaviour). We verify the scheduling scope only here.
     */
    public function test_future_scheduled_draft_is_not_returned_on_public_path(): void
    {
        // Create a draft post with a future schedule.
        $post = Post::create([]);
        PostTranslation::create([
            'post_id' => $post->id,
            'locale' => 'en',
            'title' => 'Future Post',
            'slug' => 'future-post',
            'author_id' => 1,
            'status' => 0,                          // draft
            'scheduled_at' => now()->addDay(),       // future schedule
            'preview' => '',
            'content' => '',
            'meta_keywords' => '',
            'meta_description' => '',
        ]);

        $service = app(PostViewService::class);
        $result = $service->resolveBySlug('future-post');

        // The notScheduledForFuture scope causes this to return null for a
        // future-scheduled draft — the service exposes null to the controller.
        $this->assertNull($result, 'Future-scheduled draft must not be visible on the public front path.');
    }

    /**
     * A DRAFT post whose schedule has already PASSED must NOT be visible.
     *
     * [CHARACTERIZATION FLIP — bug fix]: Previously this test asserted that a
     * status=0 post with a past schedule WAS returned, documenting the pre-fix
     * behaviour. That was a content-disclosure bug (H1): the notScheduledForFuture
     * scope was the only gate; it only hid future-scheduled drafts, so a draft
     * whose schedule had already passed slipped through.
     *
     * After the fix, applyFrontReadScope also requires status = STATUS_PUBLISHED,
     * so an unpublished draft (status=0) is never returned on the public path
     * regardless of its scheduled_at value.
     */
    public function test_past_scheduled_draft_is_not_returned(): void
    {
        $post = Post::create([]);
        PostTranslation::create([
            'post_id' => $post->id,
            'locale' => 'en',
            'title' => 'Past Scheduled',
            'slug' => 'past-scheduled',
            'author_id' => 1,
            'status' => 0,                       // draft — still not published
            'scheduled_at' => now()->subDay(),   // schedule already passed
            'preview' => '',
            'content' => '',
            'meta_keywords' => '',
            'meta_description' => '',
        ]);

        $service = app(PostViewService::class);
        $result = $service->resolveBySlug('past-scheduled');

        // A status=0 draft must never be visible on the public front path,
        // even if its scheduled_at is in the past.
        $this->assertNull($result, 'A draft with a past schedule must not be visible on the public front path.');
    }

    /**
     * A PUBLISHED post (status=1) WITH a future scheduled_at must STILL be
     * visible — publishing overrides a lingering schedule. This exercises the
     * scope's `OR status = STATUS_PUBLISHED` branch (the opposite direction from
     * the future-scheduled-draft case above).
     */
    public function test_published_post_with_future_schedule_is_still_visible(): void
    {
        $post = Post::create([]);
        PostTranslation::create([
            'post_id' => $post->id,
            'locale' => 'en',
            'title' => 'Published But Scheduled',
            'slug' => 'published-but-scheduled',
            'author_id' => 1,
            'status' => 1,                          // published
            'scheduled_at' => now()->addDay(),       // lingering future schedule
            'preview' => '',
            'content' => '',
            'meta_keywords' => '',
            'meta_description' => '',
        ]);

        $service = app(PostViewService::class);
        $result = $service->resolveBySlug('published-but-scheduled');

        $this->assertNotNull($result, 'A published post must be visible even with a future schedule.');
        $this->assertSame('published-but-scheduled', $result->slug);
    }
}
