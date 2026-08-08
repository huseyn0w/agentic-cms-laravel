<?php

namespace Tests\Feature\Plugins;

use App\Http\Models\CPanel\Plugin;
use App\Http\Models\User;
use App\Support\Content\ContentTypeRegistry;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * End-to-end for the content-type framework via the Projects plugin: enabling
 * the plugin surfaces its content type (registry + generic CRUD + sidebar prop);
 * disabling it hides it. Proves plugin discovery → registration → CRUD.
 */
class ProjectsPluginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('username', 'admin')->firstOrFail();
    }

    private function enable(bool $enabled): void
    {
        Plugin::updateOrCreate(['slug' => 'projects'], ['enabled' => $enabled]);
    }

    public function test_the_projects_table_exists_from_the_plugin_migration(): void
    {
        $this->assertTrue(Schema::hasTable('projects'));
    }

    public function test_enabled_plugin_registers_its_content_type(): void
    {
        $this->enable(true);

        $type = app(ContentTypeRegistry::class)->get('projects');

        $this->assertNotNull($type);
        $this->assertSame('projects', $type->slug);
        $this->assertSame('Projects', $type->label('en'));
    }

    public function test_disabled_plugin_hides_its_content_type(): void
    {
        $this->enable(false);

        $this->assertNull(app(ContentTypeRegistry::class)->get('projects'));

        $this->actingAs($this->admin())
            ->get(route('cpanel_content_index', 'projects'))
            ->assertNotFound();
    }

    public function test_enabled_plugin_content_crud_works_end_to_end(): void
    {
        $this->enable(true);

        // Generic list renders for the plugin type.
        $this->actingAs($this->admin())
            ->get(route('cpanel_content_index', 'projects'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/content/Index')
                ->where('type.slug', 'projects'));

        // Create a project through the generic store.
        $this->actingAs($this->admin())
            ->post(route('cpanel_content_store', 'projects'), [
                'title' => 'Shopify Editions',
                'category' => 'Shopify',
                'external_url' => 'https://www.shopify.com/editions',
                'status' => 'published',
            ])
            ->assertRedirect(route('cpanel_content_index', 'projects'));

        $this->assertDatabaseHas('projects', [
            'title' => 'Shopify Editions',
            'category' => 'Shopify',
        ]);
    }

    public function test_enabled_content_type_is_shared_to_the_sidebar(): void
    {
        $this->enable(true);

        $this->actingAs($this->admin())
            ->get(route('cpanel_content_index', 'projects'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('contentTypes', fn ($types) => collect($types)->contains('slug', 'projects')));
    }
}
