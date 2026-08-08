<?php

namespace Tests\Feature\Plugins;

use App\Http\Models\CPanel\Plugin;
use App\Http\Models\User;
use App\Support\Content\ContentTypeRegistry;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The Experience (resume) plugin rides the same content-type framework: its
 * table ships via the plugin migration, and enabling it surfaces the type for
 * generic CRUD.
 */
class ExperiencePluginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_the_experiences_table_ships_with_the_plugin(): void
    {
        $this->assertTrue(Schema::hasTable('experiences'));
    }

    public function test_enabled_plugin_creates_a_resume_entry_via_generic_crud(): void
    {
        Plugin::updateOrCreate(['slug' => 'experience'], ['enabled' => true]);

        $this->assertNotNull(app(ContentTypeRegistry::class)->get('experience'));

        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('cpanel_content_store', 'experience'), [
                'company' => 'Zalando',
                'position' => 'Senior Software Engineer',
                'period' => '2021 — Present',
            ])
            ->assertRedirect(route('cpanel_content_index', 'experience'));

        $this->assertDatabaseHas('experiences', [
            'company' => 'Zalando',
            'position' => 'Senior Software Engineer',
        ]);
    }

    public function test_disabled_by_default_hides_the_type(): void
    {
        // No plugin row / disabled → not registered.
        $this->assertNull(app(ContentTypeRegistry::class)->get('experience'));
    }
}
