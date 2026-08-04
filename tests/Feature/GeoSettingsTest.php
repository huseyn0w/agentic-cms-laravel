<?php

namespace Tests\Feature;

use App\Http\Models\CPanel\CPanelGeoSettings;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * GEO settings: the admin singleton must persist, and its values must flow into
 * the front-end machine-readable surfaces — schema.org JSON-LD on the homepage
 * (Organization/LocalBusiness + FAQPage) and the /llms.txt index — which is the
 * whole point of the feature (making the site citable by generative engines).
 */
class GeoSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    private function saveGeo(array $overrides = []): CPanelGeoSettings
    {
        $geo = CPanelGeoSettings::firstOrNew(['id' => 1]);
        $geo->fill(array_merge([
            'business_name' => 'Elman Group',
            'business_type' => 'LocalBusiness',
            'description' => 'Custom Laravel CMS and AI integration studio.',
            'services' => "Laravel development\nCustom CMS\nAI / MCP integration",
            'service_area' => 'Baku, Azerbaijan; Remote, EU',
            'contact_email' => 'contact@elman.group',
            'same_as' => "https://linkedin.com/in/huseyn0w\nhttps://github.com/huseyn0w",
            'faq' => 'Do you work remotely? | Yes, with clients across the EU and worldwide.',
            'emit_jsonld' => true,
            'include_in_llms' => true,
        ], $overrides));
        $geo->save();

        Cache::flush();

        return $geo;
    }

    public function test_homepage_emits_geo_enriched_jsonld(): void
    {
        $this->saveGeo();

        $html = $this->get('/')->assertStatus(200)->getContent();

        // @type reflects the chosen business_type.
        $this->assertMatchesRegularExpression('/"@type":\s*"LocalBusiness"/', $html);
        // Services become knowsAbout + Service offers.
        $this->assertStringContainsString('AI / MCP integration', $html);
        $this->assertMatchesRegularExpression('/"@type":\s*"Service"/', $html);
        // sameAs authority links.
        $this->assertStringContainsString('linkedin.com/in/huseyn0w', $html);
        // FAQ becomes a FAQPage block.
        $this->assertMatchesRegularExpression('/"@type":\s*"FAQPage"/', $html);
        $this->assertStringContainsString('Do you work remotely?', $html);
    }

    public function test_jsonld_is_suppressed_when_toggle_is_off(): void
    {
        $this->saveGeo(['emit_jsonld' => false]);

        $html = $this->get('/')->assertStatus(200)->getContent();

        // Falls back to the plain Organization block; no GEO extras.
        $this->assertStringNotContainsString('AI / MCP integration', $html);
        $this->assertDoesNotMatchRegularExpression('/"@type":\s*"FAQPage"/', $html);
    }

    public function test_llms_txt_includes_about_and_services(): void
    {
        $this->saveGeo();

        $body = $this->get('/llms.txt')->assertStatus(200)->getContent();

        $this->assertStringContainsString('## About', $body);
        $this->assertStringContainsString('## Services', $body);
        $this->assertStringContainsString('Laravel development', $body);
        $this->assertStringContainsString('Service area: Baku, Azerbaijan; Remote, EU', $body);
    }

    public function test_admin_geo_settings_page_renders(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('cpanel_geo_settings'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/settings/Geo')
                ->has('geo_settings.business_type')
                ->has('geo_settings.services')
                ->where('geo_settings.emit_jsonld', fn ($v) => is_bool($v)));
    }

    public function test_admin_can_persist_geo_settings(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('cpanel_update_geo_settings'), [
                'business_name' => 'Elman Group',
                'business_type' => 'ProfessionalService',
                'description' => 'We build CMS platforms.',
                'services' => "Web development\nConsulting",
                'emit_jsonld' => '1',
                'include_in_llms' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('geo_settings', [
            'id' => 1,
            'business_name' => 'Elman Group',
            'business_type' => 'ProfessionalService',
        ]);
    }

    public function test_invalid_business_type_is_rejected(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('cpanel_update_geo_settings'), [
                'business_type' => 'NotARealType',
            ])
            ->assertSessionHasErrors('business_type');
    }
}
