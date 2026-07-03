<?php

namespace App\Http\Middleware;

use App\Support\SecureUpload;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/**
 * Byte-level upload guard applied to the media-library (LFM) routes
 * (FEATURE_MATRIX §21 / §7).
 *
 * Before an upload reaches UploadController, every file on the request is
 * sniffed by its magic bytes via App\Support\SecureUpload. Anything that is not
 * an allowed raster image / PDF — notably SVG and mislabeled polyglots — is
 * rejected with 422 so it is never written to storage. The LFM config
 * allow-lists still apply as a second layer; this is the anti-polyglot floor.
 */
class ValidateUploadBytes
{
    public function handle(Request $request, Closure $next)
    {
        foreach ($this->uploadedFiles($request) as $file) {
            if (! SecureUpload::isAllowed($file)) {
                return response()->json([
                    'error' => trans('validation.uploaded', ['attribute' => 'file'])
                        ?: 'The uploaded file type is not allowed.',
                ], 422);
            }
        }

        return $next($request);
    }

    /**
     * All uploaded files on the request, flattened (LFM may send `upload[]`).
     *
     * @return array<int, SymfonyFile>
     */
    private function uploadedFiles(Request $request): array
    {
        $files = [];
        $all = $request->allFiles();

        array_walk_recursive($all, function ($file) use (&$files) {
            if ($file instanceof SymfonyFile) {
                $files[] = $file;
            }
        });

        return $files;
    }
}
