<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AEO: per-bot AI-crawler allow/deny map for the SEO settings singleton.
 *
 * Stores a JSON object keyed by the bot keys in config/ai_crawlers.php, e.g.
 * {"gptbot": false, "claudebot": true}. A missing key means "allowed" (the
 * default), so an empty/null column leaves robots.txt unchanged. Portable
 * across MySQL and SQLite (json → text on SQLite).
 */
class AddAiCrawlersToSeoSettings extends Migration
{
    public function up()
    {
        Schema::table('seo_settings', function (Blueprint $table) {
            $table->json('ai_crawlers')->nullable()->after('robots_extra');
        });
    }

    public function down()
    {
        Schema::table('seo_settings', function (Blueprint $table) {
            $table->dropColumn('ai_crawlers');
        });
    }
}
