<?php

namespace Tests\Feature\Front;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The scaffolded legal pages (Impressum, Datenschutz) exist as published pages
 * and surface as footer links in the shared public shell.
 */
class FooterLegalLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_shell_exposes_legal_footer_links(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('shell.legalLinks', 2)
                ->where('shell.legalLinks.0.title', 'Imprint')
                ->where('shell.legalLinks.0.url', url('/impressum'))
                ->where('shell.legalLinks.1.url', url('/datenschutz')));
    }

    public function test_a_legal_page_resolves_on_the_front(): void
    {
        $this->get('/impressum')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('public/Page'));
    }
}
