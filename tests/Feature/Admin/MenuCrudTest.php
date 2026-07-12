<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\MenuTranslation;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Full admin CRUD round-trip for menus (translatable, content lives in
 * `menu_translations`). Also covers the previously-broken
 * `cpanel_add_new_menu` form, which 500'd on SQLite due to an ambiguous
 * `order by id` in the posts/pages translation joins used to build the
 * menu source list.
 */
class MenuCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Footer',
            'slug' => 'footer-menu',
            'content' => '[{"label":"Home","slug":"/"}]',
        ], $overrides);
    }

    public function test_new_menu_form_renders(): void
    {
        // Regression: get_post_list()/get_pages_list() ordered by an unqualified
        // `id` over a join where both tables have an `id` column -> 500 on SQLite.
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/menus/new')
            ->assertStatus(200);
    }

    public function test_admin_can_create_a_menu(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/agentic-cms-laravel-admin/menus/new', $this->payload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('cpanel_menu_list'));

        $translation = MenuTranslation::where('title', 'Footer')->first();
        $this->assertNotNull($translation);
        $this->assertSame('en', $translation->locale);
    }

    public function test_admin_can_update_a_menu(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/menus/new', $this->payload());
        $translation = MenuTranslation::where('title', 'Footer')->firstOrFail();

        $response = $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/menus/'.$translation->menu_id.'/update', $this->payload([
                'content' => '[{"label":"Changed","slug":"/x"}]',
            ]));

        $response->assertSessionHasNoErrors();
        $this->assertStringContainsString('Changed', MenuTranslation::where('title', 'Footer')->firstOrFail()->content);
    }

    public function test_admin_can_delete_a_menu(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/menus/new', $this->payload());
        $translation = MenuTranslation::where('title', 'Footer')->firstOrFail();

        $this->actingAs($this->admin)
            ->delete('/agentic-cms-laravel-admin/menus/'.$translation->menu_id.'/delete')
            ->assertOk();

        $this->assertSame(0, MenuTranslation::where('title', 'Footer')->count());
    }

    public function test_validation_blocks_invalid_menu(): void
    {
        $response = $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/menus/new')
            ->post('/agentic-cms-laravel-admin/menus/new', ['title' => '']);

        $response->assertSessionHasErrors(['title', 'slug', 'content']);
    }

    public function test_user_with_panel_access_but_no_menu_permission_is_blocked(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoMenus',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_menus' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/menus')->assertStatus(401);
    }
}
