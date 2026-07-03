<?php

namespace App\Services\Front;

use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Phase 7 (P7): composes the public content-syndication feeds — RSS 2.0
 * (/rss.xml) and Atom 1.0 (/atom.xml) — from the most recent PUBLISHED posts.
 *
 * All data access goes through PostRepository::feedEntries (which already
 * excludes drafts and future-scheduled posts); this service only renders the
 * returned rows into XML and the controller wraps the strings in HTTP
 * responses/caching. No Eloquent or query building lives here.
 */
class FeedService
{
    /** Maximum number of items in a feed. */
    private const ITEM_LIMIT = 20;

    public function __construct(
        private PostRepository $posts,
        private CategoryRepository $categories,
    ) {}

    /**
     * Resolve a category by slug in the feed locale (config('app.locale')), or
     * null when unknown — the controller turns null into a 404.
     */
    public function resolveCategory(string $slug)
    {
        return $this->categories->findForFeedBySlug($slug, config('app.locale'));
    }

    /**
     * Build the RSS 2.0 feed of a single category's most recent published posts
     * (FEATURE_MATRIX §16). Reuses the same rendering as the global feed; only
     * the item set, channel title and self-link differ.
     */
    public function buildCategoryRss(int $categoryId, string $categoryTitle, string $categorySlug): string
    {
        return $this->buildRss($this->categoryContext($categoryId, $categoryTitle, $categorySlug));
    }

    /**
     * Build the Atom 1.0 feed of a single category's most recent published posts.
     */
    public function buildCategoryAtom(int $categoryId, string $categoryTitle, string $categorySlug): string
    {
        return $this->buildAtom($this->categoryContext($categoryId, $categoryTitle, $categorySlug));
    }

    /**
     * Build the RSS 2.0 feed of the most recent published posts.
     *
     * @param  array{0:string,1:string,2:string,3:string,4:Collection,5:string}|null  $context
     */
    public function buildRss(?array $context = null): string
    {
        [$base, $title, $description, $locale, $items, $self] = $context ?? $this->context();
        $self = $self ?: $base.'/rss.xml';
        $built = $this->latestTimestamp($items);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n";
        $xml .= "  <channel>\n";
        $xml .= '    <title>'.$this->esc($title)."</title>\n";
        $xml .= '    <link>'.$this->esc($base)."</link>\n";
        $xml .= '    <description>'.$this->esc($description)."</description>\n";
        $xml .= '    <language>'.$this->esc($locale)."</language>\n";
        $xml .= '    <atom:link href="'.$this->esc($self).'" rel="self" type="application/rss+xml"/>'."\n";
        $xml .= '    <lastBuildDate>'.$built->toRssString()."</lastBuildDate>\n";

        foreach ($items as $item) {
            $url = $this->postUrl($base, $locale, $item->slug);
            $xml .= "    <item>\n";
            $xml .= '      <title>'.$this->esc($item->title)."</title>\n";
            $xml .= '      <link>'.$this->esc($url)."</link>\n";
            $xml .= '      <guid isPermaLink="true">'.$this->esc($url)."</guid>\n";
            $xml .= '      <pubDate>'.$item->created_at->toRssString()."</pubDate>\n";
            $xml .= '      <description>'.$this->esc($this->summary($item))."</description>\n";
            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n";
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Build the Atom 1.0 feed of the most recent published posts.
     *
     * @param  array{0:string,1:string,2:string,3:string,4:Collection,5:string}|null  $context
     */
    public function buildAtom(?array $context = null): string
    {
        [$base, $title, $description, $locale, $items, $self] = $context ?? $this->context();
        $self = $self ?: $base.'/atom.xml';
        $updated = $this->latestTimestamp($items);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="'.$this->esc($locale).'">'."\n";
        $xml .= '  <title>'.$this->esc($title)."</title>\n";
        $xml .= '  <subtitle>'.$this->esc($description)."</subtitle>\n";
        $xml .= '  <link href="'.$this->esc($base).'"/>'."\n";
        $xml .= '  <link href="'.$this->esc($self).'" rel="self"/>'."\n";
        $xml .= '  <id>'.$this->esc($base.'/')."</id>\n";
        $xml .= '  <updated>'.$updated->toAtomString()."</updated>\n";

        foreach ($items as $item) {
            $url = $this->postUrl($base, $locale, $item->slug);
            $xml .= "  <entry>\n";
            $xml .= '    <title>'.$this->esc($item->title)."</title>\n";
            $xml .= '    <link href="'.$this->esc($url)."\"/>\n";
            $xml .= '    <id>'.$this->esc($url)."</id>\n";
            $xml .= '    <published>'.$item->created_at->toAtomString()."</published>\n";
            $xml .= '    <updated>'.optional($item->updated_at ?? $item->created_at)->toAtomString()."</updated>\n";
            $xml .= '    <summary>'.$this->esc($this->summary($item))."</summary>\n";
            $xml .= "  </entry>\n";
        }

        $xml .= '</feed>';

        return $xml;
    }

    /**
     * Shared feed context: site URL, channel title/description, locale, rows,
     * and the feed's self-link (null → the builder uses the site-wide default).
     *
     * @return array{0:string,1:string,2:string,3:string,4:Collection,5:?string}
     */
    private function context(): array
    {
        $base = rtrim(config('app.url'), '/');
        $locale = config('app.locale');
        $title = get_general_settings('website_name') ?: config('app.name');
        $description = get_general_settings('tagline') ?: $title;
        $items = $this->posts->feedEntries($locale, self::ITEM_LIMIT);

        return [$base, $title, $description, $locale, $items, null];
    }

    /**
     * Feed context scoped to a single category (FEATURE_MATRIX §16): same site
     * base/locale, but the channel title names the category, the item set is the
     * category's published posts, and the self-link points at the category feed.
     *
     * @return array{0:string,1:string,2:string,3:string,4:Collection,5:string}
     */
    private function categoryContext(int $categoryId, string $categoryTitle, string $categorySlug): array
    {
        $base = rtrim(config('app.url'), '/');
        $locale = config('app.locale');
        $siteTitle = get_general_settings('website_name') ?: config('app.name');
        $title = $siteTitle.' — '.$categoryTitle;
        $description = get_general_settings('tagline') ?: $siteTitle;
        $items = $this->posts->feedEntriesForCategory($categoryId, $locale, self::ITEM_LIMIT);
        $self = $base.'/blog/category/'.ltrim($categorySlug, '/').'/rss.xml';

        return [$base, $title, $description, $locale, $items, $self];
    }

    /**
     * Public URL of a post in the default locale (no locale prefix).
     */
    private function postUrl(string $base, string $locale, string $slug): string
    {
        $default = config('app.locale');
        $localePart = ($locale === $default) ? '' : $locale.'/';

        return $base.'/'.$localePart.'posts/'.ltrim($slug, '/');
    }

    /**
     * A short, tag-free summary for a feed item: the editor preview when set,
     * otherwise a trimmed excerpt of the body.
     */
    private function summary($item): string
    {
        $text = ! empty($item->preview) ? $item->preview : (string) $item->content;

        return Str::limit(trim(strip_tags($text)), 500);
    }

    /**
     * Newest timestamp across the items, used for the feed's build/updated date;
     * falls back to "now" when the feed is empty.
     */
    private function latestTimestamp($items)
    {
        $latest = null;
        foreach ($items as $item) {
            $stamp = $item->updated_at ?? $item->created_at;
            if ($stamp && ($latest === null || $stamp->greaterThan($latest))) {
                $latest = $stamp;
            }
        }

        return $latest ?? now();
    }

    /**
     * Escape a value for safe interpolation into XML element or attribute
     * content. First strips characters that are illegal in XML 1.0 (the C0
     * control range except tab/LF/CR), which would otherwise make the feed
     * non-well-formed; then escapes & < > " ' (ENT_QUOTES escapes the double
     * quote that ENT_XML1 alone leaves raw inside href="..." attributes).
     */
    private function esc(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

        // ENT_SUBSTITUTE keeps the rest of an invalid-UTF-8 string (replacing the
        // bad bytes with U+FFFD) instead of htmlspecialchars returning "".
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
