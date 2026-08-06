<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configurable password policy on the security_settings singleton. Defaults
 * reproduce today's behaviour (min length 8, no other constraints, HIBP off),
 * so existing accounts and tests are unaffected until an admin tightens it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('password_min_length')->default(8)->after('require_2fa_for_admins');
            $table->boolean('password_require_mixed_case')->default(false)->after('password_min_length');
            $table->boolean('password_require_numbers')->default(false)->after('password_require_mixed_case');
            $table->boolean('password_require_symbols')->default(false)->after('password_require_numbers');
            $table->boolean('password_check_hibp')->default(false)->after('password_require_symbols');
        });
    }

    public function down(): void
    {
        Schema::table('security_settings', function (Blueprint $table) {
            $table->dropColumn([
                'password_min_length',
                'password_require_mixed_case',
                'password_require_numbers',
                'password_require_symbols',
                'password_check_hibp',
            ]);
        });
    }
};
