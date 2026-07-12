<?php

namespace Tests\Feature\Scheduling;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\PostTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin post form exposes the schedule: an editor can set a future
 * scheduled_at (and a draft status), and it persists on the translation.
 */
class PostScheduleFormTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Sched Post', 'slug' => 'sched-post', 'content' => 'body', 'preview' => 'prev',
            'author_id' => $this->admin->id, 'meta_keywords' => 'k', 'meta_description' => 'd',
            'category' => [1], 'status' => 1,
        ], $overrides);
    }

    public function test_admin_can_schedule_a_post_via_the_form(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->payload());
        $postId = PostTranslation::where('slug', 'sched-post')->firstOrFail()->post_id;

        $future = now()->addDays(3)->format('Y-m-d H:i:s');
        $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/posts/'.$postId.'/update', $this->payload([
                'status' => 0, 'scheduled_at' => $future,
            ]))
            ->assertSessionHasNoErrors();

        $fresh = PostTranslation::where('slug', 'sched-post')->firstOrFail();
        $this->assertNotNull($fresh->scheduled_at, 'Schedule should persist from the form.');
        $this->assertSame(0, (int) $fresh->status);

        // And it is hidden on the front.
        $this->get('/posts/sched-post')->assertNotFound();
    }

    public function test_clearing_the_schedule_field_unschedules_the_post(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->payload());
        $postId = PostTranslation::where('slug', 'sched-post')->firstOrFail()->post_id;
        PostTranslation::where('post_id', $postId)->update(['scheduled_at' => now()->addDay()]);

        // Re-save with an empty schedule field -> schedule cleared.
        $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/posts/'.$postId.'/update', $this->payload(['scheduled_at' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull(PostTranslation::where('slug', 'sched-post')->firstOrFail()->scheduled_at);
    }
}
