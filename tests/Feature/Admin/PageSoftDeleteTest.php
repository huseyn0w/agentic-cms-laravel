<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\Page;
use App\Http\Models\PageTranslation;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Pages mirror the posts soft-delete/trash/restore/permanent-delete flow
 * (FEATURE_MATRIX §1). Soft-deleting a page hides it from normal listings but
 * keeps the row (and its translations) for restore.
 */
class PageSoftDeleteTest extends TestCase
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
            'title' => 'Trashable Page',
            'slug' => 'trashable-page',
            'author_id' => (string) $this->admin->id,
            'content' => 'page body',
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'template' => 'default',
            'status' => 1,
        ], $overrides);
    }

    private function createPage(): int
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/pages/new', $this->payload());

        return PageTranslation::where('slug', 'trashable-page')->firstOrFail()->page_id;
    }

    public function test_admin_can_soft_delete_a_page(): void
    {
        $pageId = $this->createPage();

        $this->actingAs($this->admin)
            ->delete('/agentic-cms-laravel-admin/pages/'.$pageId.'/delete')
            ->assertOk();

        $this->assertNull(Page::find($pageId), 'Page should be soft deleted.');
        $this->assertNotNull(Page::withTrashed()->find($pageId), 'Soft deleted page row should remain.');
        // Translations are preserved so the page can be restored intact.
        $this->assertSame(1, PageTranslation::where('page_id', $pageId)->count());
    }

    public function test_trashed_pages_listing_shows_only_soft_deleted_pages(): void
    {
        $deletedId = $this->createPage();
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/pages/new', $this->payload([
            'title' => 'Live Page', 'slug' => 'live-page',
        ]));
        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/pages/'.$deletedId.'/delete');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/pages/trashed')
            ->assertOk()
            ->assertSee('Trashable Page')
            ->assertDontSee('Live Page');
    }

    public function test_trashed_tab_renders_the_inertia_list_in_trashed_mode(): void
    {
        // The trashed list is now an Inertia page; its restore/destroy
        // affordances are React buttons (covered by the Vitest Pages/List
        // suite). Here we assert the server renders the list component in
        // trashed mode with the soft-deleted row present.
        $pageId = $this->createPage();
        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/pages/'.$pageId.'/delete');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/pages/trashed')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/pages/List')
                ->where('trashed', true)
                ->where('pages_list.data', fn ($rows) => collect($rows)->contains('title', 'Trashable Page')));
    }

    public function test_admin_can_restore_a_soft_deleted_page(): void
    {
        $pageId = $this->createPage();
        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/pages/'.$pageId.'/delete');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/pages/'.$pageId.'/restore')
            ->assertRedirect();

        $this->assertNotNull(Page::find($pageId), 'Page should be restored (no longer trashed).');
    }

    public function test_admin_can_permanently_destroy_a_trashed_page(): void
    {
        $pageId = $this->createPage();
        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/pages/'.$pageId.'/delete');

        $this->actingAs($this->admin)
            ->delete('/agentic-cms-laravel-admin/pages/'.$pageId.'/destroy')
            ->assertOk();

        $this->assertNull(Page::withTrashed()->find($pageId), 'Page row should be gone.');
        // The FK cascade removes the translations too.
        $this->assertSame(0, PageTranslation::where('page_id', $pageId)->count());
    }

    public function test_destroy_endpoint_cannot_permanently_delete_a_live_page(): void
    {
        // A live (non-trashed) page must survive a direct hit on the destroy
        // endpoint — permanent-delete is restricted to already-trashed rows.
        $pageId = $this->createPage();

        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/pages/'.$pageId.'/destroy');

        $this->assertNotNull(Page::find($pageId), 'A live page must not be force-deleted via destroy.');
    }

    public function test_user_without_page_permission_cannot_reach_trash_actions(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoPagesTrash',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_pages' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/pages/trashed')->assertStatus(401);
        $this->actingAs($user)->get('/agentic-cms-laravel-admin/pages/1/restore')->assertStatus(401);
        $this->actingAs($user)->delete('/agentic-cms-laravel-admin/pages/1/destroy')->assertStatus(401);
        $this->actingAs($user)->post('/agentic-cms-laravel-admin/pages/multiple', [
            'pages_action' => 'destroy', 'pages' => [1],
        ])->assertStatus(401);
    }

    public function test_soft_deleted_page_is_hidden_from_front_and_sitemap(): void
    {
        // The seeded "contact" page is public + in the sitemap; trashing it must
        // remove it from both (the SoftDeletes scope applies to every front query).
        $contact = PageTranslation::where('slug', 'contact')->firstOrFail();
        $this->get('/sitemap.xml')->assertSee('contact', false);

        Page::findOrFail($contact->page_id)->delete();

        // The front page resolution (not cached) 404s immediately.
        $this->get('/contact')->assertNotFound();

        // The sitemap is cached for an hour (eventually-consistent for ALL content
        // changes, by design); bust that cache to assert the QUERY itself excludes
        // trashed pages — guarding against a future raw-query refactor leaking them.
        Cache::forget('agentic_cms_laravel.sitemap.xml');
        $sitemap = $this->get('/sitemap.xml')->getContent();
        $this->assertStringNotContainsString('/contact', $sitemap, 'Trashed page must drop from the sitemap query.');
    }

    public function test_admin_can_bulk_restore_trashed_pages(): void
    {
        $a = $this->createPage();
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/pages/new', $this->payload([
            'title' => 'Second Page', 'slug' => 'second-page',
        ]));
        $b = PageTranslation::where('slug', 'second-page')->firstOrFail()->page_id;
        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/pages/'.$a.'/delete');
        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/pages/'.$b.'/delete');

        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/pages/multiple', [
            'pages_action' => 'restore',
            'pages' => [$a, $b],
        ])->assertRedirect();

        $this->assertNotNull(Page::find($a));
        $this->assertNotNull(Page::find($b));
    }
}
