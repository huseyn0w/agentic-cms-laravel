<?php

namespace App\Services\Front;

use App\Repositories\CategoryRepository;
use App\Repositories\PageRepository;
use App\Repositories\PostRepository;
use App\Repositories\ServiceRepository;

/**
 * Phase 7 (SEO/GEO): composes the public SEO feeds — sitemap.xml, robots.txt and
 * llms.txt — out of the SEO/GEO settings helpers and the sitemap/llms rows
 * provided by the page/post/category repositories. All data access goes through
 * those repositories; the service contains no Eloquent or query building, and
 * the controller only wraps the returned strings in HTTP responses/caching.
 */
class SeoFeedService
{
    public function __construct(
        private PageRepository $pages,
        private PostRepository $posts,
        private CategoryRepository $categories,
        private ServiceRepository $services,
    ) {}

    /**
     * Build the full XML sitemap with hreflang alternates.
     */
    public function buildSitemap(): string
    {
        $base = rtrim(config('app.url'), '/');
        $default = config('app.locale');         // 'en'

        $urls = [];

        // --- Pages (slug "/" => homepage) ---
        $pages = $this->pages->sitemapEntries();
        $urls = array_merge($urls, $this->groupBy($pages, 'page_id', '', $base, $default, true));

        // --- Posts ---
        $posts = $this->posts->sitemapEntries();
        $urls = array_merge($urls, $this->groupBy($posts, 'post_id', 'posts/', $base, $default));

        // --- Categories (category_translations has no timestamps) ---
        $categories = $this->categories->sitemapEntries();
        $urls = array_merge($urls, $this->groupBy($categories, 'category_id', 'category/', $base, $default));

        // --- Services ---
        $services = $this->services->sitemapEntries();
        $urls = array_merge($urls, $this->groupBy($services, 'service_id', 'services/', $base, $default));

        return $this->renderSitemap($urls);
    }

    /**
     * Build one <url> entry per entity, with one <xhtml:link> alternate per
     * available locale translation.
     */
    protected function groupBy($rows, $key, $prefix, $base, $default, bool $isPage = false): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $byId[$row->$key][$row->locale] = $row;
        }

        $urls = [];
        foreach ($byId as $id => $translations) {
            // Canonical loc uses the default locale if present, else any.
            $canonicalRow = $translations[$default] ?? reset($translations);
            $loc = $this->urlFor($base, $prefix, $canonicalRow->slug, $default, $default, $isPage);

            $alternates = [];
            foreach ($translations as $locale => $row) {
                $alternates[$locale] = $this->urlFor($base, $prefix, $row->slug, $locale, $default, $isPage);
            }

            $urls[] = [
                'loc' => $loc,
                'lastmod' => optional($canonicalRow->updated_at)->toAtomString(),
                'alternates' => $alternates,
            ];
        }

        return $urls;
    }

    protected function urlFor($base, $prefix, $slug, $locale, $default, bool $isPage): string
    {
        $localePart = ($locale === $default) ? '' : $locale.'/';

        // Home page (slug "/") => site root (per-locale root for non-default).
        if ($isPage && $slug === '/') {
            return $locale === $default ? $base : $base.'/'.$locale;
        }

        return $base.'/'.$localePart.$prefix.ltrim($slug, '/');
    }

    protected function renderSitemap(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
              .'xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($url['loc'], ENT_XML1)."</loc>\n";
            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>'.htmlspecialchars($url['lastmod'], ENT_XML1)."</lastmod>\n";
            }
            foreach ($url['alternates'] as $locale => $href) {
                $xml .= '    <xhtml:link rel="alternate" hreflang="'.htmlspecialchars($locale, ENT_XML1)
                      .'" href="'.htmlspecialchars($href, ENT_XML1)."\"/>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Build the dynamic robots.txt body honouring the "discourage search
     * engines" toggle, the sitemap toggle, and the admin "extra lines".
     */
    public function buildRobots(): string
    {
        $seo = get_seo_settings();
        $base = rtrim(config('app.url'), '/');

        $lines = ['User-agent: *'];

        if ($seo && ! empty($seo->discourage_search_engines)) {
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'Disallow: /agentic-cms-laravel-admin';
            $lines[] = 'Disallow: /search';
            $lines[] = 'Allow: /';
        }

        if (! $seo || $seo->sitemap_enabled) {
            $lines[] = '';
            $lines[] = 'Sitemap: '.$base.'/sitemap.xml';
        }

        if ($seo && ! empty($seo->robots_extra)) {
            $lines[] = '';
            // Normalise CRLF and append admin-provided extra lines verbatim.
            foreach (preg_split('/\r\n|\r|\n/', trim($seo->robots_extra)) as $extra) {
                $lines[] = $extra;
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Build the minimal llms.txt (emerging GEO standard): a short markdown index
     * of the site, optional GEO business summary, and key links.
     */
    public function buildLlms(): string
    {
        $base = rtrim(config('app.url'), '/');
        $name = get_general_settings('website_name');
        $tagline = get_general_settings('tagline');

        $geo = get_geo_settings();
        $emitGeo = $geo && $geo->include_in_llms;

        // Real Service content-type pages — emitted as links to /services/{slug}
        // in an additive "## Service pages" section (separate from the GEO
        // free-text business-services summary).
        $serviceRecords = $this->services->llmsEntries();

        $lines = [];
        $lines[] = '# '.(($emitGeo && ! empty($geo->business_name)) ? $geo->business_name : $name);
        $lines[] = '';
        if (! empty($tagline)) {
            $lines[] = '> '.$tagline;
            $lines[] = '';
        }

        // GEO: machine-readable summary for generative engines.
        if ($emitGeo) {
            if (! empty($geo->description)) {
                $lines[] = '## About';
                $lines[] = '';
                $lines[] = $geo->description;
                $lines[] = '';
            }

            // GEO free-text business-services summary (distinct from the real
            // Service content-type pages, which are linked further down).
            $services = $geo->servicesList();
            if (! empty($services)) {
                $lines[] = '## Services';
                $lines[] = '';
                foreach ($services as $service) {
                    $lines[] = '- '.$service;
                }
                if (! empty($geo->service_area)) {
                    $lines[] = '';
                    $lines[] = 'Service area: '.$geo->service_area;
                }
                $lines[] = '';
            }

            $faq = $geo->faqList();
            if (! empty($faq)) {
                $lines[] = '## FAQ';
                $lines[] = '';
                foreach ($faq as $qa) {
                    $lines[] = '### '.$qa['question'];
                    $lines[] = $qa['answer'];
                    $lines[] = '';
                }
            }
        }

        // Real Service content-type pages — linked, locale-aware. Additive and
        // distinct from the GEO free-text services summary above.
        if ($serviceRecords->isNotEmpty()) {
            $lines[] = '## Service pages';
            $lines[] = '';
            foreach ($serviceRecords as $svc) {
                $lines[] = '- ['.$svc->title.']('.$base.'/services/'.$svc->slug.')';
            }
            $lines[] = '';
        }

        $lines[] = '## Key links';
        $lines[] = '';
        $lines[] = '- [Home]('.$base.')';
        $lines[] = '- [Sitemap]('.$base.'/sitemap.xml)';

        $categories = $this->categories->llmsEntries();

        if ($categories->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## Categories';
            $lines[] = '';
            foreach ($categories as $category) {
                $lines[] = '- ['.$category->title.']('.$base.'/category/'.$category->slug.')';
            }
        }

        return implode("\n", $lines)."\n";
    }
}
