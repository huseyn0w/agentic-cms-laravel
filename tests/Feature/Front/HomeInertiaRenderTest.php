<?php

namespace Tests\Feature\Front;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The homepage is the first public page on Inertia. Its <body> is React; its
 * <head> stays server-rendered by Blade (seo-meta via the app-public root), so
 * these tests check both: the Inertia component + shaped props, and that the
 * SEO head still ships in the raw HTML.
 */
class HomeInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_homepage_renders_the_inertia_component_with_shaped_props(): void
    {
        $this->get('/')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('public/Home')
                ->has('shell.menu')
                ->has('shell.languages')
                ->has('shell.auth')
                ->has('page.title')
                ->has('hero')
                ->has('postsSection.posts')
                ->has('about.authors')
        );
    }

    public function test_homepage_still_ships_the_seo_head_in_html(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // The Blade seo-meta partial owns the head; exactly one title, plus the
        // canonical link, proves withViewData reached it.
        $this->assertSame(1, substr_count($html, '<title>'));
        $this->assertStringContainsString('<link rel="canonical"', $html);
    }

    public function test_homepage_uses_the_public_root_with_theme_stylesheet(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // app-public root loads the public theme build (.theme-default).
        $this->assertMatchesRegularExpression('#/build/assets/app-[^"]+\.css#', $html);
    }
}
