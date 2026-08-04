<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\Page;
use App\Http\Models\PageTranslation;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Full admin CRUD round-trip for pages (translatable, content lives in
 * `page_translations`). The PageObserver json-encodes custom_fields and
 * sanitises content on the way in.
 */
class PageCrudTest extends TestCase
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
            'title' => 'About Page',
            'slug' => 'about-page',
            'author_id' => (string) $this->admin->id,
            'content' => 'page body',
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'template' => 'default',
            'status' => 1,
        ], $overrides);
    }

    public function test_admin_can_create_a_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/agentic-cms-laravel-admin/pages/new', $this->payload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $translation = PageTranslation::where('slug', 'about-page')->first();
        $this->assertNotNull($translation);
        $this->assertSame('About Page', $translation->title);
        $this->assertSame('en', $translation->locale);
    }

    public function test_admin_can_update_a_page(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/pages/new', $this->payload());
        $translation = PageTranslation::where('slug', 'about-page')->firstOrFail();

        $response = $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/pages/'.$translation->page_id.'/update', $this->payload([
                'content' => 'updated page body',
            ]));

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $fresh = PageTranslation::where('slug', 'about-page')->firstOrFail();
        $this->assertStringContainsString('updated page body', $fresh->content);
    }

    public function test_admin_can_delete_a_page(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/pages/new', $this->payload());
        $translation = PageTranslation::where('slug', 'about-page')->firstOrFail();

        $this->actingAs($this->admin)
            ->delete('/agentic-cms-laravel-admin/pages/'.$translation->page_id.'/delete')
            ->assertOk();

        // Delete is now a soft-delete (parity with posts): the page is hidden
        // from normal listings but the row + translation are kept for restore.
        $this->assertNull(Page::find($translation->page_id), 'Page should be soft deleted.');
        $this->assertNotNull(Page::withTrashed()->find($translation->page_id), 'Soft deleted page row should remain.');
        $this->assertSame(1, PageTranslation::where('slug', 'about-page')->count());
    }

    public function test_admin_can_create_a_page_with_a_long_title_and_slug(): void
    {
        $longTitle = str_repeat('a', 120);
        $longSlug = str_repeat('b', 120);

        $response = $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/pages/new')
            ->post('/agentic-cms-laravel-admin/pages/new', $this->payload([
                'title' => $longTitle,
                'slug' => $longSlug,
            ]));

        $response->assertSessionHasNoErrors();
        $this->assertNotNull(
            PageTranslation::where('slug', $longSlug)->first(),
            'A 120-char title/slug must be accepted (was capped at max:20).'
        );
    }

    public function test_create_accepts_integer_author_id_as_the_inertia_form_sends_it(): void
    {
        // The React form submits JSON (Inertia), so author_id arrives as an
        // integer — not the form-encoded string the old Blade form sent. The
        // rule must accept it (regression guard: it was `string`, which 422'd
        // every real create/update through the UI).
        $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/pages/new')
            ->postJson('/agentic-cms-laravel-admin/pages/new', $this->payload([
                'slug' => 'int-author-page',
                'author_id' => $this->admin->id, // integer, not (string)
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNotNull(PageTranslation::where('slug', 'int-author-page')->first());
    }

    public function test_custom_fields_round_trip_through_the_observer(): void
    {
        // The React builder sends custom_fields as an associative structure;
        // PageObserver json-encodes it and the theme reads it via get_field().
        $customFields = [
            'headline' => ['type' => 'text', 'admin_label' => 'Headline', 'value' => 'Welcome'],
            'cta' => ['type' => 'link', 'admin_label' => 'CTA', 'value' => [
                'label' => 'Go', 'url' => 'https://example.com', 'target' => '1',
            ]],
        ];

        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/pages/new', $this->payload([
            'slug' => 'cf-page',
            'custom_fields' => $customFields,
        ]))->assertSessionHasNoErrors();

        $translation = PageTranslation::where('slug', 'cf-page')->firstOrFail();
        $stored = json_decode($translation->custom_fields, true);

        $this->assertSame('Welcome', $stored['headline']['value']);
        $this->assertSame('text', $stored['headline']['type']);
        $this->assertSame('https://example.com', $stored['cta']['value']['url']);
        $this->assertSame('1', $stored['cta']['value']['target']);
    }

    public function test_repeater_custom_field_nested_structure_round_trips(): void
    {
        // A repeater stores rows of items; the theme reads it as
        // get_field('slides', ...) -> { 'row-0': { title: {...} }, ... }.
        $repeater = [
            'slides' => [
                'type' => 'repeater',
                'admin_label' => 'Slides',
                'value' => [
                    'row-0' => ['title' => ['type' => 'text', 'admin_label' => 'Title', 'value' => 'First']],
                    'row-1' => ['title' => ['type' => 'text', 'admin_label' => 'Title', 'value' => 'Second']],
                ],
            ],
        ];

        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/pages/new', $this->payload([
            'slug' => 'repeater-page',
            'custom_fields' => $repeater,
        ]))->assertSessionHasNoErrors();

        $stored = json_decode(
            PageTranslation::where('slug', 'repeater-page')->firstOrFail()->custom_fields,
            true
        );

        $this->assertSame('repeater', $stored['slides']['type']);
        $this->assertSame('First', $stored['slides']['value']['row-0']['title']['value']);
        $this->assertSame('Second', $stored['slides']['value']['row-1']['title']['value']);
    }

    public function test_validation_blocks_invalid_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->from('/agentic-cms-laravel-admin/pages/new')
            ->post('/agentic-cms-laravel-admin/pages/new', ['title' => '']);

        $response->assertSessionHasErrors(['title', 'slug', 'author_id', 'template']);
    }

    public function test_user_with_panel_access_but_no_page_permission_is_blocked(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoPages',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_pages' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/pages')->assertStatus(401);
    }
}
