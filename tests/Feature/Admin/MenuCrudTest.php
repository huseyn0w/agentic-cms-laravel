<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\MenuTranslation;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
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
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    public function test_menus_list_renders_inertia_with_shaped_rows(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/menus/new', $this->payload());

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/menus')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/menus/List')
                ->has('menus_list.data.0.id')
                ->has('menus_list.data.0.title'));
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
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/menus/Form')
                ->where('entity', null)
                ->has('terms_list.pages')
                ->has('terms_list.posts')
                ->has('terms_list.categories'));
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

        // Row delete now redirects back (Inertia router.delete) instead of the
        // legacy jQuery-AJAX 'OK' echo.
        $this->actingAs($this->admin)
            ->delete('/agentic-cms-laravel-admin/menus/'.$translation->menu_id.'/delete')
            ->assertRedirect();

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
