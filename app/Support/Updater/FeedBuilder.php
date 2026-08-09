<?php

namespace App\Support\Updater;

use RuntimeException;

/**
 * Builds the flat releases.json feed the updater reads.
 *
 * ReleaseFeed expects each release as a flat object (version, url, sha256, and
 * a signature) — not the raw GitHub Releases API shape. This class turns the
 * build artifacts (the archive's .sha256, its .sig, and release-manifest.json)
 * into one such entry and merges it into the existing feed, newest first.
 *
 * Pure and filesystem-only; the CI git plumbing (checkout/commit/push of the
 * feed branch) lives in the workflow, not here.
 */
class FeedBuilder
{
    /**
     * A single flat feed entry built from the artifacts in $dir for $version.
     *
     * @return array<string, mixed>
     */
    public function entry(string $dir, string $version, string $repoUrl): array
    {
        $dir = rtrim($dir, '/');
        $repoUrl = rtrim($repoUrl, '/');
        $archive = 'release-'.$version.'.tar.gz';

        $manifest = $this->readManifest($dir.'/release-manifest.json');

        $entry = [
            'version' => $version,
            'url' => $repoUrl.'/releases/download/v'.$version.'/'.$archive,
            'sha256' => $this->readSha256($dir.'/'.$archive.'.sha256'),
            'min_php' => $manifest['min_php'] ?? null,
            'min_from_version' => $manifest['min_from_version'] ?? null,
        ];

        $sigPath = $dir.'/'.$archive.'.sig';
        if (is_file($sigPath)) {
            $entry['signature'] = trim((string) file_get_contents($sigPath));
        }

        return array_filter($entry, fn ($value) => $value !== null);
    }

    /**
     * Upsert $entry into $releases by version, then sort newest first.
     *
     * @param  array<int, mixed>  $releases  decoded feed entries (untrusted)
     * @param  array<string, mixed>  $entry
     * @return array<int, array<string, mixed>>
     */
    public function merge(array $releases, array $entry): array
    {
        $releases = array_values(array_filter(
            $releases,
            fn ($r) => is_array($r) && ($r['version'] ?? null) !== $entry['version']
        ));

        $releases[] = $entry;

        usort($releases, fn ($a, $b) => version_compare((string) $b['version'], (string) $a['version']));

        return $releases;
    }

    private function readSha256(string $path): string
    {
        if (! is_file($path)) {
            throw new RuntimeException("Missing checksum file: {$path}");
        }

        if (! preg_match('/[a-f0-9]{64}/i', (string) file_get_contents($path), $m)) {
            throw new RuntimeException("No sha256 found in: {$path}");
        }

        return strtolower($m[0]);
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Missing release manifest: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }
}
