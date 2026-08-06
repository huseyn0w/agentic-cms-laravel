<?php

namespace Tests\Feature\Front;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The remaining PageController templates (standard page, contact) and the
 * public user profile, now on Inertia. Bodies are React; the SEO head stays
 * server-rendered by Blade (the profile route emits ProfilePage + Person).
 */
class PublicPageTemplatesInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_standard_page_renders_the_page_component(): void
    {
        // Seeded "about" page uses the standard 'page' template.
        $this->get('/about')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('public/Page')
                ->has('shell.menu')
                ->has('page.title')
                ->has('crumbs')
        );
    }

    public function test_contact_page_renders_the_contact_component(): void
    {
        $this->get('/contact')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('public/Contact')
                ->has('action')
                ->has('csrfToken')
                ->where('prefill', null)
        );
    }

    public function test_public_profile_renders_the_profile_component(): void
    {
        $this->get('/users/admin')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('public/Profile')
                ->has('profile.displayName')
                ->has('profile.socials')
                ->where('profile.username', 'admin')
        );
    }

    public function test_public_profile_ships_profilepage_json_ld(): void
    {
        $html = $this->get('/users/admin')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<title>'));
        $this->assertStringContainsString('"@type": "ProfilePage"', $html);
        $this->assertStringContainsString('"@type": "Person"', $html);
    }

    public function test_menu_items_carry_the_internal_flag_for_client_navigation(): void
    {
        // The header menu drives Inertia vs full-load links; typed items are
        // internal (prefetching <Link>), custom links are not.
        $this->get('/about')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page->has('shell.menu.0.internal')
        );
    }
}
