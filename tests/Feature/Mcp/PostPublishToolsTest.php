<?php

namespace Tests\Feature\Mcp;

use App\Http\Models\Post;
use App\Http\Models\PostTranslation;
use App\Http\Models\Revision;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use App\Mcp\Servers\AgenticCmsLaravelServer;
use App\Mcp\Tools\Posts\ListPostRevisionsTool;
use App\Mcp\Tools\Posts\PublishPostTool;
use App\Mcp\Tools\Posts\RestorePostRevisionTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * MCP post-publish and revision tool families: verifies the auth gate,
 * that PublishPostTool flips a draft translation to published and is
 * asserted via DB, and that revision list/restore delegate cleanly.
 */
class PostPublishToolsTest extends TestCase
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
     * Insert a post + one translation row via the query builder, bypassing
     * the Translatable trait / observers so we have full control of status.
     */
    private function makeDraftPost(User $author, string $locale = 'en', int $status = 0): PostTranslation
    {
        $postId = DB::table('posts')->insertGetId([]);

        $slug = 'draft-post-'.bin2hex(random_bytes(4));

        DB::table('post_translations')->insert([
            'post_id' => $postId,
            'author_id' => $author->id,
            'locale' => $locale,
            'title' => 'Draft Post',
            'slug' => $slug,
            'status' => $status,
            'content' => '<p>body</p>',
            'preview' => 'preview',
            'meta_description' => '',
            'meta_keywords' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return PostTranslation::where('slug', $slug)->firstOrFail();
    }

    // -----------------------------------------------------------------------
    // Auth gate — PublishPostTool
    // -----------------------------------------------------------------------

    public function test_publish_post_rejects_unauthenticated_callers(): void
    {
        AgenticCmsLaravelServer::tool(PublishPostTool::class, ['id' => 1])
            ->assertSee('Authentication required');
    }

    public function test_publish_post_denies_user_without_manage_posts(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(PublishPostTool::class, ['id' => 1])
            ->assertSee('Permission denied');
    }

    // -----------------------------------------------------------------------
    // Auth gate — ListPostRevisionsTool
    // -----------------------------------------------------------------------

    public function test_list_post_revisions_rejects_unauthenticated_callers(): void
    {
        AgenticCmsLaravelServer::tool(ListPostRevisionsTool::class, ['id' => 1, 'locale' => 'en'])
            ->assertSee('Authentication required');
    }

    public function test_list_post_revisions_denies_user_without_manage_posts(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ListPostRevisionsTool::class, ['id' => 1, 'locale' => 'en'])
            ->assertSee('Permission denied');
    }

    // -----------------------------------------------------------------------
    // Auth gate — RestorePostRevisionTool
    // -----------------------------------------------------------------------

    public function test_restore_post_revision_rejects_unauthenticated_callers(): void
    {
        AgenticCmsLaravelServer::tool(RestorePostRevisionTool::class, ['id' => 1, 'locale' => 'en', 'revision_id' => 1])
            ->assertSee('Authentication required');
    }

    public function test_restore_post_revision_denies_user_without_manage_posts(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(RestorePostRevisionTool::class, ['id' => 1, 'locale' => 'en', 'revision_id' => 1])
            ->assertSee('Permission denied');
    }

    // -----------------------------------------------------------------------
    // Happy path — PublishPostTool flips draft to published
    // -----------------------------------------------------------------------

    public function test_publish_post_flips_draft_to_published(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);
        $translation = $this->makeDraftPost($user, 'en', 0);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(PublishPostTool::class, ['id' => $translation->post_id, 'locale' => 'en'])
            ->assertOk()
            ->assertSee('published');

        $this->assertDatabaseHas('post_translations', [
            'id' => $translation->id,
            'status' => Post::STATUS_PUBLISHED,
        ]);
    }

    public function test_publish_post_clears_scheduled_at(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);
        $translation = $this->makeDraftPost($user, 'en', 0);

        // Stamp a future schedule manually
        DB::table('post_translations')->where('id', $translation->id)->update([
            'scheduled_at' => now()->addDay(),
        ]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(PublishPostTool::class, ['id' => $translation->post_id, 'locale' => 'en'])
            ->assertOk();

        $fresh = PostTranslation::find($translation->id);
        $this->assertNull($fresh->scheduled_at, 'Schedule should be cleared on publish.');
        $this->assertSame(Post::STATUS_PUBLISHED, (int) $fresh->status);
    }

    public function test_publish_post_returns_error_for_nonexistent_post(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(PublishPostTool::class, ['id' => 99999])
            ->assertSee('No post found');
    }

    public function test_publish_post_without_locale_publishes_all_translations(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);

        // Insert a post with two translations
        $postId = DB::table('posts')->insertGetId([]);

        foreach (['en', 'de'] as $locale) {
            $slug = 'multi-'.$locale.'-'.bin2hex(random_bytes(4));
            DB::table('post_translations')->insert([
                'post_id' => $postId,
                'author_id' => $user->id,
                'locale' => $locale,
                'title' => "Title {$locale}",
                'slug' => $slug,
                'status' => 0,
                'content' => 'body',
                'preview' => 'preview',
                'meta_description' => '',
                'meta_keywords' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(PublishPostTool::class, ['id' => $postId])
            ->assertOk()
            ->assertSee('"translations_published":2');

        $this->assertSame(
            2,
            PostTranslation::where('post_id', $postId)->where('status', Post::STATUS_PUBLISHED)->count()
        );
    }

    // -----------------------------------------------------------------------
    // Happy path — ListPostRevisionsTool
    // -----------------------------------------------------------------------

    public function test_list_post_revisions_returns_empty_when_no_revisions(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);
        $translation = $this->makeDraftPost($user);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ListPostRevisionsTool::class, [
                'id' => $translation->post_id,
                'locale' => 'en',
            ])
            ->assertOk()
            ->assertSee('"total":0');
    }

    public function test_list_post_revisions_returns_error_for_missing_translation(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);
        $translation = $this->makeDraftPost($user, 'en');

        // Ask for a locale that has no translation
        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ListPostRevisionsTool::class, [
                'id' => $translation->post_id,
                'locale' => 'xx',
            ])
            ->assertSee('No');
    }

    public function test_list_post_revisions_returns_existing_revisions(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);
        $translation = $this->makeDraftPost($user, 'en');

        // Insert a revision manually (mirrors what the updating observer does)
        Revision::create([
            'revisionable_type' => $translation->getMorphClass(),
            'revisionable_id' => $translation->id,
            'user_id' => $user->id,
            'data' => $translation->getAttributes(),
        ]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ListPostRevisionsTool::class, [
                'id' => $translation->post_id,
                'locale' => 'en',
            ])
            ->assertOk()
            ->assertSee('"total":1');
    }

    // -----------------------------------------------------------------------
    // Happy path — RestorePostRevisionTool
    // -----------------------------------------------------------------------

    public function test_restore_post_revision_returns_error_for_missing_translation(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(RestorePostRevisionTool::class, [
                'id' => 99999,
                'locale' => 'en',
                'revision_id' => 1,
            ])
            ->assertSee('Could not restore');
    }

    public function test_restore_post_revision_restores_content(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);
        $translation = $this->makeDraftPost($user, 'en');

        // Snapshot the original state as a revision
        $originalAttributes = $translation->getAttributes();
        $revision = Revision::create([
            'revisionable_type' => $translation->getMorphClass(),
            'revisionable_id' => $translation->id,
            'user_id' => $user->id,
            'data' => $originalAttributes,
        ]);

        // Change the title directly in DB (simulates an edit after the snapshot)
        DB::table('post_translations')
            ->where('id', $translation->id)
            ->update(['title' => 'Changed Title']);

        // Restore the revision — the title should revert
        AgenticCmsLaravelServer::actingAs($user)
            ->tool(RestorePostRevisionTool::class, [
                'id' => $translation->post_id,
                'locale' => 'en',
                'revision_id' => $revision->id,
            ])
            ->assertOk()
            ->assertSee('restored');

        $this->assertDatabaseHas('post_translations', [
            'id' => $translation->id,
            'title' => 'Draft Post',
        ]);
    }
}
