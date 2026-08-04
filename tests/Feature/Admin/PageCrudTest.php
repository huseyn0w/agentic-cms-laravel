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
