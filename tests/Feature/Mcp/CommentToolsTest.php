<?php

namespace Tests\Feature\Mcp;

use App\Http\Models\Comments;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use App\Mcp\Servers\AgenticCmsLaravelServer;
use App\Mcp\Tools\Comments\DeleteCommentTool;
use App\Mcp\Tools\Comments\GetCommentTool;
use App\Mcp\Tools\Comments\ListCommentsTool;
use App\Mcp\Tools\Comments\ModerateCommentTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * MCP Comment tool family: verifies the manage_comments auth gate and
 * happy-path list/get/moderate/delete operations.
 */
class CommentToolsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(array $flags): User
    {
        $role = UserRoles::create([
            'name' => 'role_'.bin2hex(random_bytes(4)),
            'permissions' => json_encode($flags),
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    /**
     * Insert a comment row with real FK-satisfying parent records.
     *
     * SQLite enforces FK constraints when foreign_key_constraints = true (the
     * project default), so we must create a real user and post row first.
     */
    private function makeComment(string $body = 'Test comment', int $status = 0): Comments
    {
        // Lazily create the FK dependency rows.
        $postId = DB::table('posts')->insertGetId([]);
        $userId = $this->userWithPermissions([])->id;

        $id = DB::table('post_comments')->insertGetId([
            'user_id' => $userId,
            'post_id' => $postId,
            'parent_id' => null,
            'comment' => $body,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Comments::find($id);
    }

    // -----------------------------------------------------------------------
    // Auth gate — unauthenticated
    // -----------------------------------------------------------------------

    public function test_list_comments_rejects_unauthenticated_callers(): void
    {
        AgenticCmsLaravelServer::tool(ListCommentsTool::class, [])
            ->assertSee('Authentication required');
    }

    public function test_get_comment_rejects_unauthenticated_callers(): void
    {
        AgenticCmsLaravelServer::tool(GetCommentTool::class, ['id' => 1])
            ->assertSee('Authentication required');
    }

    public function test_moderate_comment_rejects_unauthenticated_callers(): void
    {
        AgenticCmsLaravelServer::tool(ModerateCommentTool::class, ['id' => 1, 'approved' => true])
            ->assertSee('Authentication required');
    }

    public function test_delete_comment_rejects_unauthenticated_callers(): void
    {
        AgenticCmsLaravelServer::tool(DeleteCommentTool::class, ['id' => 1])
            ->assertSee('Authentication required');
    }

    // -----------------------------------------------------------------------
    // Auth gate — permission denied
    // -----------------------------------------------------------------------

    public function test_list_comments_denies_user_without_manage_comments(): void
    {
        $user = $this->userWithPermissions(['manage_comments' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ListCommentsTool::class, [])
            ->assertSee('Permission denied');
    }

    public function test_get_comment_denies_user_without_manage_comments(): void
    {
        $user = $this->userWithPermissions(['manage_comments' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(GetCommentTool::class, ['id' => 1])
            ->assertSee('Permission denied');
    }

    public function test_moderate_comment_denies_user_without_manage_comments(): void
    {
        $user = $this->userWithPermissions(['manage_comments' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ModerateCommentTool::class, ['id' => 1, 'approved' => true])
            ->assertSee('Permission denied');
    }

    public function test_delete_comment_denies_user_without_manage_comments(): void
    {
        $user = $this->userWithPermissions(['manage_comments' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(DeleteCommentTool::class, ['id' => 1])
            ->assertSee('Permission denied');
    }

    // -----------------------------------------------------------------------
    // Happy path: List
    // -----------------------------------------------------------------------

    public function test_list_comments_returns_ok_with_pagination_shape(): void
    {
        $user = $this->userWithPermissions(['manage_comments' => 1]);

        $this->makeComment('First comment');
        $this->makeComment('Second comment');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ListCommentsTool::class, ['per_page' => 10])
            ->assertOk()
            ->assertSee('total')
            ->assertSee('comments');
    }

    // -----------------------------------------------------------------------
    // Happy path: Get
    // -----------------------------------------------------------------------

    public function test_get_comment_returns_correct_comment(): void
    {
        $user = $this->userWithPermissions(['manage_comments' => 1]);

        $comment = $this->makeComment('Hello world');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(GetCommentTool::class, ['id' => $comment->id])
            ->assertOk()
            ->assertSee('Hello world');
    }

    public function test_get_nonexistent_comment_returns_error(): void
    {
        $user = $this->userWithPermissions(['manage_comments' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(GetCommentTool::class, ['id' => 99999])
            ->assertSee('No comment found');
    }

    // -----------------------------------------------------------------------
    // Happy path: Moderate (approve / un-approve)
    // -----------------------------------------------------------------------

    public function test_moderate_approve_sets_status(): void
    {
        $user = $this->userWithPermissions(['manage_comments' => 1]);

        $comment = $this->makeComment('Pending comment', 0);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ModerateCommentTool::class, ['id' => $comment->id, 'approved' => true])
            ->assertOk()
            ->assertSee('moderated');

        $this->assertDatabaseHas('post_comments', ['id' => $comment->id, 'status' => 1]);
    }

    public function test_moderate_unapprove_sets_status(): void
    {
        $user = $this->userWithPermissions(['manage_comments' => 1]);

        $comment = $this->makeComment('Approved comment', 1);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ModerateCommentTool::class, ['id' => $comment->id, 'approved' => false])
            ->assertOk()
            ->assertSee('moderated');

        $this->assertDatabaseHas('post_comments', ['id' => $comment->id, 'status' => 0]);
    }

    public function test_moderate_nonexistent_comment_returns_error(): void
    {
        $user = $this->userWithPermissions(['manage_comments' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ModerateCommentTool::class, ['id' => 99999, 'approved' => true])
            ->assertSee('Could not moderate');
    }

    // -----------------------------------------------------------------------
    // Happy path: Delete
    // -----------------------------------------------------------------------

    public function test_delete_comment_removes_record(): void
    {
        $user = $this->userWithPermissions(['manage_comments' => 1]);

        $comment = $this->makeComment('Delete me');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(DeleteCommentTool::class, ['id' => $comment->id])
            ->assertOk()
            ->assertSee('deleted');

        $this->assertDatabaseMissing('post_comments', ['id' => $comment->id]);
    }

    public function test_delete_nonexistent_comment_returns_error(): void
    {
        $user = $this->userWithPermissions(['manage_comments' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(DeleteCommentTool::class, ['id' => 99999])
            ->assertSee('Could not delete');
    }
}
