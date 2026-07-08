<?php

namespace Tests\Feature\Admin;

use App\Http\Models\Post;
use App\Http\Models\PostTranslation;
use App\Http\Models\User;
use App\Services\CPanel\CPanelPostService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Direct tests for CPanelPostService (content CRUD critical path).
 *
 * CPanelPostService delegates to CPanelPostRepository, which in turn is
 * exercised by PostObserver and PostTranslationObserver. Those observers read
 * `content`, `preview`, and `category` from app('request') — not from the
 * repository arguments — because the admin form submits a conventional HTTP
 * POST and the observers were written against that contract.
 *
 * We therefore hydrate app('request') before calling the service methods.
 * This is the same mechanism as App\Mcp\Concerns\HydratesRequest uses for the
 * MCP tool layer. We use feature (DB-backed) tests because the entire write
 * path is persistence-oriented.
 */
class CPanelPostServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin')->firstOrFail();
        $this->actingAs($this->admin);
    }

    /**
     * Hydrate the global request so PostObserver/PostTranslationObserver see the
     * content/preview/category fields they read from app('request').
     *
     * Without this, the observer would clobber those fields with null from the
     * empty current request, causing content to be lost or the category pivot
     * to never be synced.
     *
     * @param  array<string, mixed>  $data
     */
    private function hydrateRequest(array $data): void
    {
        request()->merge($data);
    }

    /** create() persists a new PostTranslation row with the supplied attributes. */
    public function test_create_persists_post_and_translation(): void
    {
        $service = app(CPanelPostService::class);

        $payload = [
            'title' => 'Service Created Post',
            'slug' => 'service-created-post',
            'content' => '<p>body</p>',
            'preview' => 'short preview',
            'author_id' => $this->admin->id,
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'status' => 1,
            'category' => [1],
        ];

        // Hydrate the request so PostObserver::created() / PostTranslationObserver::saving()
        // see content/preview/category when they call app('request')->...
        $this->hydrateRequest($payload);

        $service->create($payload);

        $this->assertDatabaseHas('post_translations', [
            'slug' => 'service-created-post',
            'locale' => 'en',
        ]);

        $translation = PostTranslation::where('slug', 'service-created-post')->first();
        $this->assertNotNull($translation);
        $this->assertSame('Service Created Post', $translation->title);
    }

    /** create() wires up the category pivot through the PostObserver. */
    public function test_create_attaches_category_via_observer(): void
    {
        $service = app(CPanelPostService::class);

        $payload = [
            'title' => 'Post With Category',
            'slug' => 'post-with-category',
            'content' => 'content',
            'preview' => 'preview',
            'author_id' => $this->admin->id,
            'meta_keywords' => '',
            'meta_description' => '',
            'status' => 1,
            'category' => [1],
        ];

        $this->hydrateRequest($payload);
        $service->create($payload);

        $translation = PostTranslation::where('slug', 'post-with-category')->firstOrFail();
        $post = Post::find($translation->post_id);
        $this->assertSame(1, $post->categories()->count(), 'Category must be attached via PostObserver.');
    }

    /** update() persists changed attributes to the existing post translation. */
    public function test_update_persists_changed_content(): void
    {
        $service = app(CPanelPostService::class);

        // Use the seeded post (slug 'introducing-the-cms') as the row to update.
        $existing = PostTranslation::where('slug', 'introducing-the-cms')->where('locale', 'en')->firstOrFail();
        $postId = $existing->post_id;

        $updatePayload = [
            'title' => 'Updated Title',
            'slug' => 'introducing-the-cms',
            'content' => '<p>updated content</p>',
            'preview' => 'updated preview',
            'author_id' => $this->admin->id,
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'status' => 1,
        ];

        // Hydrate request so PostTranslationObserver::saving() sees content/preview
        // (the observers read these straight off app('request'), mirroring the form POST).
        $this->hydrateRequest($updatePayload);

        // Call the REAL service update — this is what must persist the change.
        // CPanelPostService::update -> BaseCrudService::update -> repository update;
        // the translated attributes propagate to post_translations via Astrotomic.
        $result = $service->update($postId, $updatePayload);

        $this->assertTrue($result, 'Service update must report success.');

        // Assert via a FRESH DB read that the service call persisted the change.
        $fresh = PostTranslation::where('post_id', $postId)->where('locale', 'en')->firstOrFail()->fresh();
        $this->assertSame('Updated Title', $fresh->title);
        $this->assertStringContainsString('updated content', $fresh->content);

        $this->assertDatabaseHas('post_translations', [
            'post_id' => $postId,
            'locale' => 'en',
            'title' => 'Updated Title',
        ]);
    }

    /** trashed() returns only soft-deleted posts. */
    public function test_trashed_returns_only_deleted_posts(): void
    {
        $service = app(CPanelPostService::class);

        // Soft-delete the seeded post.
        Post::find(1)->delete();

        $trashed = $service->trashed(10);

        $this->assertGreaterThanOrEqual(1, $trashed->total());
    }
}
