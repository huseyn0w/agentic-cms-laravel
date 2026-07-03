<?php

namespace App\Listeners;

use App\Http\Models\MediaMetadata;
use UniSharp\LaravelFilemanager\Events\FileWasUploaded;

/**
 * FEATURE_MATRIX §7 — capture per-asset media metadata on upload.
 *
 * When LFM finishes writing an upload it fires FileWasUploaded with the file's
 * absolute path. We normalise that to a storage-relative path and persist the
 * technical metadata (mime, size, and — for raster images — width/height) so
 * the asset has a metadata row from the moment it exists. Editorial fields
 * (alt/title/caption) are filled in later from the media UI and are never
 * clobbered here (MediaMetadata::forPath only merges non-null values).
 */
class CaptureMediaMetadata
{
    public function handle(FileWasUploaded $event): void
    {
        $absolute = (string) $event->path();

        if ($absolute === '' || ! is_file($absolute)) {
            return;
        }

        $attributes = [
            'mime' => $this->mime($absolute),
            'size' => @filesize($absolute) ?: null,
        ];

        $dimensions = @getimagesize($absolute);
        if (is_array($dimensions)) {
            $attributes['width'] = $dimensions[0];
            $attributes['height'] = $dimensions[1];
        }

        MediaMetadata::forPath($this->relativePath($absolute), $attributes);
    }

    private function mime(string $path): ?string
    {
        if (class_exists(\finfo::class)) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
            if ($mime !== false && $mime !== '') {
                return $mime;
            }
        }

        return null;
    }

    /**
     * Reduce an absolute upload path to a stable, storage-relative key. Anchors
     * on the LFM base directory (default "public/uploads") so the same asset
     * maps to the same key regardless of the deployment's absolute base path.
     */
    private function relativePath(string $absolute): string
    {
        $absolute = str_replace('\\', '/', $absolute);
        $anchor = trim((string) config('lfm.base_directory', 'public/uploads'), '/');

        if ($anchor !== '' && ($pos = strpos($absolute, $anchor)) !== false) {
            return ltrim(substr($absolute, $pos + strlen($anchor)), '/');
        }

        return ltrim(basename($absolute), '/');
    }
}
