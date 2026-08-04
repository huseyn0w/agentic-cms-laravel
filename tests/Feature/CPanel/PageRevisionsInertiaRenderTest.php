<?php

namespace Tests\Feature\CPanel;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\PageTranslation;
use App\Http\Models\Revision;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PageRevisionsInertiaRenderTest extends TestCase
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

    /**
     * Create a page, then edit it once so the updating observer snapshots the
     * pre-edit state into a revision. Returns [pageId, revisionId].
     *
     * @return array{0: int, 1: int}
     */
    private function createPageWithRevision(string $slug): array
    {
        $payload = [
            'title' => 'Original title',
            'slug' => $slug,
            'author_id' => (string) $this->admin->id,
            'content' => 'original body',
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'template' => 'default',
            'status' => 1,
        ];
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/pages/new', $payload);
        $pageId = PageTranslation::where('slug', $slug)->firstOrFail()->page_id;

        $this->actingAs($this->admin)->put('/agentic-cms-laravel-admin/pages/'.$pageId.'/update', array_merge($payload, [
            'title' => 'Updated title',
            'content' => 'updated body',
        ]));

        $revisionId = Revision::orderByDesc('id')->firstOrFail()->id;

        return [$pageId, $revisionId];
    }

    public function test_revisions_list_renders_inertia_component_with_shaped_rows(): void
    {
        [$pageId] = $this->createPageWithRevision('rev-page-list');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/pages/'.$pageId.'/revisions/en')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/pages/Revisions')
                ->where('entity_id', $pageId)
                ->where('lang', 'en')
                ->where('revisions.data', function ($rows) {
                    $row = collect($rows)->first();

                    return $row !== null
                        && array_key_exists('id', $row)
                        && array_key_exists('version', $row)
                        && array_key_exists('author', $row)
                        && array_key_exists('created_at', $row);
                }));
    }

    public function test_revision_diff_renders_inertia_component_with_fields(): void
    {
        [$pageId, $revisionId] = $this->createPageWithRevision('rev-page-diff');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/pages/'.$pageId.'/revisions/'.$revisionId.'/compare/en')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/pages/RevisionDiff')
                ->where('entity_id', $pageId)
                ->where('lang', 'en')
                ->where('revision.id', $revisionId)
                ->where('fields', fn ($fields) => collect($fields)->contains(
                    fn ($f) => array_key_exists('field', $f)
                        && array_key_exists('old', $f)
                        && array_key_exists('current', $f)
                        && array_key_exists('changed', $f)
                )));
    }
}
