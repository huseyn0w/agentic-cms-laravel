<?php

namespace App\Http\Controllers\CPanel;

use App\Services\CPanel\CPanelMediaService;
use Illuminate\Http\Request;

class CPanelMediaController extends CPanelBaseController
{
    public function __construct(private CPanelMediaService $media)
    {
        parent::__construct();
    }

    public function index()
    {
        return view('cpanel.media.media');
    }

    /**
     * Read the stored metadata for a media asset (by storage-relative path),
     * for the edit UI. Returns an empty payload when none has been recorded yet.
     */
    public function metadata(Request $request)
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:2048'],
        ]);

        return response()->json([
            'path' => $data['path'],
            'metadata' => $this->media->metadataFor($data['path']),
        ]);
    }

    /**
     * Persist editorial metadata (alt/title/caption) for a media asset
     * (FEATURE_MATRIX §7). Upserts by path so it works whether or not the
     * on-upload technical capture already created the row.
     */
    public function updateMetadata(Request $request)
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:2048'],
            'alt' => ['nullable', 'string', 'max:1024'],
            'title' => ['nullable', 'string', 'max:1024'],
            'caption' => ['nullable', 'string', 'max:4096'],
        ]);

        $meta = $this->media->saveEditorial(
            $data['path'],
            $data['alt'] ?? null,
            $data['title'] ?? null,
            $data['caption'] ?? null,
        );

        return response()->json(['saved' => true, 'metadata' => $meta]);
    }
}
