<?php

namespace Tests\Feature\Scheduling;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\PostTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Scheduled publishing (FEATURE_MATRIX §1): a post carries a nullable
 * `scheduled_at`; the `posts:publish-due` command flips due, not-yet-published
 * posts to published and clears the schedule. Future schedules are untouched.
 */
class PublishDuePostsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    /**
     * Create a draft (status 0) post via the admin flow and stamp a schedule
     * onto its translation (query builder -> no observers).
     */
    private function scheduledDraft(string $slug, $scheduledAt, int $status = 0): PostTranslation
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', [
            'title' => $slug, 'slug' => $slug, 'content' => 'body', 'preview' => 'prev',
            'author_id' => $this->admin->id, 'meta_keywords' => 'k', 'meta_description' => 'd',
            'category' => [1], 'status' => $status,
        ]);

        $t = PostTranslation::where('slug', $slug)->firstOrFail();
        PostTranslation::where('id', $t->id)->update(['scheduled_at' => $scheduledAt, 'status' => $status]);

        return $t->fresh();
    }

    public function test_due_draft_is_published_and_unscheduled(): void
    {
        $t = $this->scheduledDraft('due-post', now()->subHour());

        Artisan::call('posts:publish-due');

        $fresh = PostTranslation::findOrFail($t->id);
        $this->assertSame(1, (int) $fresh->status, 'Due post should be published.');
        $this->assertNull($fresh->scheduled_at, 'Schedule should be cleared after publishing.');
    }

    public function test_future_schedule_is_left_untouched(): void
    {
        $t = $this->scheduledDraft('future-post', now()->addDay());

        Artisan::call('posts:publish-due');

        $fresh = PostTranslation::findOrFail($t->id);
        $this->assertSame(0, (int) $fresh->status, 'Future post must stay a draft.');
        $this->assertNotNull($fresh->scheduled_at, 'Future schedule must remain.');
    }

    public function test_already_published_post_is_not_touched(): void
    {
        // Past schedule but already published — the command must not re-touch it.
        $t = $this->scheduledDraft('live-post', now()->subHour(), status: 1);

        Artisan::call('posts:publish-due');

        $fresh = PostTranslation::findOrFail($t->id);
        $this->assertSame(1, (int) $fresh->status);
        // Its (stale) schedule is left as-is — only drafts are transitioned.
        $this->assertNotNull($fresh->scheduled_at);
    }
}
