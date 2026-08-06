<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opt-in HSTS + CSP on the security_settings singleton. Baseline security
 * headers (nosniff, frame options, referrer policy, permissions policy) are
 * always sent by the SecurityHeaders middleware and need no columns. HSTS and
 * CSP are off/empty by default so production behaviour is unchanged until an
 * admin turns them on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_settings', function (Blueprint $table) {
            $table->boolean('hsts_enabled')->default(false)->after('password_check_hibp');
            $table->unsignedInteger('hsts_max_age')->default(15552000)->after('hsts_enabled'); // 180 days
            $table->text('csp')->nullable()->after('hsts_max_age');
            $table->boolean('csp_report_only')->default(false)->after('csp');
        });
    }

    public function down(): void
    {
        Schema::table('security_settings', function (Blueprint $table) {
            $table->dropColumn(['hsts_enabled', 'hsts_max_age', 'csp', 'csp_report_only']);
        });
    }
};
