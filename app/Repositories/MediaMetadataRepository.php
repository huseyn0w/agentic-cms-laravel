<?php

namespace App\Repositories;

use App\Http\Models\MediaMetadata;

/**
 * Repository for per-asset media metadata (FEATURE_MATRIX §7). The single home
 * for media_metadata query building; consumed by CaptureMediaMetadata (on
 * upload) and CPanelMediaService (edit UI).
 */
class MediaMetadataRepository
{
    /**
     * The metadata row for a storage-relative path, or null.
     */
    public function findByPath(string $path): ?MediaMetadata
    {
        return MediaMetadata::where('path', $path)->first();
    }

    /**
     * Upsert the metadata for a path, merging the given attributes. Only
     * non-null attributes are written, so a partial edit never wipes fields
     * populated elsewhere (e.g. technical fields captured on upload).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function upsert(string $path, array $attributes): MediaMetadata
    {
        return MediaMetadata::forPath($path, $attributes);
    }

    /**
     * Write the given attributes verbatim for a path (creating the row if
     * needed), including nulls — so an editorial edit can clear a field. Used
     * for the alt/title/caption edit path, distinct from the null-merging
     * upsert() used by on-upload technical capture.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function setEditorial(string $path, array $attributes): MediaMetadata
    {
        return MediaMetadata::updateOrCreate(['path' => $path], $attributes);
    }
}
