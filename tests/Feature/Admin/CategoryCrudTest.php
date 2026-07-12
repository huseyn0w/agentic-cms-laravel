<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\CategoryTranslation;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Full admin CRUD round-trip for categories (translatable, content lives in
 * `category_translations`). Guarded by the manage_categories alias, which
 * checks the manage_post_categories permission.
 */
class CategoryCrudTest extends TestCase
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
            'title' => 'Travel',
            'slug' => 'travel',
            'description' => 'desc',
            'meta_description' => 'md',
            'meta_keywords' => 'mk',
            'parent_category_id' => '',
        ], $overrides);
    }

    public function test_admin_can_create_a_category(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/agentic-cms-laravel-admin/categories/new', $this->payload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $translation = CategoryTranslation::where('slug', 'travel')->first();
        $this->assertNotNull($translation);
        $this->assertSame('Travel', $translation->title);
        $this->assertSame('en', $translation->locale);
    }

    public function test_admin_can_update_a_category(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/categories/new', $this->payload());
        $translation = CategoryTranslation::where('slug', 'travel')->firstOrFail();

        $response = $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/categories/'.$translation->category_id.'/update', $this->payload([
                'description' => 'updated description',
            ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame('updated description', CategoryTranslation::where('slug', 'travel')->firstOrFail()->description);
    }

    public function test_admin_can_delete_a_category(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/categories/new', $this->payload());
        $translation = CategoryTranslation::where('slug', 'travel')->firstOrFail();

        $this->actingAs($this->admin)
            ->delete('/agentic-cms-laravel-admin/categories/'.$translation->category_id.'/delete')
            ->assertOk();

        $this->assertSame(0, CategoryTranslation::where('slug', 'travel')->count());
    }

    public function test_validation_blocks_invalid_category(): void
    {
        $response = $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/categories/new')
            ->post('/agentic-cms-laravel-admin/categories/new', ['title' => '']);

        $response->assertSessionHasErrors(['title', 'slug']);
    }

    public function test_user_with_panel_access_but_no_category_permission_is_blocked(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoCats',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_post_categories' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/categories')->assertStatus(401);
    }
}
