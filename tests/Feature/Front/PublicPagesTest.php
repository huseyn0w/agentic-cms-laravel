<?php

namespace Tests\Feature\Front;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public front-end rendering: home, pages, posts, category archives, search,
 * public user profiles and the localized route variants. All read-only and
 * available to guests. Seeded fixtures (see CPanel*Seeder) provide:
 *   page  "/" (home), page "contact"
 *   post  "introducing-the-cms"
 *   category "announcements" / "about"
 *   user  "admin"
 */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_page_renders(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_home_head_preloads_critical_fonts(): void
    {
        // DESIGN_SYSTEM §3/§7: the two critical woff2 weights are preloaded so
        // there is no layout shift on the LCP text.
        $html = $this->get('/')->assertStatus(200)->getContent();

        $this->assertMatchesRegularExpression(
            '/<link rel="preload" href="[^"]*newsreader-latin-wght-normal[^"]*\.woff2"[^>]*as="font"[^>]*type="font\/woff2"[^>]*crossorigin/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<link rel="preload" href="[^"]*inter-latin-wght-normal[^"]*\.woff2"[^>]*as="font"[^>]*type="font\/woff2"[^>]*crossorigin/',
            $html
        );
    }

    public function test_mobile_drawer_is_aria_modal(): void
    {
        // DESIGN_SYSTEM §5/§8: the focus-trapped mobile drawer must be a modal.
        $html = $this->get('/')->assertStatus(200)->getContent();

        $this->assertStringContainsString('id="mobile-drawer"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringNotContainsString('aria-modal="false"', $html);
    }

    public function test_content_page_renders(): void
    {
        $this->get('/contact')->assertStatus(200);
    }

    public function test_single_post_renders(): void
    {
        $this->get('/posts/introducing-the-cms')->assertStatus(200);
    }

    public function test_missing_post_returns_404(): void
    {
        $this->get('/posts/this-post-does-not-exist')->assertStatus(404);
    }

    public function test_category_archive_renders(): void
    {
        $this->get('/category/announcements')->assertStatus(200);
    }

    public function test_category_paginated_page_renders(): void
    {
        $this->get('/category/announcements/page/1')->assertStatus(200);
    }

    public function test_public_user_profile_renders(): void
    {
        $this->get('/users/admin')->assertStatus(200);
    }

    public function test_localized_post_route_renders(): void
    {
        // {locale?}/posts/{slug}; en is the default locale.
        $this->get('/en/posts/introducing-the-cms')->assertStatus(200);
    }

    public function test_localized_category_route_renders(): void
    {
        $this->get('/en/category/announcements')->assertStatus(200);
    }
}
