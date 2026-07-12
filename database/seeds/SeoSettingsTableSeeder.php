<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Phase 7 (SEO/GEO): sensible default SEO settings (singleton row id = 1).
 *
 * Idempotent: skips if a row already exists so re-running the full seeder set
 * against the live MySQL dev DB never duplicates the singleton.
 */
class SeoSettingsTableSeeder extends Seeder
{
    public function run()
    {
        if (DB::table('seo_settings')->exists()) {
            return;
        }

        DB::table('seo_settings')->insertOrIgnore([
            'title_separator' => '—',
            'default_meta_description' => 'AgenticCms-Laravel — a fast, lightweight Laravel content platform.',
            'default_og_image' => null,
            'og_site_name' => 'AgenticCms-Laravel',
            'twitter_handle' => null,
            'google_site_verification' => null,
            'bing_site_verification' => null,
            'ga4_measurement_id' => null,
            'gtm_container_id' => null,
            'discourage_search_engines' => false,
            'sitemap_enabled' => true,
            'robots_extra' => null,
        ]);
    }
}
