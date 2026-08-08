<?php

namespace Tests\Feature\CPanel;

use App\Http\Models\CPanel\CPanelThemeSettings;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Theme settings (tier-1 theming): the admin singleton must persist, and its
 * values must be injected as CSS variables into the public root Blade — that
 * injection is the whole point (re-skin with no rebuild). Colours/fonts are
 * validated because they are inlined into CSS.
 */
class CPanelThemeSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    private function saveTheme(array $overrides = []): CPanelThemeSettings
    {
        $theme = CPanelThemeSettings::firstOrNew(['id' => 1]);
        $theme->fill(array_merge([
            'site_title' => 'Elman Group',
            'accent_color' => '#ff0000',
            'font_family' => 'Georgia, serif',
            'radius' => 4,
        ], $overrides));
        $theme->save();

        Cache::flush();

        return $theme;
    }

    public function test_admin_theme_settings_page_renders(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('cpanel_theme_settings'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/settings/Theme')
                ->has('theme_settings.accent_color')
                ->has('theme_settings.radius'));
    }

    public function test_admin_can_persist_theme_settings(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('cpanel_update_theme_settings'), [
                'site_title' => 'Elman Group',
                'accent_color' => '#3366FF',
                'font_family' => 'Georgia, serif',
                'radius' => '12',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('theme_settings', [
            'id' => 1,
            'site_title' => 'Elman Group',
            // Normalised to lowercase on save.
            'accent_color' => '#3366ff',
            'radius' => 12,
        ]);
    }

    public function test_public_page_injects_the_accent_css_variable(): void
    {
        $this->saveTheme(['accent_color' => '#ff0000', 'radius' => 4]);

        $html = $this->get('/')->assertStatus(200)->getContent();

        $this->assertStringContainsString('id="cms-theme-vars"', $html);
        $this->assertStringContainsString('--accent:#ff0000', $html);
        $this->assertStringContainsString('--radius-md:4px', $html);
    }

    public function test_no_style_block_when_theme_is_unconfigured(): void
    {
        // Fresh DB (no theme row) → no override block, shipped defaults stand.
        $html = $this->get('/')->assertStatus(200)->getContent();

        $this->assertStringNotContainsString('id="cms-theme-vars"', $html);
    }

    public function test_invalid_accent_color_is_rejected(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('cpanel_update_theme_settings'), [
                'accent_color' => 'red; } body { display:none }',
            ])
            ->assertSessionHasErrors('accent_color');
    }

    public function test_unsafe_font_family_is_rejected(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('cpanel_update_theme_settings'), [
                'font_family' => 'Georgia; } * { color:red }',
            ])
            ->assertSessionHasErrors('font_family');
    }
}
