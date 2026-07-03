<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/**
 * Byte-level upload guard (FEATURE_MATRIX §21 / §7).
 *
 * The unisharp/laravel-filemanager MIME check trusts a client-declarable value
 * on some paths and — historically — allowed `image/svg+xml`, which is a live
 * XSS/polyglot vector. This guard is the single source of truth for what may be
 * stored: it sniffs the file's real MIME via magic bytes (finfo), rejects SVG
 * outright, and only accepts a small allow-list of raster images + PDF. The
 * stored extension is derived from the *validated* MIME, never from the
 * client-supplied filename, so a polyglot (e.g. SVG/HTML markup named `x.jpg`)
 * is rejected rather than saved under a trusted extension.
 */
class SecureUpload
{
    /**
     * Allowed real MIME types (magic-byte detected) → canonical extension.
     * SVG is deliberately absent.
     *
     * @var array<string, string>
     */
    public const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    /**
     * True when the file's real (sniffed) MIME is on the allow-list.
     */
    public static function isAllowed(SymfonyFile $file): bool
    {
        return array_key_exists(self::detectMime($file), self::ALLOWED);
    }

    /**
     * The canonical extension derived from the validated (sniffed) MIME, or null
     * when the file is not an allowed type.
     */
    public static function extensionFor(SymfonyFile $file): ?string
    {
        return self::ALLOWED[self::detectMime($file)] ?? null;
    }

    /**
     * Detect the real MIME type from the file's magic bytes. Never trusts the
     * client-supplied name/extension.
     */
    public static function detectMime(SymfonyFile $file): string
    {
        $path = $file->getRealPath() ?: $file->getPathname();

        if ($path && is_readable($path) && class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($path);
            if ($mime !== false && $mime !== '') {
                return strtolower($mime);
            }
        }

        // Fall back to Symfony's guesser (also finfo-backed) if the direct read
        // failed for any reason.
        return strtolower((string) $file->getMimeType());
    }
}
