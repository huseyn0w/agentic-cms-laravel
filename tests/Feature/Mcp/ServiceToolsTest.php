<?php

namespace Tests\Feature\Mcp;

use App\Http\Models\Service;
use App\Http\Models\ServiceTranslation;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use App\Mcp\Servers\AgenticCmsLaravelServer;
use App\Mcp\Tools\Services\CreateServiceTool;
use App\Mcp\Tools\Services\DeleteServiceTool;
use App\Mcp\Tools\Services\GetServiceTool;
use App\Mcp\Tools\Services\ListServicesTool;
use App\Mcp\Tools\Services\UpdateServiceTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * MCP Service tool family: verifies the manage_services auth gate and the
 * happy-path CRUD, mirroring the tag/category tool tests.
 */
class ServiceToolsTest extends TestCase
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

    private function makeService(string $title, string $locale = 'en'): Service
    {
        $id = DB::table('services')->insertGetId(['sort_order' => 0]);

        ServiceTranslation::create([
            'service_id' => $id,
            'locale' => $locale,
            'title' => $title,
            'slug' => Str::slug($title),
            'status' => 1,
        ]);

        return Service::find($id);
    }

    // --- Auth gate -----------------------------------------------------------

    public function test_list_services_rejects_unauthenticated_callers(): void
    {
        AgenticCmsLaravelServer::tool(ListServicesTool::class, [])
            ->assertSee('Authentication required');
    }

    public function test_create_service_denies_user_without_manage_services(): void
    {
        $user = $this->userWithPermissions(['manage_services' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(CreateServiceTool::class, ['title' => 'Hosting'])
            ->assertSee('Permission denied');
    }

    public function test_delete_service_denies_user_without_manage_services(): void
    {
        $user = $this->userWithPermissions(['manage_services' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(DeleteServiceTool::class, ['id' => 1])
            ->assertSee('Permission denied');
    }

    // --- Happy path ----------------------------------------------------------

    public function test_create_service_returns_ok_and_persists(): void
    {
        $user = $this->userWithPermissions(['manage_services' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(CreateServiceTool::class, [
                'title' => 'Managed Hosting',
                'locale' => 'en',
                'excerpt' => 'We host your app.',
                'status' => 1,
            ])
            ->assertOk()
            ->assertSee('created');

        $this->assertDatabaseHas('service_translations', ['slug' => 'managed-hosting']);
    }

    public function test_list_services_returns_pagination_shape(): void
    {
        $user = $this->userWithPermissions(['manage_services' => 1]);
        $this->makeService('Hosting');
        $this->makeService('SEO');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(ListServicesTool::class, ['locale' => 'en', 'per_page' => 10])
            ->assertOk()
            ->assertSee('total')
            ->assertSee('services');
    }

    public function test_get_service_returns_correct_service(): void
    {
        $user = $this->userWithPermissions(['manage_services' => 1]);
        $service = $this->makeService('Consulting');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(GetServiceTool::class, ['id' => $service->id, 'locale' => 'en'])
            ->assertOk()
            ->assertSee('Consulting');
    }

    public function test_update_service_changes_fields(): void
    {
        $user = $this->userWithPermissions(['manage_services' => 1]);
        $service = $this->makeService('Old Title');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(UpdateServiceTool::class, [
                'id' => $service->id,
                'locale' => 'en',
                'title' => 'New Title',
            ])
            ->assertOk()
            ->assertSee('updated');

        $this->assertDatabaseHas('service_translations', ['service_id' => $service->id, 'title' => 'New Title']);
    }

    public function test_delete_service_soft_deletes(): void
    {
        $user = $this->userWithPermissions(['manage_services' => 1]);
        $service = $this->makeService('Temp');

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(DeleteServiceTool::class, ['id' => $service->id])
            ->assertOk()
            ->assertSee('deleted');

        $this->assertNull(Service::find($service->id));
        $this->assertNotNull(Service::withTrashed()->find($service->id));
    }
}
