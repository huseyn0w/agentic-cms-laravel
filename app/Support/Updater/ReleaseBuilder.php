<?php

namespace App\Support\Updater;

use FilesystemIterator;
use PharData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Packages a prebuilt core release from a source tree.
 *
 * Walks the source root, keeps ONLY core-owned paths (per PathManifest), writes
 * a release-manifest.json (version, min_php, min_from_version, per-file sha256),
 * bundles everything into a tar.gz, and writes the archive's sha256 alongside.
 * Site-owned and preserve paths are never included — a fork's zone and runtime
 * state stay out of the release. Used by the CI release job (cms:build-release).
 *
 * Uses PharData rather than an external tar binary so it is testable and works
 * anywhere PHP does (no shell dependency). Signing is a separate step (Signer).
 */
class ReleaseBuilder
{
    /** Minimum PHP the release requires; the updater refuses to apply below it. */
    private const MIN_PHP = '8.2.0';

    /** Oldest installed version this release can upgrade from. */
    private const MIN_FROM_VERSION = '0.0.0';

    public function __construct(private PathManifest $manifest) {}

    /**
     * Build a release from $sourceRoot into $outputDir for $version.
     *
     * @return array{version: string, archive: string, manifest: string, sha256: string, files: int}
     */
    public function build(string $sourceRoot, string $outputDir, string $version): array
    {
        $sourceRoot = rtrim($sourceRoot, '/');
        $outputDir = rtrim($outputDir, '/');

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $files = $this->collectCoreFiles($sourceRoot);

        $manifestData = [
            'version' => $version,
            'min_php' => self::MIN_PHP,
            'min_from_version' => self::MIN_FROM_VERSION,
            'files' => array_map(fn (array $f) => [
                'path' => $f['rel'],
                'sha256' => hash_file('sha256', $f['abs']),
            ], $files),
        ];

        $manifestPath = $outputDir.'/release-manifest.json';
        file_put_contents(
            $manifestPath,
            json_encode($manifestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $archivePath = $this->buildArchive($outputDir, $version, $files, $manifestPath);

        $sha256 = hash_file('sha256', $archivePath);
        file_put_contents($archivePath.'.sha256', $sha256);

        return [
            'version' => $version,
            'archive' => $archivePath,
            'manifest' => $manifestPath,
            'sha256' => $sha256,
            'files' => count($files),
        ];
    }

    /**
     * @param  list<array{rel: string, abs: string}>  $files
     */
    private function buildArchive(string $outputDir, string $version, array $files, string $manifestPath): string
    {
        $tarPath = $outputDir.'/release-'.$version.'.tar';

        // PharData refuses to overwrite; clear any prior build artifacts.
        foreach ([$tarPath, $tarPath.'.gz'] as $stale) {
            if (is_file($stale)) {
                unlink($stale);
            }
        }

        $phar = new PharData($tarPath);

        foreach ($files as $file) {
            // addFromString (not addFile) so the file's contents are written
            // into the archive eagerly and reliably.
            $phar->addFromString($file['rel'], (string) file_get_contents($file['abs']));
        }

        // The manifest travels inside the archive so the updater can validate
        // extracted files against it before applying.
        $phar->addFromString('release-manifest.json', (string) file_get_contents($manifestPath));

        $phar->compress(\Phar::GZ);

        // compress() writes a sibling .tar.gz and leaves the .tar behind.
        unset($phar);
        if (is_file($tarPath)) {
            unlink($tarPath);
        }

        return $tarPath.'.gz';
    }

    /**
     * Walk the source tree and collect every core-owned file.
     *
     * @return list<array{rel: string, abs: string}>
     */
    private function collectCoreFiles(string $sourceRoot): array
    {
        $collected = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                continue;
            }

            $abs = $item->getPathname();
            $rel = ltrim(str_replace('\\', '/', substr($abs, strlen($sourceRoot))), '/');

            if ($this->manifest->isCoreOwned($rel)) {
                $collected[] = ['rel' => $rel, 'abs' => $abs];
            }
        }

        // Deterministic order so a rebuild of the same tree is reproducible.
        usort($collected, fn ($a, $b) => strcmp($a['rel'], $b['rel']));

        return $collected;
    }
}
