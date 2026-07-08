<?php

namespace Tests\Feature\Front;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FEATURE_MATRIX §16 — per-category RSS/Atom feeds
 * (/blog/category/{slug}/rss.xml and .../atom.xml). Reuses FeedService,
 * published-only, locale-aware. Seeded fixtures: category slug "category-one"
 * (id 1) contains the published post "introducing-the-cms".
 */
class CategoryFeedTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    private function categorySlug(): string
    {
        return DB::table('category_translations')
            ->where('category_id', 1)->where('locale', 'en')->value('slug');
    }

    private function makeCategorisedPost(string $slug, int $categoryId = 1, int $status = 1): void
    {
        $postId = DB::table('posts')->insertGetId([]);
        DB::table('post_translations')->insert([
            'title' => $slug, 'slug' => $slug, 'author_id' => $this->admin->id,
            'post_id' => $postId, 'locale' => 'en', 'status' => $status,
            'preview' => 'prev '.$slug, 'content' => 'content '.$slug,
            'meta_keywords' => 'k', 'meta_description' => 'd',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('category_post')->insert(['category_id' => $categoryId, 'post_id' => $postId]);
    }

    public function test_category_rss_returns_valid_xml_with_its_posts(): void
    {
        $response = $this->get('/blog/category/'.$this->categorySlug().'/rss.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

        $xml = $response->getContent();
        $this->assertStringContainsString('<rss', $xml);
        $this->assertStringContainsString('posts/introducing-the-cms', $xml);
        $this->assertInstanceOf(\SimpleXMLElement::class, simplexml_load_string($xml));
    }

    public function test_category_atom_returns_valid_xml(): void
    {
        $response = $this->get('/blog/category/'.$this->categorySlug().'/atom.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/atom+xml; charset=UTF-8');
        $this->assertInstanceOf(\SimpleXMLElement::class, simplexml_load_string($response->getContent()));
    }

    public function test_unknown_category_feed_is_404(): void
    {
        $this->get('/blog/category/no-such-category/rss.xml')->assertStatus(404);
    }

    public function test_category_feed_excludes_drafts_and_other_categories(): void
    {
        $this->makeCategorisedPost('cat-draft', 1, status: 0);
        $this->makeCategorisedPost('cat-other', 2, status: 1);

        $xml = $this->get('/blog/category/'.$this->categorySlug().'/rss.xml')->getContent();

        $this->assertStringNotContainsString('cat-draft', $xml, 'Draft must not leak into the category feed.');
        $this->assertStringNotContainsString('cat-other', $xml, 'Posts from other categories must not appear.');
    }
}
