<?php

namespace Tests\Feature\Front;

use App\Http\Models\PageTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Admin-only page preview: GET /agentic-cms-laravel-admin/pages/{id}/preview
 * renders the public page for a draft the public site hides (pages require
 * status = 1 to be publicly readable). Gated behind auth + manage_pages.
 */
class PagePreviewTest extends TestCase
{
    use RefreshDatabase;

    private int $pageId;

    private string $slug;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);

        // A standard-template page (template lives on page_translations), forced
        // to draft so the public site hides it.
        $translation = PageTranslation::where('locale', 'en')
            ->whereNotIn('template', ['home', 'contacts'])
            ->firstOrFail();
        $translation->update(['status' => 0]);

        $this->pageId = $translation->page_id;
        $this->slug = $translation->slug;
    }

    public function test_admin_can_preview_a_draft_page(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get("/agentic-cms-laravel-admin/pages/{$this->pageId}/preview")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('public/Page')
                ->where('preview', true));
    }

    public function test_public_route_still_hides_the_draft_page(): void
    {
        $this->get('/'.$this->slug)->assertNotFound();
    }

    public function test_preview_requires_authentication(): void
    {
        $this->get("/agentic-cms-laravel-admin/pages/{$this->pageId}/preview")
            ->assertRedirect('/login');
    }

    public function test_preview_denied_without_manage_pages_permission(): void
    {
        $user = User::factory()->create(['role_id' => 2]);

        $this->actingAs($user)
            ->get("/agentic-cms-laravel-admin/pages/{$this->pageId}/preview")
            ->assertForbidden();
    }

    public function test_preview_of_unknown_page_is_not_found(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/agentic-cms-laravel-admin/pages/999999/preview')
            ->assertNotFound();
    }
}
