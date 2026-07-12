<?php

namespace Tests\Feature\Plugins;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\User;
use App\Repositories\CPanelPluginRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginAdminTest extends TestCase
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

    public function test_admin_can_see_discovered_plugins(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/plugins')
            ->assertOk()
            ->assertSee('Reading time');
    }

    public function test_admin_can_enable_a_plugin(): void
    {
        $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/plugins/toggle', ['slug' => 'reading-time', 'enabled' => 1])
            ->assertRedirect();

        $this->assertDatabaseHas('plugins', ['slug' => 'reading-time', 'enabled' => true]);
    }

    public function test_admin_can_disable_a_plugin(): void
    {
        app(CPanelPluginRepository::class)->ensureExists('reading-time');
        app(CPanelPluginRepository::class)->setEnabled('reading-time', true);

        $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/plugins/toggle', ['slug' => 'reading-time', 'enabled' => 0])
            ->assertRedirect();

        $this->assertDatabaseHas('plugins', ['slug' => 'reading-time', 'enabled' => false]);
    }

    public function test_guest_cannot_access_plugin_admin(): void
    {
        $this->get('/agentic-cms-laravel-admin/plugins')->assertRedirect();
    }

    public function test_toggle_rejects_unknown_slug(): void
    {
        $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/plugins/toggle', ['slug' => 'totally-not-a-plugin', 'enabled' => 1])
            ->assertNotFound();

        $this->assertDatabaseMissing('plugins', ['slug' => 'totally-not-a-plugin']);
    }
}
