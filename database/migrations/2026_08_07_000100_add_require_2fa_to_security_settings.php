<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-wide switch: when on, anyone with see_admin_panel must enroll in 2FA
 * before using the panel. Defaults off so nothing changes until an admin opts in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_settings', function (Blueprint $table) {
            $table->boolean('require_2fa_for_admins')->default(false)->after('login_block_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('security_settings', function (Blueprint $table) {
            $table->dropColumn('require_2fa_for_admins');
        });
    }
};
