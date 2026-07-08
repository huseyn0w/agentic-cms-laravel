<?php

namespace Tests\Feature;

use App\Http\Models\CategoryTranslation;
use App\Http\Models\PageTranslation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Phase 7 (SEO/GEO): front-end SEO meta coverage.
 *
 * Recreates the test lost in a merge. Exercises the public-facing output of
 * resources/views/default/partials/seo-meta.blade.php (head meta, Open Graph,
 * JSON-LD) plus the SeoController endpoints (sitemap.xml, robots.txt), and the
 * new per-entity meta_noindex override on pages/categories.
 */
class SeoMetaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_homepage_renders_core_seo_meta(): void
    {
        $html = $this->get('/')->assertStatus(200)->getContent();

        $this->assertStringContainsString('<title>', $html);
        $this->assertStringContainsString('<link rel="canonical"', $html);
        $this->assertStringContainsString('property="og:title"', $html);

        // JSON-LD WebSite + Organization blocks on the homepage (GEO).
        // json_ld() pretty-prints, so match the type without assuming spacing.
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertMatchesRegularExpression('/"@type":\s*"WebSite"/', $html);
        $this->assertMatchesRegularExpression('/"@type":\s*"Organization"/', $html);
    }

    public function test_post_page_renders_article_meta_and_jsonld(): void
    {
        $html = $this->get('/posts/introducing-the-cms')->assertStatus(200)->getContent();

        $this->assertStringContainsString('property="og:type" content="article"', $html);
        $this->assertMatchesRegularExpression('/"@type":\s*"BlogPosting"/', $html);
    }

    public function test_post_page_renders_article_og_meta(): void
    {
        $html = $this->get('/posts/introducing-the-cms')->assertStatus(200)->getContent();

        // §8: articles must expose published time + author for Open Graph.
        $this->assertStringContainsString('property="article:published_time"', $html);
        $this->assertStringContainsString('property="article:author"', $html);
    }

    public function test_sitemap_returns_valid_xml_with_post_slug(): void
    {
        $response = $this->get('/sitemap.xml')->assertStatus(200);

        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();

        // Well-formed XML.
        $this->assertNotFalse(simplexml_load_string($xml), 'Sitemap is not valid XML');

        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString('introducing-the-cms', $xml);
    }

    public function test_robots_txt_is_served(): void
    {
        $this->get('/robots.txt')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('User-agent: *');
    }

    public function test_page_with_noindex_renders_noindex_in_head(): void
    {
        // Flag the "contact" page as noindex and confirm the head honours it.
        PageTranslation::where('slug', 'contact')->where('locale', 'en')
            ->update(['meta_noindex' => true]);
        Cache::flush();

        $html = $this->get('/contact')->assertStatus(200)->getContent();

        $this->assertStringContainsString('noindex', $html);
    }

    public function test_category_with_noindex_renders_noindex_in_head(): void
    {
        CategoryTranslation::where('slug', 'announcements')->where('locale', 'en')
            ->update(['meta_noindex' => true]);
        Cache::flush();

        $html = $this->get('/category/announcements')->assertStatus(200)->getContent();

        $this->assertStringContainsString('noindex', $html);
    }
}
