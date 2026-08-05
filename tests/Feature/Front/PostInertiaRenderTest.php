<?php

namespace Tests\Feature\Front;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Blog post detail on Inertia. Body is React; the crawler-critical head
 * (Article + BreadcrumbList JSON-LD) stays server-rendered by Blade, so both
 * are checked: the component + shaped props, and the JSON-LD in raw HTML.
 */
class PostInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/posts/introducing-the-cms';

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_post_renders_the_inertia_component_with_shaped_props(): void
    {
        $this->get($this->url)->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('public/Post')
                ->has('shell.menu')
                ->has('post.content')
                ->has('post.author.name')
                ->has('post.tags')
                ->has('related')
                ->has('comments.data')
                ->has('commentForm.postUrl')
                ->where('commentForm.canComment', false)
        );
    }

    public function test_post_ships_article_json_ld_in_the_head(): void
    {
        $html = $this->get($this->url)->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<title>'));
        // seo-meta emits BlogPosting + BreadcrumbList for the posts route
        // (json_ld() pretty-prints, hence the space after the colon).
        $this->assertStringContainsString('"@type": "BlogPosting"', $html);
        $this->assertStringContainsString('"@type": "BreadcrumbList"', $html);
    }

    public function test_missing_post_still_404s(): void
    {
        $this->get('/posts/this-post-does-not-exist')->assertStatus(404);
    }
}
