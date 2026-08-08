<?php

namespace App\Support\Updater;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Reads the update channel and answers "is a newer release available?".
 *
 * The channel is a JSON feed (GitHub Releases API shape or a committed
 * releases.json) listing releases, each with at least a version, a download
 * url, and a sha256. Comparison is semver (version_compare). A tier-1 fleet
 * site points its channel at core; a tier-2 fork points it at its own feed —
 * this class is identical either way.
 */
class ReleaseFeed
{
    private string $channel;

    public function __construct(?string $channel = null)
    {
        $this->channel = $channel ?? (string) config('cms.update.channel', '');
    }

    /**
     * All releases from the feed, newest first. Empty on any failure.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->channel === '') {
            return [];
        }

        try {
            $response = Http::acceptJson()->timeout(15)->get($this->channel);
        } catch (Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $body = $response->json();

        // Accept both {"releases": [...]} and a bare [...] feed.
        $releases = is_array($body) && isset($body['releases']) && is_array($body['releases'])
            ? $body['releases']
            : $body;

        if (! is_array($releases)) {
            return [];
        }

        $releases = array_values(array_filter(
            $releases,
            fn ($r) => is_array($r) && isset($r['version']) && is_string($r['version'])
        ));

        usort($releases, fn ($a, $b) => version_compare($b['version'], $a['version']));

        return $releases;
    }

    /**
     * The highest-version release, or null when the feed is empty/unreachable.
     *
     * @return array<string, mixed>|null
     */
    public function latest(): ?array
    {
        return $this->all()[0] ?? null;
    }

    /**
     * The latest release strictly newer than $currentVersion, or null when the
     * site is already up to date.
     *
     * @return array<string, mixed>|null
     */
    public function available(string $currentVersion): ?array
    {
        $latest = $this->latest();

        if ($latest === null) {
            return null;
        }

        return version_compare($latest['version'], $currentVersion, '>') ? $latest : null;
    }
}
