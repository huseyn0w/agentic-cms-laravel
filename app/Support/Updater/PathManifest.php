<?php

namespace App\Support\Updater;

/**
 * Classifies repo paths as core-owned, site-owned, or preserve/state.
 *
 * This is the safety boundary the WordPress-style updater relies on: it may
 * overwrite ONLY core-owned paths and must never touch site-owned or preserve
 * paths. Ownership is resolved by longest-prefix match, so a nested site prefix
 * (app/Site) wins over its core parent (app). On a tie, "protected" wins over
 * "core", and anything unclassified defaults to protected — the updater errs on
 * the side of leaving files alone.
 *
 * Filesystem classification is not data access, so this lives in Support and is
 * safe to use from services (the arch layering test allows it).
 */
class PathManifest
{
    /** @var list<string> */
    private array $core;

    /** @var list<string> */
    private array $site;

    /** @var list<string> */
    private array $preserve;

    /**
     * @param  array{core?: list<string>, site?: list<string>, preserve?: list<string>}  $paths
     */
    public function __construct(array $paths)
    {
        $this->core = array_map($this->normalize(...), $paths['core'] ?? []);
        $this->site = array_map($this->normalize(...), $paths['site'] ?? []);
        $this->preserve = array_map($this->normalize(...), $paths['preserve'] ?? []);
    }

    /**
     * The owner of a path: 'core', 'site', or 'preserve'.
     */
    public function owner(string $path): string
    {
        $path = $this->normalize($path);

        $best = null;
        $bestLength = -1;

        // Order matters only for ties: evaluate protected sets (site, preserve)
        // before core so an equal-length match resolves to protected.
        foreach ([['site', $this->site], ['preserve', $this->preserve], ['core', $this->core]] as [$owner, $prefixes]) {
            foreach ($prefixes as $prefix) {
                if (! $this->matches($path, $prefix)) {
                    continue;
                }

                $length = strlen($prefix);

                if ($length > $bestLength) {
                    $bestLength = $length;
                    $best = $owner;
                }
            }
        }

        // Unclassified paths are left alone (never overwritten).
        return $best ?? 'preserve';
    }

    public function isCoreOwned(string $path): bool
    {
        return $this->owner($path) === 'core';
    }

    public function isSiteOwned(string $path): bool
    {
        return $this->owner($path) === 'site';
    }

    /**
     * True when a path must never be overwritten (site-owned or preserve/state).
     */
    public function isProtected(string $path): bool
    {
        return $this->owner($path) !== 'core';
    }

    /** @return list<string> */
    public function coreOwnedPrefixes(): array
    {
        return $this->core;
    }

    /** @return list<string> */
    public function siteOwnedPrefixes(): array
    {
        return $this->site;
    }

    /** @return list<string> */
    public function preservePrefixes(): array
    {
        return $this->preserve;
    }

    /**
     * A prefix matches when the path equals it or sits under it as a directory.
     */
    private function matches(string $path, string $prefix): bool
    {
        return $path === $prefix || str_starts_with($path, $prefix.'/');
    }

    private function normalize(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }
}
