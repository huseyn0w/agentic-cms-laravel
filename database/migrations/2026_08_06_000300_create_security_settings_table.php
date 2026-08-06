<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Security settings singleton (row id = 1).
 *
 * Holds the login-protection knobs that used to be hardcoded in the
 * ThrottlesLogins concern (max attempts / decay) plus an optional longer
 * auto-block tier that trips after a higher failed-attempt threshold. Mirrors
 * the geo_settings / seo_settings convention: one row, no timestamps,
 * model-cached. Absence of the row is tolerated by get_security_settings(),
 * which falls back to the shipped defaults.
 */
class CreateSecuritySettingsTable extends Migration
{
    public function up()
    {
        Schema::create('security_settings', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Standard throttle: N failed attempts within the decay window locks
            // the email+IP pair for that window.
            $table->boolean('login_throttle_enabled')->default(true);
            $table->unsignedSmallInteger('login_max_attempts')->default(5);
            $table->unsignedSmallInteger('login_decay_minutes')->default(1);

            // Auto-block tier: a second, longer-lived counter. Once failed
            // attempts reach the (higher) threshold, the pair is blocked for the
            // longer block window regardless of the short throttle having decayed.
            $table->boolean('login_block_enabled')->default(false);
            $table->unsignedSmallInteger('login_block_threshold')->default(10);
            $table->unsignedInteger('login_block_minutes')->default(60);
        });
    }

    public function down()
    {
        Schema::dropIfExists('security_settings');
    }
}
