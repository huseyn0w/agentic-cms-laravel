<?php

namespace Tests\Feature\CPanel;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\PageTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PageListInertiaRenderTest extends TestCase
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

    private function createPage(string $title, string $slug): int
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/pages/new', [
            'title' => $title,
            'slug' => $slug,
            'author_id' => (string) $this->admin->id,
            'content' => 'body',
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'template' => 'default',
            'status' => 1,
        ]);

        return PageTranslation::where('slug', $slug)->firstOrFail()->page_id;
    }

    public function test_list_renders_inertia_component_with_shaped_rows_and_trashed_false(): void
    {
        $this->createPage('Visible page', 'visible-page');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/pages')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/pages/List')
                ->where('trashed', false)
                ->where('pages_list.data', function ($rows) {
                    $row = collect($rows)->firstWhere('title', 'Visible page');

                    return $row !== null
                        && array_key_exists('id', $row)
                        && array_key_exists('author', $row)
                        && array_key_exists('created_at', $row)
                        && array_key_exists('status', $row);
                }));
    }

    public function test_trashed_list_renders_the_same_component_with_trashed_true(): void
    {
        $pageId = $this->createPage('Trash me', 'trash-me-page');
        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/pages/'.$pageId.'/delete');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/pages/trashed')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/pages/List')
                ->where('trashed', true)
                ->where('pages_list.data', fn ($rows) => collect($rows)->contains('title', 'Trash me')));
    }

    public function test_new_form_renders_inertia_component_with_options(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/pages/new')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/pages/Form')
                ->where('entity', null)
                ->has('templates')
                ->has('authors')
                ->has('categories_list'));
    }

    public function test_edit_form_renders_inertia_component_with_entity_and_decoded_custom_fields(): void
    {
        $pageId = $this->createPage('Editable', 'editable-page');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/pages/'.$pageId.'/en')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/pages/Form')
                ->where('entity.title', 'Editable')
                ->where('entity.slug', 'editable-page')
                ->where('entity.template', 'default')
                // custom_fields is decoded back to an (here empty) associative array.
                ->where('entity.custom_fields', [])
                ->has('templates'));
    }
}
