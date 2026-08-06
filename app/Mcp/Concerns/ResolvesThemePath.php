<?php

namespace App\Mcp\Concerns;

/**
 * Path safety for the theme-file tools.
 *
 * Since the UI migrated to Inertia + React, the editable "theme" is the set of
 * React page components under resources/js/pages (*.tsx). This trait centralises
 * the allow-listing: it rejects path traversal, absolute paths, non-.tsx files,
 * co-located test files (*.test.tsx), and anything resolving outside the pages
 * root. There is intentionally no method here that compiles or executes a
 * component — only locate/read/write of text.
 */
trait ResolvesThemePath
{
    /** Absolute path to the React pages directory (the editable UI surface). */
    protected function themeRoot(): string
    {
        return realpath(resource_path('js/pages')) ?: resource_path('js/pages');
    }

    /**
     * Resolve a caller-supplied relative path to an absolute path that is
     * guaranteed to live inside the pages root and end in .tsx (never a
     * co-located *.test.tsx).
     *
     * @return string|null Absolute safe path, or null if the path is rejected.
     */
    protected function safeThemePath(string $relative, bool $mustExist = true): ?string
    {
        // No absolute paths, no traversal, no NUL bytes.
        if ($relative === '' || str_contains($relative, "\0") || str_contains($relative, '..')) {
            return null;
        }

        if (str_starts_with($relative, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $relative)) {
            return null;
        }

        if (! str_ends_with($relative, '.tsx') || str_ends_with($relative, '.test.tsx')) {
            return null;
        }

        $root = $this->themeRoot();
        $candidate = $root.DIRECTORY_SEPARATOR.ltrim($relative, '/\\');

        if ($mustExist) {
            $real = realpath($candidate);

            return ($real && str_starts_with($real, $root.DIRECTORY_SEPARATOR)) ? $real : null;
        }

        // For writes the file may not exist yet; verify the *parent* directory
        // resolves inside the pages root so a new file can't escape it.
        $parent = realpath(dirname($candidate));

        if (! $parent || ! ($parent === $root || str_starts_with($parent, $root.DIRECTORY_SEPARATOR))) {
            return null;
        }

        return $parent.DIRECTORY_SEPARATOR.basename($candidate);
    }
}
