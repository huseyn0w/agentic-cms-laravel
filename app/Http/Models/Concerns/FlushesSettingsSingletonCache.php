<?php

namespace App\Http\Models\Concerns;

/**
 * The settings singletons (general/site/seo/geo/theme/security) are cached for
 * the duration of a request by cms_settings_singleton() so the header, footer,
 * seo-meta and controllers don't re-query the same one row. This trait keeps
 * that cache honest: when the row is written or deleted, drop the cached copy so
 * a read later in the SAME request (e.g. an admin saving settings then rendering
 * the result) sees the fresh value instead of the stale one.
 */
trait FlushesSettingsSingletonCache
{
    public static function bootFlushesSettingsSingletonCache(): void
    {
        static::saved(fn () => cms_forget_settings_singleton(static::class));
        static::deleted(fn () => cms_forget_settings_singleton(static::class));
    }
}
