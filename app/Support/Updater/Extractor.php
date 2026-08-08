<?php

namespace App\Support\Updater;

use PharData;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Extracts a downloaded release tar.gz to a working directory and validates the
 * extracted tree against the release-manifest.json it carries (every listed
 * file must exist and its sha256 must match). The updater validates BEFORE it
 * applies, so a truncated or tampered archive is caught while the live install
 * is still untouched.
 */
class Extractor
{
    /**
     * Extract $archivePath into a fresh subdirectory of $workDir and return the
     * extraction path.
     */
    public function extract(string $archivePath, string $workDir): string
    {
        if (! is_file($archivePath)) {
            throw new RuntimeException("Release archive not found: {$archivePath}");
        }

        $dest = rtrim($workDir, '/').'/extracted';
        if (! is_dir($dest)) {
            mkdir($dest, 0775, true);
        }

        // PharData caches an archive by its path for the life of the process,
        // so opening one that was just built in the same request can read stale
        // (empty) contents. Copy it to a unique name first to dodge the cache.
        $uniqueArchive = rtrim($workDir, '/').'/src-'.bin2hex(random_bytes(6)).'.tar.gz';
        copy($archivePath, $uniqueArchive);

        // Iterate the archive and stream each entry out by hand: PharData::
        // extractTo() has been observed to write empty files from a .tar.gz on
        // some builds. The in-archive path lives after "<archive-name>/" in the
        // entry's phar:// pathname.
        $phar = new PharData($uniqueArchive);
        $needle = basename($uniqueArchive).'/';

        $iterator = new RecursiveIteratorIterator($phar, RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($iterator as $item) {
            $full = str_replace('\\', '/', $item->getPathname());
            $pos = strpos($full, $needle);

            if ($pos === false) {
                continue;
            }

            $rel = substr($full, $pos + strlen($needle));
            $out = $dest.'/'.$rel;
            $dir = dirname($out);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            file_put_contents($out, $item->getContent());
        }

        @unlink($uniqueArchive);

        return $dest;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function readManifest(string $extractedDir): ?array
    {
        $path = rtrim($extractedDir, '/').'/release-manifest.json';

        if (! is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }

    /**
     * True when every file listed in the manifest is present in the extracted
     * tree with a matching sha256.
     */
    public function validateManifest(string $extractedDir): bool
    {
        $manifest = $this->readManifest($extractedDir);

        if ($manifest === null || ! isset($manifest['files']) || ! is_array($manifest['files'])) {
            return false;
        }

        foreach ($manifest['files'] as $file) {
            if (! is_array($file) || ! isset($file['path'], $file['sha256'])) {
                return false;
            }

            $full = rtrim($extractedDir, '/').'/'.$file['path'];

            if (! is_file($full) || hash_file('sha256', $full) !== $file['sha256']) {
                return false;
            }
        }

        return true;
    }
}
