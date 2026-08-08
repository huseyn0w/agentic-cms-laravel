<?php

namespace Tests\Feature\CPanel;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\CPanel\CPanelSeoSettings;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * AEO settings tab: allow/deny AI crawlers. The toggles persist on the SEO
 * settings singleton's ai_crawlers field and feed the robots.txt builder.
 */
class CPanelAeoSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_aeo_page_renders_catalog_and_allow_map(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/agentic-cms-laravel-admin/aeo-settings')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/settings/Aeo')
                ->has('ai_crawler_catalog')
                ->has('ai_crawlers'));
    }

    public function test_blocking_a_crawler_adds_a_disallow_stanza_to_robots(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)->post('/agentic-cms-laravel-admin/aeo-settings', [
            'ai_crawlers' => ['gptbot' => false],
        ])->assertRedirect();

        // Persisted on the SEO singleton's ai_crawlers field.
        $this->assertFalse(CPanelSeoSettings::first()->ai_crawlers['gptbot']);

        $robots = $this->get('/robots.txt');
        $robots->assertOk();
        $robots->assertSee('User-agent: GPTBot', false);
    }

    public function test_aeo_page_requires_authentication(): void
    {
        $this->get('/agentic-cms-laravel-admin/aeo-settings')->assertRedirect('/login');
    }
}
