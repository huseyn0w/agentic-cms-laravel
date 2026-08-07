<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Newsletter subscribers (Phase 1). A standalone list — not tied to the users
 * table — so anyone can subscribe by email. `token` is a stable, unguessable
 * per-subscriber secret used in BOTH the confirm and unsubscribe URLs; it never
 * expires so unsubscribe links keep working forever. `created_at` doubles as the
 * consent timestamp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('status', 16)->default('pending'); // pending|confirmed|unsubscribed
            $table->string('token', 64)->unique();
            $table->string('locale', 8)->nullable();
            $table->string('source', 32)->default('footer');  // footer|admin
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
