<?php

namespace Tests\Feature\Front;

use App\Http\Models\ContentRecord;
use App\Http\Models\CPanel\Plugin;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Public front-end for content types: an enabled, public plugin type (Projects)
 * renders a generic index + detail from its schema, shows published rows only,
 * and 404s when the plugin is disabled. This is what makes Projects/Experience
 * a Tier-1 (core + data) feature — no fork React required.
 */
class PublicContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    private function enableProjects(bool $enabled = true): void
    {
        Plugin::updateOrCreate(['slug' => 'projects'], ['enabled' => $enabled]);
    }

    private function insertProject(array $attributes): int
    {
        return ContentRecord::forTable('projects')->newQuery()->insertGetId(array_merge([
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    public function test_index_lists_published_rows_only(): void
    {
        $this->enableProjects();
        $this->insertProject(['title' => 'Alpha', 'status' => 'published', 'sort_order' => 2]);
        $this->insertProject(['title' => 'Beta', 'status' => 'published', 'sort_order' => 1]);
        $this->insertProject(['title' => 'Hidden draft', 'status' => 'draft', 'sort_order' => 0]);

        $this->get('/projects')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('public/ContentIndex')
                ->where('slug', 'projects')
                ->where('hasDetail', true)
                ->has('items', 2)
                // sort_order asc: Beta (1) before Alpha (2)
                ->where('items.0.title', 'Beta')
                ->where('items.1.title', 'Alpha'));
    }

    public function test_index_omits_richtext_from_the_listing_payload(): void
    {
        $this->enableProjects();
        $this->insertProject(['title' => 'Alpha', 'status' => 'published', 'content' => '<p>big body</p>']);

        $this->get('/projects')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('items.0.title', 'Alpha')
                ->missing('items.0.content'));
    }

    public function test_detail_renders_a_published_row(): void
    {
        $this->enableProjects();
        $id = $this->insertProject([
            'title' => 'Shopify Editions',
            'content' => '<p>Case study</p>',
            'status' => 'published',
        ]);

        $this->get('/projects/'.$id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('public/ContentDetail')
                ->where('title', 'Shopify Editions')
                ->where('item.content', '<p>Case study</p>'));
    }

    public function test_detail_of_a_draft_row_is_404(): void
    {
        $this->enableProjects();
        $id = $this->insertProject(['title' => 'Draft', 'status' => 'draft']);

        $this->get('/projects/'.$id)->assertNotFound();
    }

    public function test_disabled_plugin_hides_the_public_pages(): void
    {
        $this->enableProjects(false);
        $id = $this->insertProject(['title' => 'Alpha', 'status' => 'published']);

        $this->get('/projects')->assertNotFound();
        $this->get('/projects/'.$id)->assertNotFound();
    }

    public function test_experience_without_a_status_column_shows_every_row(): void
    {
        Plugin::updateOrCreate(['slug' => 'experience'], ['enabled' => true]);
        ContentRecord::forTable('experiences')->newQuery()->insert([
            ['company' => 'Acme', 'position' => 'Engineer', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['company' => 'Globex', 'position' => 'Lead', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->get('/experience')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('public/ContentIndex')
                ->where('slug', 'experience')
                ->has('items', 2));
    }
}
