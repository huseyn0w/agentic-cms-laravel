<?php

namespace Tests\Feature\CPanel;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\PostTranslation;
use App\Http\Models\Revision;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PostRevisionsInertiaRenderTest extends TestCase
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
     * Create a post, then edit it once so the updating observer snapshots the
     * pre-edit state into a revision. Returns [postId, revisionId].
     *
     * @return array{0: int, 1: int}
     */
    private function createPostWithRevision(string $slug): array
    {
        $payload = [
            'title' => 'Original title',
            'slug' => $slug,
            'content' => 'original body',
            'preview' => 'p',
            'author_id' => $this->admin->id,
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'category' => [1],
            'status' => 1,
        ];
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $payload);
        $postId = PostTranslation::where('slug', $slug)->firstOrFail()->post_id;

        $this->actingAs($this->admin)->put('/agentic-cms-laravel-admin/posts/'.$postId.'/update', array_merge($payload, [
            'title' => 'Updated title',
            'content' => 'updated body',
        ]));

        $revisionId = Revision::orderByDesc('id')->firstOrFail()->id;

        return [$postId, $revisionId];
    }

    public function test_revisions_list_renders_inertia_component_with_shaped_rows(): void
    {
        [$postId] = $this->createPostWithRevision('rev-list');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/posts/'.$postId.'/revisions/en')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/posts/Revisions')
                ->where('entity_id', $postId)
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
        [$postId, $revisionId] = $this->createPostWithRevision('rev-diff');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/posts/'.$postId.'/revisions/'.$revisionId.'/compare/en')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/posts/RevisionDiff')
                ->where('entity_id', $postId)
                ->where('lang', 'en')
                ->where('revision.id', $revisionId)
                ->where('fields', fn ($fields) => collect($fields)->contains(
                    fn ($f) => array_key_exists('field', $f)
                        && array_key_exists('old', $f)
                        && array_key_exists('current', $f)
                        && array_key_exists('changed', $f)
                )));
    }

    public function test_revisions_of_a_trashed_post_are_unreachable(): void
    {
        [$postId] = $this->createPostWithRevision('rev-trashed');
        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/posts/'.$postId.'/delete');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/posts/'.$postId.'/revisions/en')
            ->assertNotFound();
    }
}
