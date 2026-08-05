<?php

namespace Tests\Feature\Tags;

use App\Http\Models\Post;
use App\Repositories\TagRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Parity §2: a browseable public tag archive at /tag/{slug} listing the posts
 * carrying that tag.
 */
class TagArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
        app()->setLocale('en');
    }

    public function test_tag_archive_lists_its_posts(): void
    {
        $post = Post::findOrFail(1);
        app(TagRepository::class)->syncToPost($post, ['Laravel']);

        $response = $this->get('/tag/laravel');

        $response->assertStatus(200);
        $response->assertSee('Laravel', false);
        $response->assertSee($post->title, false);
    }

    public function test_unknown_tag_returns_404(): void
    {
        $this->get('/tag/does-not-exist')->assertNotFound();
    }

    public function test_post_detail_shows_its_tags_linking_to_the_archive(): void
    {
        $post = Post::findOrFail(1);
        app(TagRepository::class)->syncToPost($post, ['Laravel']);

        // The post detail is Inertia now; its tags ride in the props (the React
        // page links each to /tag/{slug}), not in Blade markup.
        $this->get('/posts/'.$post->slug)->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('public/Post')
                ->where('post.tags', fn ($tags) => collect($tags)->contains(
                    fn ($tag) => $tag['name'] === 'Laravel' && str_contains($tag['url'], 'tag/laravel')
                ))
        );
    }
}
