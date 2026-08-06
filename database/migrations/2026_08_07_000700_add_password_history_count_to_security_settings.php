<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How many previous passwords a user may not reuse. 0 (default) disables the
 * policy, so existing installs are unaffected until an admin opts in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_settings', function (Blueprint $table) {
            $table->unsignedInteger('password_history_count')->default(0)->after('site_lockdown_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('security_settings', function (Blueprint $table) {
            $table->dropColumn('password_history_count');
        });
    }
};
