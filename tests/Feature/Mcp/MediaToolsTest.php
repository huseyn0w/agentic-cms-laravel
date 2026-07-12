<?php

namespace Tests\Feature\Mcp;

use App\Http\Models\User;
use App\Http\Models\UserRoles;
use App\Mcp\Servers\AgenticCmsLaravelServer;
use App\Mcp\Tools\Media\GetMediaMetadataTool;
use App\Mcp\Tools\Media\ListMediaTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Media MCP tools: read-only listing + metadata.
 *
 * Security surface: unauthenticated callers and callers lacking the
 * manage_general_settings permission must be rejected; path traversal must never
 * escape the uploads root.
 */
class MediaToolsTest extends TestCase
{
    use RefreshDatabase;

    private string $uploadsRoot;

    /** @var list<string> */
    private array $tempFiles = [];

    private function userWithPermissions(array $flags): User
    {
        $role = UserRoles::create([
            'name' => 'role_'.bin2hex(random_bytes(4)),
            'permissions' => json_encode($flags),
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadsRoot = public_path('uploads');

        if (! is_dir($this->uploadsRoot)) {
            mkdir($this->uploadsRoot, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    private function createTempMediaFile(string $filename, string $contents = 'test-content'): string
    {
        $path = $this->uploadsRoot.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    // -----------------------------------------------------------------------
    // Authentication / authorization
    // -----------------------------------------------------------------------

    public function test_unauthenticated_list_is_rejected(): void
    {
        AgenticCmsLaravelServer::tool(ListMediaTool::class, [])
            ->assertSee('Authentication required');
    }

    public function test_unauthenticated_metadata_is_rejected(): void
    {
        AgenticCmsLaravelServer::tool(GetMediaMetadataTool::class, ['path' => 'test.jpg'])
            ->assertSee('Authentication required');
    }

    public function test_list_is_denied_without_required_permission(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ListMediaTool::class, [])
            ->assertSee('Permission denied');
    }

    public function test_metadata_is_denied_without_required_permission(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(GetMediaMetadataTool::class, ['path' => 'test.jpg'])
            ->assertSee('Permission denied');
    }

    // -----------------------------------------------------------------------
    // Happy path
    // -----------------------------------------------------------------------

    public function test_authorized_list_returns_uploaded_file(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 1]);

        $this->createTempMediaFile('mcp_test_image.jpg', 'fake-jpeg-content');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ListMediaTool::class, [])
            ->assertOk()
            ->assertSee('mcp_test_image.jpg');
    }

    public function test_authorized_metadata_returns_file_info(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 1]);

        $this->createTempMediaFile('mcp_meta_test.txt', 'hello');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(GetMediaMetadataTool::class, ['path' => 'mcp_meta_test.txt'])
            ->assertOk()
            ->assertSee('mcp_meta_test.txt');
    }

    // -----------------------------------------------------------------------
    // Path traversal / security
    // -----------------------------------------------------------------------

    public function test_list_rejects_traversal_subdirectory(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ListMediaTool::class, ['subdirectory' => '../../config'])
            ->assertSee('Rejected subdirectory');
    }

    public function test_metadata_rejects_traversal_path(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 1]);

        // Must not succeed — must see an error, not file contents
        AgenticCmsLaravelServer::actingAs($user)
            ->tool(GetMediaMetadataTool::class, ['path' => '../../.env'])
            ->assertSee('rejected path')
            ->assertDontSee('APP_KEY');
    }

    public function test_metadata_rejects_absolute_path(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(GetMediaMetadataTool::class, ['path' => '/etc/passwd'])
            ->assertSee('rejected path');
    }

    public function test_metadata_rejects_null_byte_in_path(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(GetMediaMetadataTool::class, ['path' => "valid\0../../.env"])
            ->assertSee('rejected path');
    }
}
