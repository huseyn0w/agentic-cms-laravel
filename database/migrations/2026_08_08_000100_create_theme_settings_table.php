<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Theme settings singleton (row id = 1) — tier-1 (data/token) theming.
 *
 * The public site is fully CSS-variable driven (resources/css/tokens.css), so a
 * fleet site can re-skin from the admin with NO rebuild: these values are
 * injected as CSS variables into the public root Blade at request time. This is
 * what makes the in-admin core updater viable on hosts with no Node — a fork
 * consumes core's prebuilt bundle and still gets its own brand. Mirrors the
 * seo/geo settings convention: one row, no timestamps, model-cached.
 */
class CreateThemeSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Brand name shown in the public header (falls back to the app name).
            $table->string('site_title')->nullable();

            // Accent colour (hex). Overrides --accent / --accent-hover / --ring
            // on the public theme. Empty = keep the shipped violet accent.
            $table->string('accent_color', 7)->nullable();

            // Font family stack applied to the public theme body. Empty = Geist.
            $table->string('font_family', 255)->nullable();

            // Base corner radius in px (overrides --radius-md). Null = shipped.
            $table->unsignedSmallInteger('radius')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('theme_settings');
    }
}
