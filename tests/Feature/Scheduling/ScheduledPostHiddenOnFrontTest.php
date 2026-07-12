<?php

namespace Tests\Feature\Scheduling;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\PostTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * A post scheduled for the future must not be reachable on the public site
 * until its time (it has status 0 + a future scheduled_at). Once due/published
 * it is visible again. Guards the front read paths (detail + sitemap + lists).
 */
class ScheduledPostHiddenOnFrontTest extends TestCase
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

    private function makePost(string $slug, $scheduledAt, int $status = 1): PostTranslation
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

    public function test_future_scheduled_post_detail_is_404(): void
    {
        $this->makePost('hidden-post', now()->addDay(), status: 0);

        $this->get('/posts/hidden-post')->assertNotFound();
    }

    public function test_due_or_unscheduled_post_detail_is_visible(): void
    {
        $this->makePost('shown-post', now()->subHour(), status: 1);

        $this->get('/posts/shown-post')->assertOk()->assertSee('shown-post');
    }

    public function test_published_post_with_lingering_future_schedule_stays_visible(): void
    {
        // Publishing (status 1) wins over a pending schedule: a published post is
        // always public, even if a future scheduled_at lingers on the row.
        $this->makePost('published-lingering', now()->addDay(), status: 1);

        $this->get('/posts/published-lingering')->assertOk();
    }

    public function test_future_scheduled_post_is_excluded_from_sitemap(): void
    {
        $this->makePost('sitemap-hidden', now()->addDay(), status: 0);
        $this->makePost('sitemap-shown', now()->subHour(), status: 1);

        Cache::forget('agentic_cms_laravel.sitemap.xml');
        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString('sitemap-hidden', $xml, 'Scheduled post must not be in the sitemap.');
        $this->assertStringContainsString('sitemap-shown', $xml);
    }
}
