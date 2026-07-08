<?php

namespace Tests\Feature\Front;

use App\Http\Models\CPanel\CPanelSeoSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7 (SEO/GEO): public head meta, sitemap.xml, robots.txt and llms.txt.
 *
 * Seeded fixtures: page "/" (home) + "contact", post "introducing-the-cms",
 * category "announcements".
 */
class SeoMetaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    // --- Head meta --------------------------------------------------------

    public function test_home_has_core_seo_meta(): void
    {
        $html = $this->get('/')->assertStatus(200)->getContent();

        $this->assertStringContainsString('<title>', $html);
        $this->assertStringContainsString('rel="canonical"', $html);
        $this->assertStringContainsString('property="og:title"', $html);
        $this->assertStringContainsString('name="twitter:card"', $html);
        $this->assertStringContainsString('hreflang="x-default"', $html);
        // WebSite + Organization JSON-LD on the homepage.
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"WebSite"', $html);
        $this->assertStringContainsString('"SearchAction"', $html);
    }

    public function test_post_has_article_jsonld_and_og_tags(): void
    {
        $html = $this->get('/posts/introducing-the-cms')->assertStatus(200)->getContent();

        $this->assertStringContainsString('property="og:title"', $html);
        $this->assertStringContainsString('property="og:type" content="article"', $html);
        $this->assertStringContainsString('rel="canonical"', $html);
        $this->assertStringContainsString('"BlogPosting"', $html);
        $this->assertStringContainsString('"BreadcrumbList"', $html);
    }

    public function test_legacy_addthis_script_is_gone_from_posts(): void
    {
        $html = $this->get('/posts/introducing-the-cms')->getContent();
        $this->assertStringNotContainsString('addthis', $html);
    }

    public function test_no_analytics_script_by_default(): void
    {
        $html = $this->get('/')->getContent();
        $this->assertStringNotContainsString('googletagmanager.com', $html);
    }

    public function test_analytics_script_rendered_when_configured(): void
    {
        $seo = CPanelSeoSettings::first();
        $seo->ga4_measurement_id = 'G-TESTID123';
        $seo->save();

        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('googletagmanager.com/gtag/js?id=G-TESTID123', $html);
        $this->assertStringContainsString('async', $html);
    }

    // --- sitemap.xml ------------------------------------------------------

    public function test_sitemap_returns_valid_xml_with_post(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();
        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString('posts/introducing-the-cms', $xml);
        $this->assertStringContainsString('xhtml:link', $xml);

        // Well-formed XML.
        $this->assertInstanceOf(\SimpleXMLElement::class, simplexml_load_string($xml));
    }

    public function test_sitemap_disabled_returns_404(): void
    {
        $seo = CPanelSeoSettings::first();
        $seo->sitemap_enabled = false;
        $seo->save();

        $this->get('/sitemap.xml')->assertStatus(404);
    }

    // --- robots.txt -------------------------------------------------------

    public function test_robots_default_allows_and_points_to_sitemap(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertStatus(200);

        $body = $response->getContent();
        $this->assertStringContainsString('User-agent: *', $body);
        $this->assertStringContainsString('Sitemap:', $body);
        $this->assertStringContainsString('Allow: /', $body);
        // Default (not discouraged) must NOT block the whole site.
        $this->assertStringNotContainsString("\nDisallow: /\n", $body);
    }

    public function test_robots_honours_discourage_toggle(): void
    {
        $seo = CPanelSeoSettings::first();
        $seo->discourage_search_engines = true;
        $seo->save();

        $body = $this->get('/robots.txt')->assertStatus(200)->getContent();
        $this->assertStringContainsString('Disallow: /', $body);
    }

    public function test_robots_appends_admin_extra_lines(): void
    {
        $seo = CPanelSeoSettings::first();
        $seo->robots_extra = 'Disallow: /secret-area';
        $seo->save();

        $body = $this->get('/robots.txt')->getContent();
        $this->assertStringContainsString('Disallow: /secret-area', $body);
    }

    // --- llms.txt ---------------------------------------------------------

    public function test_llms_txt_renders(): void
    {
        $body = $this->get('/llms.txt')->assertStatus(200)->getContent();
        $this->assertStringContainsString('# ', $body);
        $this->assertStringContainsString('Sitemap', $body);
    }
}
