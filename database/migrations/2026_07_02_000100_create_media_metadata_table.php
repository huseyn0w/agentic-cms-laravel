<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FEATURE_MATRIX §7 — per-asset media metadata.
 *
 * LFM stores files by path only. This table attaches editorial + technical
 * metadata (alt/title/caption + mime/size/dimensions) to each asset, keyed by
 * its storage-relative path. Captured on upload, editable from the media UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_metadata', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('path')->unique();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt')->nullable();
            $table->string('title')->nullable();
            $table->text('caption')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_metadata');
    }
};
