<?php

namespace Tests\Feature\Updater;

use App\Http\Models\User;
use App\Http\Models\UserRoles;
use App\Support\Updater\UpdateService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The admin updates screen shows the current version, the cached availability,
 * and history; it re-checks and runs updates; and the "update available" state
 * is shared to admins (with manage_updates) so the top banner can render.
 */
class CPanelUpdatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('username', 'admin')->firstOrFail();
    }

    private function nonUpdater(): User
    {
        // Can see the admin panel but lacks manage_updates, so the failure is
        // specifically the manage_updates gate (401), not the panel gate.
        $role = UserRoles::create([
            'name' => 'role_'.bin2hex(random_bytes(4)),
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_updates' => 0]),
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_updates_page_renders_with_version_and_cached_availability(): void
    {
        Cache::put(UpdateService::CACHE_KEY, ['version' => '9.9.9', 'url' => 'x', 'sha256' => 'y']);

        $this->actingAs($this->admin())
            ->get(route('cpanel_updates'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/updates/Index')
                ->where('current_version', cms_version())
                ->where('available.version', '9.9.9')
                ->has('history'));
    }

    public function test_updates_page_requires_manage_updates(): void
    {
        $this->actingAs($this->nonUpdater())
            ->get(route('cpanel_updates'))
            ->assertStatus(401);
    }

    public function test_check_refreshes_the_cached_availability(): void
    {
        config(['cms.update.channel' => 'https://feed.test/releases.json']);
        Http::fake(['https://feed.test/releases.json' => Http::response(['releases' => [
            ['version' => '2.5.0', 'url' => 'https://x/2.tar.gz', 'sha256' => 'abc'],
        ]])]);

        $this->actingAs($this->admin())
            ->post(route('cpanel_updates_check'))
            ->assertRedirect();

        $this->assertSame('2.5.0', Cache::get(UpdateService::CACHE_KEY)['version']);
    }

    public function test_update_available_is_shared_to_admins_who_can_manage_updates(): void
    {
        Cache::put(UpdateService::CACHE_KEY, ['version' => '3.1.0', 'url' => 'x', 'sha256' => 'y']);

        $this->actingAs($this->admin())
            ->get(route('cpanel_updates'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('cms.updateAvailable', '3.1.0'));
    }

    public function test_update_available_is_hidden_from_users_without_permission(): void
    {
        Cache::put(UpdateService::CACHE_KEY, ['version' => '3.1.0', 'url' => 'x', 'sha256' => 'y']);

        // A guest on the public site must never see the update prop.
        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('cms.updateAvailable', null));
    }
}
