<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-migration gap: a single "Book a call" link (e.g. Calendly) on the
 * general-settings singleton. The theme renders a button pointing at it;
 * nullable so installs without a booking link render nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->string('booking_url', 2000)->nullable()->after('active_template_name');
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn('booking_url');
        });
    }
};
