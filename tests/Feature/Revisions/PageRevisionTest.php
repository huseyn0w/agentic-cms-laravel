<?php

namespace Tests\Feature\Revisions;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\PageTranslation;
use App\Http\Models\Revision;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pages get the same immutable pre-update snapshots as posts.
 */
class PageRevisionTest extends TestCase
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
            'content' => 'original page body',
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'template' => 'default',
            'status' => 1,
        ], $overrides);
    }

    public function test_creating_a_page_takes_no_revision(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/pages/new', $this->payload());

        $this->assertSame(0, Revision::count(), 'Creating a page must not snapshot.');
    }

    public function test_updating_a_page_snapshots_the_previous_translation(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/pages/new', $this->payload());
        $translation = PageTranslation::where('slug', 'about-page')->firstOrFail();

        $this->actingAs($this->admin)->put(
            '/agentic-cms-laravel-admin/pages/'.$translation->page_id.'/update',
            $this->payload(['content' => 'updated page body'])
        );

        $this->assertSame(1, Revision::count(), 'One revision after the first edit.');

        $revision = Revision::firstOrFail();
        $this->assertSame(PageTranslation::class, $revision->revisionable_type);
        $this->assertSame($translation->id, $revision->revisionable_id);
        $this->assertStringContainsString('original page body', $revision->data['content']);
    }

    public function test_restoring_a_page_revision_reverts_content(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/pages/new', $this->payload());
        $translation = PageTranslation::where('slug', 'about-page')->firstOrFail();

        $this->actingAs($this->admin)->put(
            '/agentic-cms-laravel-admin/pages/'.$translation->page_id.'/update',
            $this->payload(['content' => 'updated page body'])
        );
        $revision = Revision::firstOrFail();

        $this->actingAs($this->admin)->post(
            '/agentic-cms-laravel-admin/pages/'.$translation->page_id.'/revisions/'.$revision->id.'/restore/en'
        )->assertRedirect();

        $fresh = PageTranslation::findOrFail($translation->id);
        $this->assertStringContainsString('original page body', $fresh->content);
        $this->assertSame(2, Revision::count(), 'Restore snapshots the pre-restore state.');
    }
}
