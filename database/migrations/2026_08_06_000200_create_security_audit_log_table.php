<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Security: append-only audit trail of security-relevant events (logins,
 * failed logins, logouts, lockouts). Read from the admin Security screen.
 * Portable across MySQL and SQLite; nullable actor for anonymous events
 * (e.g. a failed login for an address that never authenticated).
 */
class CreateSecurityAuditLogTable extends Migration
{
    public function up()
    {
        Schema::create('security_audit_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('action', 40)->index();
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('actor', 191)->nullable();   // username/email at event time
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('security_audit_log');
    }
}
