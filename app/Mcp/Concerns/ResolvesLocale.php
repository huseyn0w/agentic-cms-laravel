<?php

namespace App\Mcp\Concerns;

/**
 * Locale handling for translatable content tools.
 *
 * AgenticCms-Laravel content (Post/Page/Category) is multilingual via
 * astrotomic/laravel-translatable: writes land in the *_translations row for
 * the *active* application locale. The repositories pick this up implicitly, so
 * a tool must set the locale explicitly before delegating, rather than relying
 * on the per-request Localization middleware (which never runs for MCP calls).
 */
trait ResolvesLocale
{
    /**
     * Validate $locale against config('app.languages_list') and make it the
     * active locale for the duration of the call. Falls back to the configured
     * default when null/empty. Returns the resolved locale code.
     */
    protected function applyLocale(?string $locale): string
    {
        $available = array_keys(config('app.languages_list', []));
        $default = config('app.locale', 'en');

        $resolved = ($locale && in_array($locale, $available, true)) ? $locale : $default;

        app()->setLocale($resolved);

        return $resolved;
    }

    /**
     * The list of selectable locale codes, for tool schema enums / errors.
     *
     * @return array<int, string>
     */
    protected function availableLocales(): array
    {
        return array_keys(config('app.languages_list', []));
    }
}
