<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\Menu;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * DESIGN_SYSTEM §5: the menu builder must offer keyboard-accessible reordering
 * (not drag-only) and §7 bans CDN scripts. This asserts:
 *   1. the edit screen ships the accessible reorder affordances (self-hosted
 *      reorder script + aria-live region) and no longer references the googleapis
 *      jQuery UI CDN;
 *   2. the reorder persists through the existing menu-update round-trip (the
 *      builder serialises the new order into `content` on save).
 */
class MenuReorderTest extends TestCase
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

    public function test_edit_menu_screen_renders_the_inertia_builder_with_items(): void
    {
        // The builder moved from the Blade edit_menu + menu-reorder.js to the
        // React cpanel/menus/Form. The keyboard-accessible reorder affordances
        // (move up/down buttons + an aria-live region) and the CDN-free
        // guarantee now live in that component; their behaviour is covered by
        // resources/js/pages/cpanel/menus/Form.test.tsx. Here we assert the
        // edit route renders the Inertia builder with the menu's parsed items.
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/menus/1/en')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/menus/Form')
                ->where('entity.id', 1)
                ->has('entity.items')
                ->has('terms_list.pages'));
    }

    public function test_reorder_persists_through_update_round_trip(): void
    {
        // Simulate the builder serialising a reordered list into `content`
        // (Contact now precedes Homepage — the keyboard/drag path produces this).
        $reordered = json_encode([
            ['slug' => 'contact', 'type' => 'pages', 'title' => 'Contact'],
            ['slug' => '/', 'type' => 'pages', 'title' => 'Homepage'],
        ]);

        $response = $this->actingAs($this->admin)->put(
            '/agentic-cms-laravel-admin/menus/1/update',
            [
                'title' => 'Header Menu',
                'slug' => 'header_menu',
                'content' => $reordered,
            ]
        );

        $response->assertSessionHasNoErrors();

        $menu = Menu::findOrFail(1);
        $stored = $menu->translate('en')->content;
        $decoded = json_decode($stored, true);

        $this->assertIsArray($decoded);
        $this->assertSame('contact', $decoded[0]['slug'], 'Reordered menu order was not persisted.');
        $this->assertSame('/', $decoded[1]['slug']);
    }
}
