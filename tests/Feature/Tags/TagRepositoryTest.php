<?php

namespace Tests\Feature\Tags;

use App\Http\Models\Post;
use App\Http\Models\Tag;
use App\Repositories\TagRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The tag repository owns find-or-create-by-name + slug generation and the
 * post sync (the observer/service only delegates to it).
 */
class TagRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        app()->setLocale('en');
    }

    private function repo(): TagRepository
    {
        return app(TagRepository::class);
    }

    public function test_sync_creates_new_tags_with_slugs_and_attaches_them(): void
    {
        // The demo seed attaches tags to post 1; syncToPost is authoritative and
        // replaces them, so the post ends with exactly the synced pair.
        $post = Post::findOrFail(1);

        $this->repo()->syncToPost($post, ['Laravel', 'PHP 8']);

        $this->assertSame(2, $post->tags()->count());
        $this->assertDatabaseHas('tag_translations', ['locale' => 'en', 'name' => 'Laravel', 'slug' => 'laravel']);
        $this->assertDatabaseHas('tag_translations', ['locale' => 'en', 'name' => 'PHP 8', 'slug' => 'php-8']);
    }

    public function test_sync_reuses_existing_tags_instead_of_duplicating(): void
    {
        $post = Post::findOrFail(1);
        // Baseline includes the demo-seeded tags; assert against a delta so this
        // stays a pure "no duplicate on reuse" check independent of the seed.
        $baseline = Tag::count();
        $this->repo()->syncToPost($post, ['Laravel']);
        $afterFirst = Tag::count();

        $this->repo()->syncToPost($post, ['Laravel', 'Testing']);

        $this->assertSame(2, $post->tags()->count());
        // One new tag on the first sync (Laravel), one on the second (Testing);
        // Laravel is reused, not duplicated.
        $this->assertSame($baseline + 1, $afterFirst);
        $this->assertSame($baseline + 2, Tag::count(), 'Laravel must not be duplicated');
    }

    public function test_sync_detaches_removed_tags_and_ignores_blanks(): void
    {
        $post = Post::findOrFail(1);
        $this->repo()->syncToPost($post, ['Laravel', 'PHP']);

        $this->repo()->syncToPost($post, ['Laravel', '  ', '']);

        $this->assertSame(['laravel'], $post->tags()->get()->pluck('slug')->all());
    }
}
