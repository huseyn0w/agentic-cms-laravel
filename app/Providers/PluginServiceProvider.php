<?php

namespace App\Providers;

use App\Support\Hooks;
use App\Support\PluginManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * P9: prime the hook engine with the enabled plugins.
 *
 * Loading is lazy — it runs the first time the Hooks singleton is resolved
 * (once per request/app instance) rather than during provider boot. That keeps
 * the database read out of the bootstrap phase, so it always uses the final DB
 * connection and re-evaluates the enabled set on every request (enable/disable
 * with no restart). Guarded by Schema::hasTable for fresh installs / migrations.
 */
class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $primed = false;

        $this->app->afterResolving(Hooks::class, function (Hooks $hooks, $app) use (&$primed) {
            if ($primed) {
                return;
            }
            $primed = true;

            // hasTable() hits information_schema on every request. The table
            // only ever goes from absent -> present (a migration), so cache the
            // positive result forever; a fresh install keeps re-checking until
            // the table exists. A deploy's cache:clear resets it.
            $hasPluginsTable = Cache::get('cms.schema.has_plugins_table') === true;

            if (! $hasPluginsTable) {
                if (! Schema::hasTable('plugins')) {
                    return;
                }
                Cache::forever('cms.schema.has_plugins_table', true);
            }

            // Only load the ENABLED plugins' hooks. Do NOT sync() here — that
            // does a firstOrCreate per discovered plugin (a SELECT, plus an
            // INSERT the first time) on EVERY request, including public GETs.
            // Syncing new plugin rows into the table is an admin/deploy concern:
            // CPanelPluginService::listForAdmin() and toggle() already ensure the
            // rows exist when the admin actually manages plugins.
            $app->make(PluginManager::class)->loadEnabled($hooks);
        });
    }
}
