<?php

namespace Tests\Feature\Mcp;

use App\Http\Models\Tag;
use App\Http\Models\TagTranslation;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use App\Mcp\Servers\AgenticCmsLaravelServer;
use App\Mcp\Tools\Tags\CreateTagTool;
use App\Mcp\Tools\Tags\DeleteTagTool;
use App\Mcp\Tools\Tags\GetTagTool;
use App\Mcp\Tools\Tags\ListTagsTool;
use App\Mcp\Tools\Tags\UpdateTagTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * MCP Tag tool family: verifies the auth gate and happy-path CRUD, mirroring
 * the category tool tests in AgenticCmsLaravelServerTest.
 */
class TagToolsTest extends TestCase
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

    private function makeTag(string $name, string $locale = 'en'): Tag
    {
        // Bypass the Translatable trait by inserting via the query builder so
        // we control exactly one translation row (the trait auto-inserts when
        // translated attributes are present in create()).
        $id = DB::table('tags')->insertGetId([]);

        TagTranslation::create([
            'tag_id' => $id,
            'locale' => $locale,
            'name' => $name,
            'slug' => Str::slug($name),
        ]);

        return Tag::find($id);
    }

    // -----------------------------------------------------------------------
    // Auth gate
    // -----------------------------------------------------------------------

    public function test_list_tags_rejects_unauthenticated_callers(): void
    {
        AgenticCmsLaravelServer::tool(ListTagsTool::class, [])
            ->assertSee('Authentication required');
    }

    public function test_list_tags_denies_user_without_manage_posts(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ListTagsTool::class, [])
            ->assertSee('Permission denied');
    }

    public function test_create_tag_rejects_unauthenticated_callers(): void
    {
        AgenticCmsLaravelServer::tool(CreateTagTool::class, ['name' => 'Laravel'])
            ->assertSee('Authentication required');
    }

    public function test_create_tag_denies_user_without_manage_posts(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(CreateTagTool::class, ['name' => 'Laravel'])
            ->assertSee('Permission denied');
    }

    public function test_delete_tag_rejects_unauthenticated_callers(): void
    {
        AgenticCmsLaravelServer::tool(DeleteTagTool::class, ['id' => 1])
            ->assertSee('Authentication required');
    }

    public function test_delete_tag_denies_user_without_manage_posts(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(DeleteTagTool::class, ['id' => 1])
            ->assertSee('Permission denied');
    }

    // -----------------------------------------------------------------------
    // Happy path: Create
    // -----------------------------------------------------------------------

    public function test_create_tag_returns_ok_and_persists(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(CreateTagTool::class, ['name' => 'PHP', 'locale' => 'en'])
            ->assertOk()
            ->assertSee('created');

        $this->assertDatabaseHas('tag_translations', ['name' => 'PHP']);
    }

    public function test_create_tag_is_idempotent_via_find_or_create(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);

        $first = AgenticCmsLaravelServer::actingAs($user)
            ->tool(CreateTagTool::class, ['name' => 'Laravel', 'locale' => 'en']);
        $first->assertOk();

        // Creating the same tag again should return the existing one, not a duplicate.
        $second = AgenticCmsLaravelServer::actingAs($user)
            ->tool(CreateTagTool::class, ['name' => 'Laravel', 'locale' => 'en']);
        $second->assertOk();

        $this->assertSame(1, TagTranslation::where('name', 'Laravel')->count());
    }

    // -----------------------------------------------------------------------
    // Happy path: List
    // -----------------------------------------------------------------------

    public function test_list_tags_returns_ok_with_pagination_shape(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);

        $this->makeTag('PHP');
        $this->makeTag('Laravel');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ListTagsTool::class, ['locale' => 'en', 'per_page' => 10])
            ->assertOk()
            ->assertSee('total')
            ->assertSee('tags');
    }

    // -----------------------------------------------------------------------
    // Happy path: Get
    // -----------------------------------------------------------------------

    public function test_get_tag_returns_correct_tag(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);

        $tag = $this->makeTag('Tailwind');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(GetTagTool::class, ['id' => $tag->id, 'locale' => 'en'])
            ->assertOk()
            ->assertSee('Tailwind');
    }

    // -----------------------------------------------------------------------
    // Happy path: Update
    // -----------------------------------------------------------------------

    public function test_update_tag_persists_new_name(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);

        $tag = $this->makeTag('Old Name');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(UpdateTagTool::class, ['id' => $tag->id, 'name' => 'New Name', 'locale' => 'en'])
            ->assertOk()
            ->assertSee('updated');

        $this->assertDatabaseHas('tag_translations', ['name' => 'New Name']);
    }

    public function test_update_tag_requires_at_least_one_field(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);

        $tag = $this->makeTag('Something');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(UpdateTagTool::class, ['id' => $tag->id])
            ->assertSee('Nothing to update');
    }

    // -----------------------------------------------------------------------
    // Happy path: Delete
    // -----------------------------------------------------------------------

    public function test_delete_tag_removes_record(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);

        $tag = $this->makeTag('Deleteme');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(DeleteTagTool::class, ['id' => $tag->id])
            ->assertOk()
            ->assertSee('deleted');

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_delete_nonexistent_tag_returns_error(): void
    {
        $user = $this->userWithPermissions(['manage_posts' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(DeleteTagTool::class, ['id' => 99999])
            ->assertSee('Could not delete');
    }
}
