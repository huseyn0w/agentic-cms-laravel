<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\CPanel\CPanelGeneralSettings;
use App\Http\Models\CPanel\CPanelSiteOptions;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Admin settings persistence: general-settings and site-options, both guarded
 * by manage_general_settings.
 */
class SettingsTest extends TestCase
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

    public function test_general_settings_page_renders(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/general-settings')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/settings/General')
                ->has('general_settings.website_name')
                ->where('general_settings.membership', fn ($v) => is_bool($v))
                ->has('templates'));
    }

    public function test_general_settings_persists_json_boolean_toggle(): void
    {
        // Inertia sends checkboxes as JSON booleans; the request must coerce
        // them (the old `=== 'on'` check would have stored ticked as 0).
        $this->actingAs($this->admin)
            ->postJson('/agentic-cms-laravel-admin/general-settings', [
                'website_name' => 'Bool Site',
                'tagline' => 'T',
                'posts_per_page' => 7,
                'comments_per_page' => 4,
                'contact_email' => 'b@example.com',
                'membership' => true,
                'email_verification' => false,
                'active_template_name' => 'default',
            ])
            ->assertSessionHasNoErrors();

        $settings = CPanelGeneralSettings::first();
        $this->assertSame(1, (int) $settings->membership);
        $this->assertSame(0, (int) $settings->email_verification);
    }

    public function test_admin_can_persist_general_settings(): void
    {
        $this->actingAs($this->admin)
            ->post('/agentic-cms-laravel-admin/general-settings', [
                'website_name' => 'Persisted Name',
                'tagline' => 'Persisted Tagline',
                'posts_per_page' => 9,
                'comments_per_page' => 3,
                'contact_email' => 'hi@example.com',
                'membership' => 'on',
                'active_template_name' => 'default',
            ])
            ->assertSessionHasNoErrors();

        $settings = CPanelGeneralSettings::first();
        $this->assertSame('Persisted Name', $settings->website_name);
        $this->assertSame(9, (int) $settings->posts_per_page);
    }

    public function test_site_options_page_renders(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/site-options')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/settings/SiteOptions')
                ->has('site_options.logo_url')
                ->has('site_options.copyright'));
    }

    public function test_admin_can_persist_site_options(): void
    {
        $this->actingAs($this->admin)
            ->post('/agentic-cms-laravel-admin/site-options', [
                'logo_url' => 'https://example.com/logo.png',
                'copyright' => 'Copyright 2026',
                'github_url' => 'https://github.com/example/repo',
                'linkedin_url' => 'https://linkedin.com/in/example',
            ])
            ->assertSessionHasNoErrors();

        $options = CPanelSiteOptions::first();
        $this->assertSame('https://example.com/logo.png', $options->logo_url);
        $this->assertSame('Copyright 2026', $options->copyright);
    }

    public function test_site_options_validation_rejects_non_urls(): void
    {
        $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/site-options')
            ->post('/agentic-cms-laravel-admin/site-options', [
                'logo_url' => 'not-a-url',
                'copyright' => '',
                'github_url' => 'not-a-url',
                'linkedin_url' => 'not-a-url',
            ])
            ->assertSessionHasErrors(['logo_url', 'copyright', 'github_url', 'linkedin_url']);
    }

    public function test_user_without_settings_permission_is_blocked(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoSettings',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_general_settings' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/general-settings')->assertStatus(401);
        $this->actingAs($user)->get('/agentic-cms-laravel-admin/site-options')->assertStatus(401);
    }
}
