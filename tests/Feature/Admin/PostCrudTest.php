<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\Post;
use App\Http\Models\PostTranslation;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Full admin CRUD round-trip for posts. Posts are translatable (Astrotomic):
 * the editable content lives in `post_translations`. The `category` field is a
 * validated input consumed by the PostObserver (pivot sync) and must never
 * reach the model's mass assignment.
 */
class PostCrudTest extends TestCase
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

    private function postPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Round Trip Post',
            'slug' => 'round-trip-post',
            'content' => 'post body',
            'preview' => 'preview text',
            'author_id' => $this->admin->id,
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'category' => [1],
            'status' => 1,
        ], $overrides);
    }

    public function test_admin_can_create_a_post(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('cpanel_posts_list'));

        $translation = PostTranslation::where('slug', 'round-trip-post')->first();
        $this->assertNotNull($translation, 'Post translation was not persisted.');
        $this->assertSame('Round Trip Post', $translation->title);
        $this->assertSame('en', $translation->locale);

        // The category must be attached through the observer (pivot), not mass
        // assigned onto the post row.
        $post = Post::find($translation->post_id);
        $this->assertSame(1, $post->categories()->count());
    }

    public function test_admin_can_update_a_post(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $translation = PostTranslation::where('slug', 'round-trip-post')->firstOrFail();

        $response = $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/update', $this->postPayload([
                'content' => 'edited body',
            ]));

        $response->assertSessionHasNoErrors();

        $fresh = PostTranslation::where('slug', 'round-trip-post')->firstOrFail();
        $this->assertStringContainsString('edited body', $fresh->content);
    }

    public function test_admin_can_soft_delete_a_post(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $translation = PostTranslation::where('slug', 'round-trip-post')->firstOrFail();
        $postId = $translation->post_id;

        $this->actingAs($this->admin)
            ->delete('/agentic-cms-laravel-admin/posts/'.$postId.'/delete')
            ->assertOk();

        $this->assertNull(Post::find($postId), 'Post should be soft deleted.');
        $this->assertNotNull(Post::withTrashed()->find($postId), 'Soft deleted post row should remain.');
    }

    public function test_admin_can_restore_a_soft_deleted_post(): void
    {
        // Single restore: GET /posts/{id}/restore must actually restore (the
        // route must not be shadowed by the greedy /{id}/{lang} editor route).
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $postId = PostTranslation::where('slug', 'round-trip-post')->firstOrFail()->post_id;
        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/posts/'.$postId.'/delete');
        $this->assertNull(Post::find($postId), 'Post should be trashed first.');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/posts/'.$postId.'/restore')
            ->assertRedirect();

        $this->assertNotNull(Post::find($postId), 'Post should be restored.');
    }

    public function test_destroy_endpoint_cannot_permanently_delete_a_live_post(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $postId = PostTranslation::where('slug', 'round-trip-post')->firstOrFail()->post_id;

        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/posts/'.$postId.'/destroy');

        $this->assertNotNull(Post::find($postId), 'A live post must not be force-deleted via destroy.');
    }

    public function test_validation_blocks_post_without_required_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/posts/new')
            ->post('/agentic-cms-laravel-admin/posts/new', ['title' => '']);

        $response->assertSessionHasErrors(['title', 'slug', 'author_id', 'category']);
        $this->assertSame(0, PostTranslation::where('slug', 'round-trip-post')->count());
    }

    public function test_user_without_admin_access_cannot_reach_posts(): void
    {
        // role_id 2 lacks see_admin_panel, so the panel gate denies first (403).
        $user = User::factory()->create(['role_id' => 2]);

        $this->actingAs($user)
            ->get('/agentic-cms-laravel-admin/posts')
            ->assertStatus(403);
    }

    public function test_user_with_panel_access_but_no_post_permission_is_blocked(): void
    {
        // Can see the panel, but lacks manage_posts -> ManagePosts aborts 401.
        $role = UserRoles::create([
            'name' => 'PanelOnly',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_posts' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin')->assertOk();
        $this->actingAs($user)->get('/agentic-cms-laravel-admin/posts')->assertStatus(401);
    }
}
