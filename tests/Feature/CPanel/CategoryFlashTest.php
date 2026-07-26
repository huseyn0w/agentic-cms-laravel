<?php

namespace Tests\Feature\CPanel;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\CategoryTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Regression coverage for the FlashBanner-never-fires bug: the Categories
 * controllers used to flash `category_added` / `message` keys that
 * HandleInertiaRequests::share() never reads (it only shares
 * flash.status|success|error), so the Task-2 banner stayed silent for every
 * create/update/delete in this slice. Asserts `flash.success` is now a
 * non-empty, real string after each action.
 */
class CategoryFlashTest extends TestCase
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

    public function test_create_flashes_a_non_empty_success_message(): void
    {
        $this->actingAs($this->admin)
            ->post('/agentic-cms-laravel-admin/categories/new', $this->payload());

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/categories')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('flash.success', fn ($success) => is_string($success) && $success !== ''));
    }

    public function test_update_flashes_a_non_empty_success_message(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/categories/new', $this->payload());
        $translation = CategoryTranslation::where('slug', 'travel')->firstOrFail();

        $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/categories/'.$translation->category_id.'/update', $this->payload([
                'description' => 'updated description',
            ]));

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/categories')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('flash.success', fn ($success) => is_string($success) && $success !== ''));
    }

    public function test_bulk_delete_flashes_a_non_empty_success_message(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/categories/new', $this->payload());
        $translation = CategoryTranslation::where('slug', 'travel')->firstOrFail();

        $this->actingAs($this->admin)
            ->delete('/agentic-cms-laravel-admin/categories/multipleDelete', [
                'categories' => [$translation->category_id],
                'categories_action' => 'delete',
            ]);

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/categories')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('flash.success', fn ($success) => is_string($success) && $success !== ''));
    }
}
