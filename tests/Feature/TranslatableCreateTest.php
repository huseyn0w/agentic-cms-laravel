<?php

namespace Tests\Feature;

use App\Http\Models\CategoryTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for the Laravel 11 / Symfony 7 upgrade: AgenticCmsLaravelRequest used
 * to redeclare `protected $locale`, which fatally collides with Symfony's typed
 * `?string $locale`, breaking EVERY translatable create/update form (categories,
 * posts, pages, menus). This exercises the shared path end-to-end.
 */
class TranslatableCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_category_with_translation(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->post('/agentic-cms-laravel-admin/categories/new', [
            'title' => 'QA Category',
            'slug' => 'qa-category',
            'description' => 'desc',
            'meta_description' => 'md',
            'meta_keywords' => 'mk',
            'parent_category_id' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $translation = CategoryTranslation::where('slug', 'qa-category')->first();
        $this->assertNotNull($translation, 'Category translation was not persisted.');
        $this->assertSame('QA Category', $translation->title);
        $this->assertSame('en', $translation->locale);
    }
}
