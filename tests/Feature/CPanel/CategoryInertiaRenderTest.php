<?php

namespace Tests\Feature\CPanel;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\CategoryTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CategoryInertiaRenderTest extends TestCase
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

    public function test_list_renders_inertia_component_with_pagination(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/categories')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/categories/List')
                ->has('categories_list.data'));
    }

    public function test_list_resolves_parent_title_to_the_parent_category_name(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/categories/new', [
            'title' => 'Travel',
            'slug' => 'travel',
            'description' => 'desc',
            'meta_description' => 'md',
            'meta_keywords' => 'mk',
            'parent_category_id' => '',
        ]);
        $parent = CategoryTranslation::where('slug', 'travel')->firstOrFail();

        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/categories/new', [
            'title' => 'City Breaks',
            'slug' => 'city-breaks',
            'description' => 'desc',
            'meta_description' => 'md',
            'meta_keywords' => 'mk',
            'parent_category_id' => $parent->category_id,
        ]);

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/categories')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/categories/List')
                ->where('categories_list.data', function ($rows) {
                    $child = collect($rows)->firstWhere('title', 'City Breaks');

                    return $child !== null && $child['parent_title'] === 'Travel';
                }));
    }

    public function test_new_form_renders_inertia_component(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/categories/new')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/categories/Form')
                ->where('entity', null)
                ->has('parent_options'));
    }

    public function test_edit_form_renders_inertia_component_with_entity(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/categories/new', [
            'title' => 'City Breaks',
            'slug' => 'city-breaks',
            'description' => 'desc',
            'meta_description' => 'md',
            'meta_keywords' => 'mk',
            'parent_category_id' => '',
        ]);

        $id = CategoryTranslation::where('locale', 'en')->where('slug', 'city-breaks')->value('category_id');

        $this->actingAs($this->admin)
            ->get("/agentic-cms-laravel-admin/categories/{$id}/en")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/categories/Form')
                ->where('entity.title', 'City Breaks')
                ->where('entity.slug', 'city-breaks')
                ->has('parent_options')
                ->has('translation_links'));
    }
}
