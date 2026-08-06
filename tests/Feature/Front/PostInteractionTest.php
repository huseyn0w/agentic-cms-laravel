<?php

namespace Tests\Feature\Front;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\Comments;
use App\Http\Models\PostTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Authenticated front-end post interactions: liking a post and the comment
 * store / update / delete AJAX routes. All require auth and enforce ownership.
 * Seeded post id 1 has slug "introducing-the-cms".
 */
class PostInteractionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        $this->user = User::factory()->create(['role_id' => 2]);
    }

    public function test_like_requires_authentication(): void
    {
        $this->post('/posts/handlelike/1', ['postId' => 1, 'userId' => 1])
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_like_and_unlike_a_post(): void
    {
        $before = (int) PostTranslation::where('post_id', 1)->where('locale', 'en')->value('likes');

        $this->actingAs($this->user)
            ->post('/posts/handlelike/1', ['postId' => 1, 'userId' => $this->user->id])
            ->assertOk();

        $this->assertDatabaseHas('user_post_likes', ['post_id' => 1, 'user_id' => $this->user->id]);
        $this->assertSame($before + 1, (int) PostTranslation::where('post_id', 1)->where('locale', 'en')->value('likes'));

        // Liking again toggles it off.
        $this->actingAs($this->user)
            ->post('/posts/handlelike/1', ['postId' => 1, 'userId' => $this->user->id])
            ->assertOk();

        $this->assertDatabaseMissing('user_post_likes', ['post_id' => 1, 'user_id' => $this->user->id]);
        $this->assertSame($before, (int) PostTranslation::where('post_id', 1)->where('locale', 'en')->value('likes'));
    }

    public function test_user_cannot_like_on_behalf_of_another_user(): void
    {
        $other = User::factory()->create(['role_id' => 2]);

        $this->actingAs($this->user)
            ->post('/posts/handlelike/1', ['postId' => 1, 'userId' => $other->id])
            ->assertOk();

        // handleLike returns false for a spoofed userId; nothing is recorded.
        $this->assertDatabaseMissing('user_post_likes', ['post_id' => 1, 'user_id' => $other->id]);
    }

    public function test_comment_store_requires_authentication(): void
    {
        $this->post('/posts/handlecomment/1', ['post_id' => 1, 'comment' => 'hi'])
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_store_a_comment(): void
    {
        $this->actingAs($this->user)
            ->post('/posts/handlecomment/1', [
                'post_id' => 1,
                'parent_id' => null,
                'comment' => 'A new comment',
            ])->assertRedirect();

        $this->assertDatabaseHas('post_comments', [
            'post_id' => 1,
            'user_id' => $this->user->id,
            'comment' => 'A new comment',
            'status' => 0, // non-admin comments start unapproved
        ]);
    }

    public function test_user_can_update_their_own_comment(): void
    {
        $comment = Comments::create([
            'post_id' => 1, 'parent_id' => null, 'comment' => 'orig',
            'user_id' => $this->user->id, 'status' => 1,
        ]);

        $this->actingAs($this->user)
            ->put('/posts/handlecomment/', [
                'updated_comment_id' => $comment->id,
                'comment' => 'edited',
            ])->assertRedirect();

        $this->assertSame('edited', $comment->fresh()->comment);
    }

    public function test_user_can_delete_their_own_comment(): void
    {
        $comment = Comments::create([
            'post_id' => 1, 'parent_id' => null, 'comment' => 'to delete',
            'user_id' => $this->user->id, 'status' => 1,
        ]);

        $this->actingAs($this->user)
            ->delete('/posts/deletecomment/'.$comment->id, ['commentId' => $comment->id])
            ->assertRedirect();

        $this->assertDatabaseMissing('post_comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_another_users_comment(): void
    {
        $owner = User::factory()->create(['role_id' => 2]);
        $comment = Comments::create([
            'post_id' => 1, 'parent_id' => null, 'comment' => 'protected',
            'user_id' => $owner->id, 'status' => 1,
        ]);

        $this->actingAs($this->user)
            ->delete('/posts/deletecomment/'.$comment->id, ['commentId' => $comment->id])
            ->assertRedirect();

        // Ownership check rejects the deletion: the comment must still exist.
        $this->assertDatabaseHas('post_comments', ['id' => $comment->id]);
    }

    /**
     * Broken-access-control guard: authorization is by the comment's real owner,
     * never a client-supplied username. An attacker passing their OWN username
     * (which the old check compared against self) must still not delete a
     * comment they do not own.
     */
    public function test_user_cannot_delete_others_comment_by_spoofing_own_username(): void
    {
        $owner = User::factory()->create(['role_id' => 2]);
        $comment = Comments::create([
            'post_id' => 1, 'parent_id' => null, 'comment' => 'victim comment',
            'user_id' => $owner->id, 'status' => 1,
        ]);

        $this->actingAs($this->user)
            ->delete('/posts/deletecomment/'.$comment->id, [
                'commentId' => $comment->id,
                'username' => $this->user->username, // attacker's own username
            ])->assertRedirect();

        $this->assertDatabaseHas('post_comments', ['id' => $comment->id]);
    }

    public function test_admin_can_delete_any_comment(): void
    {
        $owner = User::factory()->create(['role_id' => 2]);
        $admin = User::where('username', 'admin')->firstOrFail();
        $comment = Comments::create([
            'post_id' => 1, 'parent_id' => null, 'comment' => 'moderated away',
            'user_id' => $owner->id, 'status' => 1,
        ]);

        $this->actingAs($admin)
            ->delete('/posts/deletecomment/'.$comment->id, ['commentId' => $comment->id])
            ->assertRedirect();

        $this->assertDatabaseMissing('post_comments', ['id' => $comment->id]);
    }
}
