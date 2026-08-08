<?php

namespace App\Support\Updater;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Downloads a release archive and verifies its sha256 before the updater does
 * anything with it. A checksum mismatch (truncated download, wrong file, MITM)
 * aborts here, while the live install is still untouched. Signature verification
 * is a separate, mandatory-in-prod step (Verifier), done by UpdateService.
 */
class Downloader
{
    /**
     * Download $url to $destPath and verify it against $expectedSha256.
     * Returns the path on success; throws on any failure.
     */
    public function download(string $url, string $expectedSha256, string $destPath): string
    {
        $dir = dirname($destPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $response = Http::timeout(300)->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("Download failed ({$response->status()}): {$url}");
        }

        file_put_contents($destPath, $response->body());

        $actual = hash_file('sha256', $destPath);

        if (! hash_equals(strtolower($expectedSha256), (string) $actual)) {
            @unlink($destPath);
            throw new RuntimeException('Checksum mismatch: the download is corrupt or tampered.');
        }

        return $destPath;
    }
}
