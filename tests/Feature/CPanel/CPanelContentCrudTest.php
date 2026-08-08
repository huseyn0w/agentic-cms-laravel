<?php

namespace Tests\Feature\CPanel;

use App\Http\Models\ContentRecord;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use App\Support\Content\ContentType;
use App\Support\Content\ContentTypeRegistry;
use App\Support\Content\Field;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The generic content CRUD serves any registered content type by slug, driven by
 * its schema, gated by manage_content. Here a test type is registered directly
 * (the plugin-discovery path is covered by the Projects plugin).
 */
class CPanelContentCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);

        Schema::create('test_items', function ($table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });

        app(ContentTypeRegistry::class)->register(new ContentType(
            slug: 'test-items',
            labels: ['en' => 'Test Items'],
            table: 'test_items',
            fields: [
                new Field('title', 'Title', Field::TEXT, ['required', 'string', 'max:255'], listVisible: true),
                new Field('body', 'Body', Field::RICHTEXT, ['nullable', 'string']),
                new Field('featured', 'Featured', Field::BOOLEAN, ['boolean']),
            ],
        ));
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_items');
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::where('username', 'admin')->firstOrFail();
    }

    private function nonManager(): User
    {
        $role = UserRoles::create([
            'name' => 'role_'.bin2hex(random_bytes(4)),
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_content' => 0]),
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_index_renders_with_the_type_schema(): void
    {
        $this->actingAs($this->admin())
            ->get(route('cpanel_content_index', 'test-items'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/content/Index')
                ->where('type.slug', 'test-items')
                ->where('type.columns', ['title'])
                ->has('records'));
    }

    public function test_unknown_type_is_404(): void
    {
        $this->actingAs($this->admin())
            ->get(route('cpanel_content_index', 'nope'))
            ->assertNotFound();
    }

    public function test_requires_manage_content(): void
    {
        $this->actingAs($this->nonManager())
            ->get(route('cpanel_content_index', 'test-items'))
            ->assertStatus(401);
    }

    public function test_store_creates_a_record_from_the_schema(): void
    {
        $this->actingAs($this->admin())
            ->post(route('cpanel_content_store', 'test-items'), [
                'title' => 'Hello',
                'body' => '<p>World</p>',
                'featured' => true,
            ])
            ->assertRedirect(route('cpanel_content_index', 'test-items'));

        $this->assertDatabaseHas('test_items', ['title' => 'Hello', 'featured' => true]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin())
            ->post(route('cpanel_content_store', 'test-items'), ['title' => ''])
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('test_items', 0);
    }

    public function test_edit_renders_the_record(): void
    {
        $id = ContentRecord::forTable('test_items')
            ->newQuery()->insertGetId(['title' => 'Edit me', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin())
            ->get(route('cpanel_content_edit', ['type' => 'test-items', 'id' => $id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/content/Form')
                ->where('record.title', 'Edit me'));
    }

    public function test_update_changes_a_record(): void
    {
        $id = ContentRecord::forTable('test_items')
            ->newQuery()->insertGetId(['title' => 'Old', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin())
            ->put(route('cpanel_content_update', ['type' => 'test-items', 'id' => $id]), ['title' => 'New'])
            ->assertRedirect();

        $this->assertDatabaseHas('test_items', ['id' => $id, 'title' => 'New']);
    }

    public function test_destroy_deletes_a_record(): void
    {
        $id = ContentRecord::forTable('test_items')
            ->newQuery()->insertGetId(['title' => 'Gone', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin())
            ->delete(route('cpanel_content_destroy', ['type' => 'test-items', 'id' => $id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('test_items', ['id' => $id]);
    }
}
