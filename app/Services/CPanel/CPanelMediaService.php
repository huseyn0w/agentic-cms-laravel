<?php

namespace App\Services\CPanel;

use App\Http\Models\MediaMetadata;
use App\Repositories\MediaMetadataRepository;

/**
 * Admin media service (FEATURE_MATRIX §7): reads/writes per-asset metadata via
 * the repository. Controllers delegate here — no data access in the controller.
 */
class CPanelMediaService
{
    public function __construct(private MediaMetadataRepository $repo) {}

    /**
     * Stored metadata for an asset path (null when none recorded yet).
     */
    public function metadataFor(string $path): ?MediaMetadata
    {
        return $this->repo->findByPath($path);
    }

    /**
     * Persist editorial metadata (alt/title/caption) for an asset path.
     */
    public function saveEditorial(string $path, ?string $alt, ?string $title, ?string $caption): MediaMetadata
    {
        return $this->repo->setEditorial($path, [
            'alt' => $alt,
            'title' => $title,
            'caption' => $caption,
        ]);
    }
}
