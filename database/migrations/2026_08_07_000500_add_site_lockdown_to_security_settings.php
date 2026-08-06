<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional site lockdown (private / pre-launch mode) on the security_settings
 * singleton. When enabled, guests are redirected to the login form on the
 * public front-end; authenticated users pass through. Default false, so
 * existing installs are unaffected until an admin opts in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_settings', function (Blueprint $table) {
            $table->boolean('site_lockdown_enabled')->default(false)->after('admin_ip_allowlist');
        });
    }

    public function down(): void
    {
        Schema::table('security_settings', function (Blueprint $table) {
            $table->dropColumn('site_lockdown_enabled');
        });
    }
};
