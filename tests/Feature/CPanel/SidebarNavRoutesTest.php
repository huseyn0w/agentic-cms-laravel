<?php

namespace Tests\Feature\CPanel;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin sidebar links to hard-coded URLs in resources/js/lib/admin-nav.ts.
 * Front-end tests never hit the router, so a typo there (e.g. /menu vs /menus,
 * or /settings which has no route) ships a 404 nav item unnoticed. This test
 * reads the real nav config and asserts every href resolves for an admin.
 */
class SidebarNavRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    public function test_every_sidebar_nav_href_resolves_for_admin(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $nav = file_get_contents(resource_path('js/lib/admin-nav.ts'));
        preg_match_all('/href:\s*`\$\{A\}([^`]*)`/', $nav, $matches);

        $hrefs = array_map(
            fn (string $suffix): string => '/agentic-cms-laravel-admin'.$suffix,
            $matches[1],
        );

        $this->assertNotEmpty($hrefs, 'No nav hrefs were extracted from admin-nav.ts');

        foreach ($hrefs as $href) {
            $this->actingAs($admin)
                ->get($href)
                ->assertStatus(200, "Sidebar nav href returned non-200: {$href}");
        }
    }
}
