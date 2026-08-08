<?php

namespace App\Support\Updater;

use RuntimeException;

/**
 * Applies an extracted, validated release onto the live install.
 *
 * For each file the manifest lists, the file is copied into the target tree via
 * a temp-write + atomic rename, so a crash mid-write never leaves a half-written
 * core file. The PathManifest is enforced a second time here: the Applier
 * refuses to write anything that is not core-owned, so a malicious or malformed
 * manifest can never touch a site/preserve path (app/Site, .env, storage,
 * uploads). Returns the list of applied relative paths (for the audit log).
 */
class Applier
{
    public function __construct(private PathManifest $manifest) {}

    /**
     * @return list<string> the relative paths written
     */
    public function apply(string $extractedDir, string $targetRoot): array
    {
        $manifest = json_decode(
            (string) file_get_contents(rtrim($extractedDir, '/').'/release-manifest.json'),
            true
        );

        if (! is_array($manifest) || ! isset($manifest['files']) || ! is_array($manifest['files'])) {
            throw new RuntimeException('Cannot apply: release manifest is missing or invalid.');
        }

        $extractedDir = rtrim($extractedDir, '/');
        $targetRoot = rtrim($targetRoot, '/');
        $applied = [];

        foreach ($manifest['files'] as $file) {
            $rel = is_array($file) ? ($file['path'] ?? null) : null;

            if (! is_string($rel) || $rel === '') {
                continue;
            }

            // Safety boundary: never write outside core-owned paths, even if the
            // manifest claims to. Protects app/Site, .env, storage, uploads.
            if (! $this->manifest->isCoreOwned($rel)) {
                continue;
            }

            $source = $extractedDir.'/'.$rel;
            if (! is_file($source)) {
                continue;
            }

            $this->writeAtomic($source, $targetRoot.'/'.$rel);
            $applied[] = $rel;
        }

        return $applied;
    }

    /**
     * Copy $source to $target atomically: write to a temp sibling then rename.
     */
    private function writeAtomic(string $source, string $target): void
    {
        $dir = dirname($target);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $temp = $target.'.new-'.bin2hex(random_bytes(4));

        if (copy($source, $temp) === false) {
            throw new RuntimeException("Failed to stage update file: {$target}");
        }

        if (rename($temp, $target) === false) {
            @unlink($temp);
            throw new RuntimeException("Failed to apply update file: {$target}");
        }
    }
}
