<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\Comments;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
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
        config(['inertia.testing.ensure_pages_exist' => false]);
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
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/comments/List')
                ->has('comments_list.data')
                ->where('comments_list.data', function ($rows) {
                    $row = collect($rows)->first();

                    return $row !== null
                        && array_key_exists('comment', $row)
                        && array_key_exists('author', $row)
                        && array_key_exists('status', $row);
                }));
    }

    public function test_admin_can_approve_a_comment(): void
    {
        $comment = $this->makeComment(0);

        // Row action now moves through Inertia's router.put -> redirect back
        // (was a jQuery-AJAX echo of 'ok' before the Inertia migration).
        $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/comments/'.$comment->id.'/approve')
            ->assertRedirect();

        $this->assertSame(1, (int) $comment->fresh()->status);
    }

    public function test_admin_can_unapprove_a_comment(): void
    {
        $comment = $this->makeComment(1);

        $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/comments/'.$comment->id.'/unapprove')
            ->assertRedirect();

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
