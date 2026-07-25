<?php

namespace App\Support\I18n;

use FilesystemIterator;
use Illuminate\Support\Facades\Cache;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Builds a flat, frontend-ready dictionary for a locale from the existing
 * resources/lang/{locale}/**\/*.php files. Keys match the Blade keys verbatim
 * (directory via "/", array depth via "."); Laravel ":name" placeholders are
 * normalized to i18next "{{name}}". PHP lang files remain the single source of
 * truth — nothing is generated or duplicated.
 */
class TranslationDictionary
{
    /** @var array<string, array<string, string>> per-instance memo by locale */
    private array $memo = [];

    /** @return array<string, string> */
    public function forLocale(string $locale): array
    {
        return $this->memo[$locale] ??= $this->build($locale);
    }

    /** @return array<string, string> */
    private function build(string $locale): array
    {
        // In production the lang files are static between deploys, so cache the
        // built dictionary across requests (plain PHP-FPM gives no in-process
        // reuse). `php artisan cache:clear` on deploy refreshes it. Outside
        // production we rebuild each time so lang-file edits show immediately.
        if (app()->isProduction()) {
            return Cache::rememberForever("i18n.dict.{$locale}", fn (): array => $this->load($locale));
        }

        return $this->load($locale);
    }

    /** @return array<string, string> */
    private function load(string $locale): array
    {
        $base = lang_path($locale);

        if (! is_dir($base)) {
            return [];
        }

        $messages = [];

        foreach ($this->phpFiles($base) as $file) {
            $group = $this->groupName($base, $file);
            // Load the locale's file directly. Each PHP lang file returns its
            // array; this reads exactly that locale's strings with no cross-locale
            // fallback merging, keeping the files the single source of truth.
            $lines = require $file;

            if (is_array($lines)) {
                $this->flatten($group, $lines, $messages);
            }
        }

        return $messages;
    }

    /** @return iterable<string> absolute file paths */
    private function phpFiles(string $base): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                yield $file->getPathname();
            }
        }
    }

    private function groupName(string $base, string $file): string
    {
        $relative = substr($file, strlen($base) + 1); // strip "$base/"
        $relative = substr($relative, 0, -4);          // strip ".php"

        return str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    }

    /**
     * @param  array<string, mixed>  $lines
     * @param  array<string, string>  $out
     */
    private function flatten(string $prefix, array $lines, array &$out): void
    {
        foreach ($lines as $key => $value) {
            $composite = "{$prefix}.{$key}";

            if (is_array($value)) {
                $this->flatten($composite, $value, $out);
            } else {
                $out[$composite] = $this->normalizePlaceholders((string) $value);
            }
        }
    }

    private function normalizePlaceholders(string $value): string
    {
        // Assumes Laravel placeholder syntax only (":name"). A future lang
        // string containing a URI scheme or time literal (e.g. "mailto:info",
        // "9:30") would be rewritten too — none exist in the lang files today.
        return preg_replace_callback(
            '/:([a-zA-Z][a-zA-Z0-9_]*)/',
            fn (array $m): string => '{{'.strtolower($m[1]).'}}',
            $value
        );
    }
}
