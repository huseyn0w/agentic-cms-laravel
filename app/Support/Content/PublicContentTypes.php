<?php

namespace App\Support\Content;

use App\Support\PluginManager;

/**
 * Filesystem discovery of PUBLIC content types, used to register their front
 * routes. Enable-agnostic and DB-free (safe to call while routes/web.php loads
 * and during route:cache): it reads the plugin classes on disk, not the plugins
 * table. The controller enforces enabled + public at request time.
 */
class PublicContentTypes
{
    /**
     * Public content types as slug => hasDetail (whether a detail page applies).
     *
     * @return array<string, bool>
     */
    public static function discover(): array
    {
        $out = [];

        foreach (app(PluginManager::class)->discover() as $plugin) {
            if (! $plugin instanceof RegistersContentTypes) {
                continue;
            }

            foreach ($plugin->contentTypes() as $type) {
                if ($type->isPublic) {
                    $out[$type->slug] = $type->hasDetail();
                }
            }
        }

        return $out;
    }
}
