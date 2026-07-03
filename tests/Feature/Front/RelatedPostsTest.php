<?php

namespace Tests\Feature\Front;

use App\Http\Models\Post;
use App\Http\Models\User;
use App\Repositories\PostRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FEATURE_MATRIX §1 — "Related posts" block on the post detail page, by shared
 * taxonomy (categories/tags). laravel is the canonical stack for this feature.
 *
 * Rules mirrored from the ts reference: OR-overlap on categories+tags, the
 * current post excluded, PUBLISHED-only (no drafts / future-scheduled leaks),
 * locale-scoped, ranked by number of shared terms (recency tiebreak), capped.
 *
 * Seeded fixtures: post id 1 ("post-example") in category 1.
 */
class RelatedPostsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PostRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin')->firstOrFail();
        $this->repo = app(PostRepository::class);
    }

    /**
     * Insert a published post row (+ english translation) attached to the given
     * categories/tags. Returns the new post id.
     *
     * @param  array<int>  $categoryIds
     * @param  array<int>  $tagIds
     */
    private function makePost(string $slug, array $categoryIds = [], array $tagIds = [], int $status = 1, $scheduledAt = null): int
    {
        $postId = DB::table('posts')->insertGetId([]);
        DB::table('post_translations')->insert([
            'title' => $slug,
            'slug' => $slug,
            'author_id' => $this->admin->id,
            'post_id' => $postId,
            'locale' => 'en',
            'status' => $status,
            'preview' => 'prev '.$slug,
            'content' => 'content '.$slug,
            'meta_keywords' => 'k',
            'meta_description' => 'd',
            'scheduled_at' => $scheduledAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($categoryIds as $cid) {
            DB::table('category_post')->insert(['category_id' => $cid, 'post_id' => $postId]);
        }
        foreach ($tagIds as $tid) {
            DB::table('post_tag')->insert(['tag_id' => $tid, 'post_id' => $postId]);
        }

        return $postId;
    }

    public function test_related_returns_posts_sharing_a_category_and_excludes_current(): void
    {
        $related = $this->makePost('sibling', categoryIds: [1]);

        $result = $this->repo->getRelated(1, 'en');

        $slugs = collect($result)->pluck('slug')->all();
        $this->assertContains('sibling', $slugs);
        $this->assertNotContains('post-example', $slugs, 'The current post must be excluded.');
    }

    public function test_related_excludes_drafts_and_future_scheduled(): void
    {
        $this->makePost('draft-sibling', categoryIds: [1], status: 0);
        $this->makePost('scheduled-sibling', categoryIds: [1], status: 0, scheduledAt: now()->addDay());
        $this->makePost('published-sibling', categoryIds: [1], status: 1);

        $slugs = collect($this->repo->getRelated(1, 'en'))->pluck('slug')->all();

        $this->assertContains('published-sibling', $slugs);
        $this->assertNotContains('draft-sibling', $slugs);
        $this->assertNotContains('scheduled-sibling', $slugs);
    }

    /**
     * Insert a bare tag row (+ english translation) and return its id, so the
     * post_tag FK is satisfied.
     */
    private function makeTag(string $slug): int
    {
        $tagId = DB::table('tags')->insertGetId([]);
        DB::table('tag_translations')->insert([
            'tag_id' => $tagId,
            'locale' => 'en',
            'name' => $slug,
            'slug' => $slug,
        ]);

        return $tagId;
    }

    public function test_related_ranks_by_number_of_shared_terms(): void
    {
        $tagId = $this->makeTag('shared-tag');

        // Give the source post (id 1) a second category + a tag so overlap can vary.
        DB::table('category_post')->insert(['category_id' => 2, 'post_id' => 1]);
        DB::table('post_tag')->insert(['tag_id' => $tagId, 'post_id' => 1]);

        // one shared category only
        $this->makePost('weak', categoryIds: [1]);
        // two shared categories + a shared tag → strongest overlap
        $this->makePost('strong', categoryIds: [1, 2], tagIds: [$tagId]);

        $slugs = collect($this->repo->getRelated(1, 'en'))->pluck('slug')->all();

        $this->assertNotEmpty($slugs);
        $this->assertEquals('strong', $slugs[0], 'Post with the most shared terms ranks first.');
    }

    public function test_related_respects_cap(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->makePost("cap-$i", categoryIds: [1]);
        }

        $result = $this->repo->getRelated(1, 'en', 3);

        $this->assertLessThanOrEqual(3, count($result));
    }

    public function test_related_block_renders_on_post_detail(): void
    {
        $this->makePost('rendered-sibling', categoryIds: [1]);

        $html = $this->get('/posts/post-example')->getContent();

        $this->assertStringContainsString('rendered-sibling', $html);
    }
}
