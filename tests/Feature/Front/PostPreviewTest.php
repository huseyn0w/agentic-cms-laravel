<?php

namespace Tests\Feature\Front;

use App\Http\Models\PostTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Admin-only post preview: GET /agentic-cms-laravel-admin/posts/{id}/preview
 * renders the public post page for a draft/scheduled post that the public site
 * hides. Gated behind auth + manage_posts; forced noindex + preview banner.
 * Seeded post id 1 has the "en" translation.
 */
class PostPreviewTest extends TestCase
{
    use RefreshDatabase;

    private const PREVIEW_URL = '/agentic-cms-laravel-admin/posts/1/preview';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);

        // Hide post 1 from the public site: future-scheduled + not published.
        PostTranslation::where('post_id', 1)->where('locale', 'en')
            ->update(['status' => 0, 'scheduled_at' => now()->addDays(3)]);
    }

    public function test_admin_can_preview_a_hidden_post(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(self::PREVIEW_URL)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('public/Post')
                ->where('preview', true)
                ->where('post.id', 1)
                // The preview renders the public post page (PublicLayout), which
                // reads the shell shared prop. HandleInertiaRequests skips the
                // shell on admin routes, so it must be re-enabled for the preview
                // route or PublicLayout crashes on a null shell.
                ->has('shell.auth')
                ->has('shell.menu'));
    }

    public function test_public_route_still_hides_the_scheduled_post(): void
    {
        // The same post is a 404 on its public slug — preview does not leak it.
        $this->get('/posts/introducing-the-cms')->assertNotFound();
    }

    public function test_preview_requires_authentication(): void
    {
        $this->get(self::PREVIEW_URL)->assertRedirect('/login');
    }

    public function test_preview_denied_without_manage_posts_permission(): void
    {
        $user = User::factory()->create(['role_id' => 2]);

        $this->actingAs($user)->get(self::PREVIEW_URL)->assertForbidden();
    }

    public function test_preview_of_unknown_post_is_not_found(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)->get('/agentic-cms-laravel-admin/posts/999999/preview')
            ->assertNotFound();
    }
}
