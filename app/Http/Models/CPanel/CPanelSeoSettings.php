<?php

namespace App\Http\Models\CPanel;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 7 (SEO/GEO): global SEO settings singleton (row id = 1).
 *
 * Mirrors the CPanelSiteOptions / CPanelGeneralSettings convention: a single
 * row, no timestamps, model-cached for cheap reads on every front request.
 */
class CPanelSeoSettings extends Model
{
    use Cachable;

    public $timestamps = false;

    protected $table = 'seo_settings';

    protected $fillable = [
        'title_separator',
        'default_meta_description',
        'default_og_image',
        'og_site_name',
        'twitter_handle',
        'google_site_verification',
        'bing_site_verification',
        'ga4_measurement_id',
        'gtm_container_id',
        'discourage_search_engines',
        'sitemap_enabled',
        'robots_extra',
        'ai_crawlers',
    ];

    protected $casts = [
        'discourage_search_engines' => 'boolean',
        'sitemap_enabled' => 'boolean',
        'ai_crawlers' => 'array',
    ];
}
