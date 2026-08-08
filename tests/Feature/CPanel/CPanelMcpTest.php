<?php

namespace Tests\Feature\CPanel;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The MCP connection guide is a read-only settings screen gated by
 * manage_general_settings. It surfaces the server endpoint + OAuth discovery URL.
 */
class CPanelMcpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_mcp_page_renders_with_endpoint_and_discovery_url(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/agentic-cms-laravel-admin/mcp')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/mcp/Index')
                ->where('endpoint', url('/mcp/agentic-cms-laravel'))
                ->where('discoveryUrl', url('/.well-known/oauth-protected-resource')));
    }

    public function test_mcp_page_requires_authentication(): void
    {
        $this->get('/agentic-cms-laravel-admin/mcp')->assertRedirect('/login');
    }
}
