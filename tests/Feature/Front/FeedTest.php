<?php

namespace Tests\Feature\Front;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\PostTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Phase 7 (P7): public content syndication feeds — /rss.xml (RSS 2.0) and
 * /atom.xml (Atom 1.0). Both list PUBLISHED posts only (status 1, not a
 * future-scheduled draft), are well-formed XML, lightly cached, and registered
 * before the front catch-all. Mirrors the sitemap pattern.
 *
 * Seeded fixtures include the published post "introducing-the-cms".
 */
class FeedTest extends TestCase
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

    private function makePost(string $slug, $scheduledAt = null, int $status = 1): PostTranslation
    {
        $this->actingAs($this->admin)->post('/cmstack-laravel-admin/posts/new', [
            'title' => $slug, 'slug' => $slug, 'content' => 'body of '.$slug, 'preview' => 'prev '.$slug,
            'author_id' => $this->admin->id, 'meta_keywords' => 'k', 'meta_description' => 'd',
            'category' => [1], 'status' => $status,
        ]);

        $t = PostTranslation::where('slug', $slug)->firstOrFail();
        PostTranslation::where('id', $t->id)->update(['scheduled_at' => $scheduledAt, 'status' => $status]);

        return $t->fresh();
    }

    // --- RSS 2.0 ----------------------------------------------------------

    public function test_rss_returns_valid_xml_with_published_post(): void
    {
        $response = $this->get('/rss.xml');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

        $xml = $response->getContent();
        $this->assertStringContainsString('<rss', $xml);
        $this->assertStringContainsString('<channel>', $xml);
        $this->assertStringContainsString('posts/introducing-the-cms', $xml);
        $this->assertStringContainsString('<item>', $xml);

        // Well-formed XML.
        $this->assertInstanceOf(\SimpleXMLElement::class, simplexml_load_string($xml));
    }

    public function test_rss_channel_has_self_link_and_pubdate(): void
    {
        $xml = $this->get('/rss.xml')->getContent();
        $this->assertStringContainsString('rel="self"', $xml);
        $this->assertStringContainsString('<pubDate>', $xml);
        $this->assertStringContainsString('<guid', $xml);
    }

    // --- Atom 1.0 ---------------------------------------------------------

    public function test_atom_returns_valid_xml_with_published_post(): void
    {
        $response = $this->get('/atom.xml');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/atom+xml; charset=UTF-8');

        $xml = $response->getContent();
        $this->assertStringContainsString('<feed', $xml);
        $this->assertStringContainsString('xmlns="http://www.w3.org/2005/Atom"', $xml);
        $this->assertStringContainsString('posts/introducing-the-cms', $xml);
        $this->assertStringContainsString('<entry>', $xml);

        $this->assertInstanceOf(\SimpleXMLElement::class, simplexml_load_string($xml));
    }

    // --- PUBLISHED-only guarantees ---------------------------------------

    public function test_future_scheduled_draft_is_excluded_from_feeds(): void
    {
        $this->makePost('feed-hidden', now()->addDay(), status: 0);
        $this->makePost('feed-shown', now()->subHour(), status: 1);

        Cache::forget('cmstack_laravel.rss.xml');
        Cache::forget('cmstack_laravel.atom.xml');

        $rss = $this->get('/rss.xml')->getContent();
        $atom = $this->get('/atom.xml')->getContent();

        $this->assertStringNotContainsString('feed-hidden', $rss, 'Scheduled draft must not leak into RSS.');
        $this->assertStringNotContainsString('feed-hidden', $atom, 'Scheduled draft must not leak into Atom.');
        $this->assertStringContainsString('feed-shown', $rss);
        $this->assertStringContainsString('feed-shown', $atom);
    }

    public function test_plain_draft_is_excluded_from_feeds(): void
    {
        // A plain draft (status 0, no schedule) is reachable by slug but must
        // NEVER appear in syndication feeds — feeds carry PUBLISHED posts only.
        $this->makePost('draft-only', null, status: 0);

        Cache::forget('cmstack_laravel.rss.xml');
        Cache::forget('cmstack_laravel.atom.xml');

        $this->assertStringNotContainsString('draft-only', $this->get('/rss.xml')->getContent());
        $this->assertStringNotContainsString('draft-only', $this->get('/atom.xml')->getContent());
    }

    // --- Hostile content well-formedness ---------------------------------

    public function test_feed_stays_well_formed_with_hostile_slug_and_control_chars(): void
    {
        $t = $this->makePost('hostile', null, status: 1);

        // A double-quote in the slug lands in the Atom <link href="..."> attribute
        // (breakout) and a C0 control char in the title is illegal in XML 1.0.
        PostTranslation::where('id', $t->id)->update([
            'slug' => 'a"b',
            'title' => "ctrl\x05title",
        ]);

        Cache::forget('cmstack_laravel.rss.xml');
        Cache::forget('cmstack_laravel.atom.xml');

        $rss = $this->get('/rss.xml')->getContent();
        $atom = $this->get('/atom.xml')->getContent();

        libxml_use_internal_errors(true);
        $this->assertNotFalse(simplexml_load_string($rss), 'RSS must stay well-formed with hostile content.');
        $this->assertNotFalse(simplexml_load_string($atom), 'Atom must stay well-formed with hostile content.');
        libxml_use_internal_errors(false);

        // The injected quote must be escaped, not break out of the href attribute.
        $this->assertStringNotContainsString('href="'.url('/posts/a"b'), $atom);
    }

    // --- Autodiscovery ----------------------------------------------------

    public function test_head_advertises_feed_autodiscovery_links(): void
    {
        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('application/rss+xml', $html);
        $this->assertStringContainsString('application/atom+xml', $html);
        $this->assertStringContainsString('/rss.xml', $html);
        $this->assertStringContainsString('/atom.xml', $html);
    }
}
