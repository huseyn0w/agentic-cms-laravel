<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\CPanel\CPanelSeoSettings;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Phase 7 (SEO/GEO): admin global SEO settings page — rendering, persistence
 * (round-trip), and permission gating (manage_general_settings).
 */
class SeoSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    public function test_seo_settings_page_renders(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/seo-settings')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/settings/Seo')
                ->has('seo_settings.title_separator')
                ->where('seo_settings.sitemap_enabled', fn ($v) => is_bool($v)));
    }

    public function test_admin_can_persist_seo_settings(): void
    {
        $this->actingAs($this->admin)
            ->post('/agentic-cms-laravel-admin/seo-settings', [
                'title_separator' => '|',
                'default_meta_description' => 'A persisted default description.',
                'default_og_image' => 'https://example.com/og.png',
                'og_site_name' => 'Persisted Site',
                'twitter_handle' => '@persisted',
                'google_site_verification' => 'google-token-123',
                'bing_site_verification' => 'bing-token-456',
                'ga4_measurement_id' => 'G-PERSIST123',
                'gtm_container_id' => '',
                'discourage_search_engines' => '1',
                'sitemap_enabled' => '1',
                'robots_extra' => 'Disallow: /private',
            ])
            ->assertSessionHasNoErrors();

        $seo = CPanelSeoSettings::first();
        $this->assertSame('|', $seo->title_separator);
        $this->assertSame('Persisted Site', $seo->og_site_name);
        $this->assertSame('@persisted', $seo->twitter_handle);
        $this->assertSame('G-PERSIST123', $seo->ga4_measurement_id);
        $this->assertTrue((bool) $seo->discourage_search_engines);
        $this->assertStringContainsString('Disallow: /private', $seo->robots_extra);
    }

    public function test_unchecked_toggles_persist_as_false(): void
    {
        $this->actingAs($this->admin)
            ->post('/agentic-cms-laravel-admin/seo-settings', [
                'title_separator' => '-',
                // discourage_search_engines and sitemap_enabled omitted = unchecked
            ])
            ->assertSessionHasNoErrors();

        $seo = CPanelSeoSettings::first();
        $this->assertFalse((bool) $seo->discourage_search_engines);
        $this->assertFalse((bool) $seo->sitemap_enabled);
    }

    public function test_validation_rejects_bad_input(): void
    {
        $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/seo-settings')
            ->post('/agentic-cms-laravel-admin/seo-settings', [
                'title_separator' => '',                 // required
                'default_og_image' => 'not-a-url',        // must be a url
            ])
            ->assertSessionHasErrors(['title_separator', 'default_og_image']);
    }

    // The AI-crawler (AEO) toggles moved to their own settings tab; their page
    // render + persistence are covered by Tests\Feature\CPanel\CPanelAeoSettingsTest.
    // The robots.txt stanza is still exercised here since it is a SEO-feed concern.

    public function test_robots_txt_blocks_a_disabled_ai_crawler(): void
    {
        CPanelSeoSettings::updateOrCreate(
            ['id' => 1],
            ['title_separator' => '-', 'sitemap_enabled' => true, 'ai_crawlers' => ['gptbot' => false]],
        );

        $body = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString("User-agent: GPTBot\nDisallow: /", $body);
        // A bot left allowed gets no stanza.
        $this->assertStringNotContainsString('User-agent: ClaudeBot', $body);
    }

    public function test_user_without_settings_permission_is_blocked(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoSeo',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_general_settings' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/seo-settings')->assertStatus(401);
    }
}
