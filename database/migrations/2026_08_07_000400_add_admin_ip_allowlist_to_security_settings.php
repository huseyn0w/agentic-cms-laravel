<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional admin-panel IP allowlist on the security_settings singleton. One IP
 * or CIDR per line; empty means no restriction (default), so existing installs
 * are unaffected until an admin opts in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_settings', function (Blueprint $table) {
            $table->text('admin_ip_allowlist')->nullable()->after('csp_report_only');
        });
    }

    public function down(): void
    {
        Schema::table('security_settings', function (Blueprint $table) {
            $table->dropColumn('admin_ip_allowlist');
        });
    }
};
