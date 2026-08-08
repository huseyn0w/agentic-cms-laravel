<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log for core updates. One row per update attempt, so the admin can see
 * the history and any rollback. Written by the updater (App\Support\Updater).
 */
class CreateCmsUpdatesTable extends Migration
{
    public function up()
    {
        Schema::create('cms_updates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('from_version', 32)->nullable();
            $table->string('to_version', 32)->nullable();
            // pending | success | failed | rolled_back
            $table->string('status', 20)->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cms_updates');
    }
}
