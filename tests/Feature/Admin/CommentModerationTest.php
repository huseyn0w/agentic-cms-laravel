<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\Comments;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin comment moderation: list, approve, unapprove and (bulk) delete, all
 * guarded by manage_comments.
 */
class CommentModerationTest extends TestCase
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

    private function makeComment(int $status = 0): Comments
    {
        return Comments::create([
            'post_id' => 1,
            'parent_id' => null,
            'comment' => 'pending comment',
            'user_id' => $this->admin->id,
            'status' => $status,
        ]);
    }

    public function test_admin_can_view_comments_list(): void
    {
        $this->makeComment();

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/comments')
            ->assertStatus(200);
    }

    public function test_admin_can_approve_a_comment(): void
    {
        $comment = $this->makeComment(0);

        $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/comments/'.$comment->id.'/approve')
            ->assertOk();

        $this->assertSame(1, (int) $comment->fresh()->status);
    }

    public function test_admin_can_unapprove_a_comment(): void
    {
        $comment = $this->makeComment(1);

        $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/comments/'.$comment->id.'/unapprove')
            ->assertOk();

        $this->assertSame(0, (int) $comment->fresh()->status);
    }

    public function test_admin_can_bulk_delete_comments(): void
    {
        $a = $this->makeComment();
        $b = $this->makeComment();

        $this->actingAs($this->admin)
            ->delete('/agentic-cms-laravel-admin/comments/multipleDelete', ['comments' => [$a->id, $b->id]])
            ->assertRedirect();

        $this->assertDatabaseMissing('post_comments', ['id' => $a->id]);
        $this->assertDatabaseMissing('post_comments', ['id' => $b->id]);
    }

    public function test_user_with_panel_access_but_no_comment_permission_is_blocked(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoComments',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_comments' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/comments')->assertStatus(401);
    }
}
