<?php

namespace App\Services;

use App\Http\Models\Redirect;
use App\Repositories\RedirectRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves managed redirects (for the middleware) and owns their CRUD + import.
 * The source→target map is cached so the hot path (every front request) does one
 * cache read, not a query. All ORM access is delegated to the repository.
 */
class RedirectService
{
    public const CACHE_KEY = 'cms.redirects.map';

    public function __construct(private RedirectRepository $repo) {}

    /**
     * Resolve an incoming path to a redirect target, or null when there is no
     * match. Guards against a self-redirect loop (source == target).
     *
     * @return array{target: string, status: int}|null
     */
    public function resolve(string $path): ?array
    {
        $source = Redirect::normalizePath($path);
        $map = $this->cachedMap();

        if (! isset($map[$source])) {
            return null;
        }

        $entry = $map[$source];

        // Never redirect a path to itself — that would loop forever.
        if (Redirect::normalizePath($entry['target']) === $source) {
            return null;
        }

        return $entry;
    }

    public function recordHit(string $path): void
    {
        $this->repo->incrementHits(Redirect::normalizePath($path));
    }

    /**
     * @return array<string, array{target: string, status: int}>
     */
    public function cachedMap(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => $this->repo->map()->all());
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function list(?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->repo->paginateFiltered($search, $perPage);
    }

    /**
     * Create or update a redirect. Source is normalized; a full-URL or path
     * target is accepted as-is.
     */
    public function save(string $source, string $target, int $status = 301): Redirect
    {
        $redirect = $this->repo->upsertBySource(Redirect::normalizePath($source), [
            'target' => trim($target),
            'status_code' => in_array($status, [301, 302], true) ? $status : 301,
        ]);

        $this->flushCache();

        return $redirect;
    }

    public function delete(int $id): void
    {
        $redirect = $this->repo->find($id);

        if ($redirect !== null) {
            $this->repo->remove($redirect);
            $this->flushCache();
        }
    }

    /**
     * Bulk import a list of [source, target, status] rows. Returns the count
     * imported. Rows missing a source or target are skipped.
     *
     * @param  iterable<array{0?: string, 1?: string, 2?: int|string}>  $rows
     */
    public function import(iterable $rows): int
    {
        $count = 0;

        foreach ($rows as $row) {
            $source = isset($row[0]) ? trim((string) $row[0]) : '';
            $target = isset($row[1]) ? trim((string) $row[1]) : '';

            if ($source === '' || $target === '') {
                continue;
            }

            $status = isset($row[2]) ? (int) $row[2] : 301;

            $this->repo->upsertBySource(Redirect::normalizePath($source), [
                'target' => $target,
                'status_code' => in_array($status, [301, 302], true) ? $status : 301,
            ]);
            $count++;
        }

        $this->flushCache();

        return $count;
    }
}
