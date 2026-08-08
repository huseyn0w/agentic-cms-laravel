<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Managed URL redirects (301/302). Needed for an SEO-safe migration: old
 * WordPress permalinks (/category/post) map to the new CMS URLs so search
 * rankings and backlinks survive. Replaces Rank Math's redirection manager.
 * `source_path` is the normalized incoming path (leading slash, no trailing
 * slash, no query); `hits` counts how often each redirect fires.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source_path')->unique();
            $table->string('target');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
