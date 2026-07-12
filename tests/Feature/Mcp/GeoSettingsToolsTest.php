<?php

namespace Tests\Feature\Mcp;

use App\Http\Models\User;
use App\Http\Models\UserRoles;
use App\Mcp\Servers\AgenticCmsLaravelServer;
use App\Mcp\Tools\Settings\GetGeoSettingsTool;
use App\Mcp\Tools\Settings\UpdateGeoSettingsTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MCP GEO-settings tool family: verifies the auth gate and the
 * get→update→get round-trip asserting persistence via a fresh read,
 * mirroring the SEO settings test patterns.
 */
class GeoSettingsToolsTest extends TestCase
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

    // -----------------------------------------------------------------------
    // Auth gate — GetGeoSettingsTool
    // -----------------------------------------------------------------------

    public function test_get_geo_settings_rejects_unauthenticated_callers(): void
    {
        AgenticCmsLaravelServer::tool(GetGeoSettingsTool::class, [])
            ->assertSee('Authentication required');
    }

    public function test_get_geo_settings_denies_user_without_manage_general_settings(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(GetGeoSettingsTool::class, [])
            ->assertSee('Permission denied');
    }

    // -----------------------------------------------------------------------
    // Auth gate — UpdateGeoSettingsTool
    // -----------------------------------------------------------------------

    public function test_update_geo_settings_rejects_unauthenticated_callers(): void
    {
        AgenticCmsLaravelServer::tool(UpdateGeoSettingsTool::class, ['business_name' => 'Acme'])
            ->assertSee('Authentication required');
    }

    public function test_update_geo_settings_denies_user_without_manage_general_settings(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 0]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(UpdateGeoSettingsTool::class, ['business_name' => 'Acme'])
            ->assertSee('Permission denied');
    }

    // -----------------------------------------------------------------------
    // Happy path — read on empty DB returns defaults (no crash)
    // -----------------------------------------------------------------------

    public function test_get_geo_settings_returns_ok_on_empty_db(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(GetGeoSettingsTool::class, [])
            ->assertOk()
            ->assertSee('business_name');
    }

    // -----------------------------------------------------------------------
    // Happy path — update requires at least one field
    // -----------------------------------------------------------------------

    public function test_update_geo_settings_requires_at_least_one_field(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(UpdateGeoSettingsTool::class, [])
            ->assertSee('Nothing to update');
    }

    // -----------------------------------------------------------------------
    // Happy path — get → update → get round-trip asserts persistence
    // -----------------------------------------------------------------------

    public function test_update_geo_settings_persists_business_name(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(UpdateGeoSettingsTool::class, [
                'business_name' => 'Elman Group',
                'business_type' => 'Organization',
            ])
            ->assertOk()
            ->assertSee('updated');

        $this->assertDatabaseHas('geo_settings', ['business_name' => 'Elman Group']);
    }

    public function test_update_geo_settings_round_trip_via_fresh_read(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 1]);

        // First write
        AgenticCmsLaravelServer::actingAs($user)
            ->tool(UpdateGeoSettingsTool::class, [
                'business_name' => 'Round Trip Co',
                'business_type' => 'LocalBusiness',
                'contact_email' => 'hello@example.com',
                'emit_jsonld' => true,
                'include_in_llms' => false,
            ])
            ->assertOk();

        // Confirm persistence via a fresh GET (tests the full stack — not a cache hit)
        AgenticCmsLaravelServer::actingAs($user)
            ->tool(GetGeoSettingsTool::class, [])
            ->assertOk()
            ->assertSee('Round Trip Co')
            ->assertSee('LocalBusiness')
            ->assertSee('hello@example.com');
    }

    public function test_update_geo_settings_persists_services_and_faq(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 1]);

        AgenticCmsLaravelServer::actingAs($user)
            ->tool(UpdateGeoSettingsTool::class, [
                'business_name' => 'Acme',
                'business_type' => 'Organization',
                'services' => "Web Development\nSEO Consulting",
                'faq' => "What do you do? | We build things.\nWhere are you? | Online.",
                'same_as' => 'https://linkedin.com/company/acme',
            ])
            ->assertOk()
            ->assertSee('updated');

        $this->assertDatabaseHas('geo_settings', ['business_name' => 'Acme']);
    }

    public function test_update_geo_settings_only_changes_passed_fields(): void
    {
        $user = $this->userWithPermissions(['manage_general_settings' => 1]);

        // Set initial state
        AgenticCmsLaravelServer::actingAs($user)
            ->tool(UpdateGeoSettingsTool::class, [
                'business_name' => 'First Name',
                'business_type' => 'Organization',
                'contact_phone' => '+1 555 0000',
            ])
            ->assertOk();

        // Update only the phone; business_name must remain unchanged
        AgenticCmsLaravelServer::actingAs($user)
            ->tool(UpdateGeoSettingsTool::class, [
                'contact_phone' => '+1 555 9999',
            ])
            ->assertOk();

        $this->assertDatabaseHas('geo_settings', [
            'business_name' => 'First Name',
            'contact_phone' => '+1 555 9999',
        ]);
    }
}
