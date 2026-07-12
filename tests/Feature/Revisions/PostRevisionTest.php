<?php

namespace Tests\Feature\Revisions;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\PostTranslation;
use App\Http\Models\Revision;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Revisions are immutable snapshots of a post translation taken *before* each
 * admin update. Creating a post takes no snapshot (nothing to preserve); the
 * first edit snapshots the pre-edit state, and so on.
 */
class PostRevisionTest extends TestCase
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

    private function postPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Round Trip Post',
            'slug' => 'round-trip-post',
            'content' => 'original body',
            'preview' => 'original preview',
            'author_id' => $this->admin->id,
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'category' => [1],
            'status' => 1,
        ], $overrides);
    }

    public function test_creating_a_post_takes_no_revision(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());

        $this->assertSame(0, Revision::count(), 'Creating a post must not snapshot.');
    }

    public function test_updating_a_post_snapshots_the_previous_translation(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $translation = PostTranslation::where('slug', 'round-trip-post')->firstOrFail();

        $this->actingAs($this->admin)->put(
            '/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/update',
            $this->postPayload(['content' => 'edited body'])
        );

        $this->assertSame(1, Revision::count(), 'One revision after the first edit.');

        $revision = Revision::firstOrFail();
        $this->assertSame(PostTranslation::class, $revision->revisionable_type);
        $this->assertSame($translation->id, $revision->revisionable_id);
        $this->assertSame($this->admin->id, $revision->user_id);
        // The snapshot preserves the PRE-edit content, not the new value.
        $this->assertStringContainsString('original body', $revision->data['content']);
        $this->assertSame('Round Trip Post', $revision->data['title']);
    }

    public function test_each_edit_appends_a_new_revision(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $translation = PostTranslation::where('slug', 'round-trip-post')->firstOrFail();

        $this->actingAs($this->admin)->put('/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/update', $this->postPayload(['content' => 'edit one']));
        $this->actingAs($this->admin)->put('/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/update', $this->postPayload(['content' => 'edit two']));

        $this->assertSame(2, Revision::count(), 'Each edit appends one revision.');
    }

    public function test_restoring_a_revision_reverts_content_and_snapshots_current(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $translation = PostTranslation::where('slug', 'round-trip-post')->firstOrFail();

        // Edit: snapshots the 'original body' state as the first revision.
        $this->actingAs($this->admin)->put(
            '/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/update',
            $this->postPayload(['content' => 'edited body'])
        );
        $revision = Revision::firstOrFail();
        $this->assertStringContainsString('original body', $revision->data['content']);

        // Restore the original revision.
        $this->actingAs($this->admin)->post(
            '/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/revisions/'.$revision->id.'/restore/en'
        )->assertRedirect();

        // Content reverted to the snapshot.
        $fresh = PostTranslation::findOrFail($translation->id);
        $this->assertStringContainsString('original body', $fresh->content);

        // Restore is itself an edit: the pre-restore ('edited body') state is
        // captured as a new revision, so the restore is undoable.
        $this->assertSame(2, Revision::count(), 'Restore snapshots the pre-restore state.');
        $this->assertStringContainsString('edited body', Revision::orderByDesc('id')->firstOrFail()->data['content']);
    }

    public function test_restore_reverts_content_but_not_authorship(): void
    {
        $other = User::factory()->create(['role_id' => 1]);

        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $translation = PostTranslation::where('slug', 'round-trip-post')->firstOrFail();

        // Edit reassigns the author and changes content (snapshots the original,
        // authored by admin).
        $this->actingAs($this->admin)->put('/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/update', $this->postPayload([
            'author_id' => $other->id, 'content' => 'edited body',
        ]));
        $revision = Revision::firstOrFail();
        $this->assertSame($this->admin->id, (int) $revision->data['author_id']);

        $this->actingAs($this->admin)->post(
            '/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/revisions/'.$revision->id.'/restore/en'
        )->assertRedirect();

        $fresh = PostTranslation::findOrFail($translation->id);
        // Editorial content reverts...
        $this->assertStringContainsString('original body', $fresh->content);
        // ...but authorship is NOT silently reverted (anti-spoof: restore must
        // not reassign author_id from an old snapshot).
        $this->assertSame($other->id, (int) $fresh->author_id);
    }

    public function test_restore_with_conflicting_slug_does_not_500(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $a = PostTranslation::where('slug', 'round-trip-post')->firstOrFail();

        // Rename A (snapshots its original title/slug), freeing the old slug.
        $this->actingAs($this->admin)->put('/agentic-cms-laravel-admin/posts/'.$a->post_id.'/update', $this->postPayload([
            'title' => 'Renamed Post', 'slug' => 'renamed-post', 'content' => 'edited body',
        ]));
        $revision = Revision::firstOrFail();

        // B now takes A's original title/slug.
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload([
            'content' => 'b body',
        ]));

        // Restoring A's revision would collide on unique(locale,title,slug).
        $response = $this->actingAs($this->admin)->post(
            '/agentic-cms-laravel-admin/posts/'.$a->post_id.'/revisions/'.$revision->id.'/restore/en'
        );

        $this->assertNotSame(500, $response->status(), 'Restore must fail gracefully, not 500.');
    }

    public function test_revisions_are_unreachable_for_a_trashed_post(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $translation = PostTranslation::where('slug', 'round-trip-post')->firstOrFail();
        $this->actingAs($this->admin)->delete('/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/delete');

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/revisions/en')
            ->assertNotFound();
    }

    public function test_user_without_post_permission_cannot_access_revisions(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoPosts',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_posts' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get('/agentic-cms-laravel-admin/posts/1/revisions/en')
            ->assertStatus(401);
    }

    public function test_revisions_list_page_renders(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $translation = PostTranslation::where('slug', 'round-trip-post')->firstOrFail();
        $this->actingAs($this->admin)->put('/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/update', $this->postPayload(['content' => 'edited body']));

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/revisions/en')
            ->assertOk()
            ->assertSee('v1')
            ->assertSee($this->admin->username);
    }

    public function test_revision_diff_page_renders_and_marks_changes(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $translation = PostTranslation::where('slug', 'round-trip-post')->firstOrFail();
        $this->actingAs($this->admin)->put('/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/update', $this->postPayload(['content' => 'edited body']));
        $revision = Revision::firstOrFail();

        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/posts/'.$translation->post_id.'/revisions/'.$revision->id.'/compare/en')
            ->assertOk()
            ->assertSee('original body')
            ->assertSee('edited body');
    }

    public function test_cannot_restore_a_revision_belonging_to_another_post(): void
    {
        // Post A with one revision.
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $a = PostTranslation::where('slug', 'round-trip-post')->firstOrFail();
        $this->actingAs($this->admin)->put('/agentic-cms-laravel-admin/posts/'.$a->post_id.'/update', $this->postPayload(['content' => 'a edited']));
        $revisionOfA = Revision::firstOrFail();

        // Post B with distinct content so a clobber is detectable.
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload([
            'title' => 'Second Post', 'slug' => 'second-post', 'content' => 'b untouched body',
        ]));
        $b = PostTranslation::where('slug', 'second-post')->firstOrFail();

        // Try to restore A's revision under B's id -> must 404 and not touch B.
        $this->actingAs($this->admin)->post(
            '/agentic-cms-laravel-admin/posts/'.$b->post_id.'/revisions/'.$revisionOfA->id.'/restore/en'
        )->assertNotFound();

        $freshB = PostTranslation::findOrFail($b->id);
        $this->assertStringContainsString('b untouched body', $freshB->content, 'B must be untouched.');
    }

    public function test_cannot_compare_a_revision_belonging_to_another_post(): void
    {
        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload());
        $a = PostTranslation::where('slug', 'round-trip-post')->firstOrFail();
        $this->actingAs($this->admin)->put('/agentic-cms-laravel-admin/posts/'.$a->post_id.'/update', $this->postPayload(['content' => 'a edited']));
        $revisionOfA = Revision::firstOrFail();

        $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/posts/new', $this->postPayload([
            'title' => 'Second Post', 'slug' => 'second-post',
        ]));
        $b = PostTranslation::where('slug', 'second-post')->firstOrFail();

        // The compare endpoint must be scoped like restore (no cross-post leak).
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/posts/'.$b->post_id.'/revisions/'.$revisionOfA->id.'/compare/en')
            ->assertNotFound();
    }
}
