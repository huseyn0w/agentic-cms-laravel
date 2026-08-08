<?php

namespace App\Repositories;

use App\Http\Models\Redirect;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * All ORM access for managed redirects. Service/controller layers call only
 * these methods (arch LayeringTest keeps Eloquent out of them).
 */
class RedirectRepository extends BaseRepository
{
    public function __construct(Redirect $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    /**
     * The full source→redirect map, for the resolver cache.
     *
     * @return Collection<string, array{target: string, status: int}>
     */
    public function map(): Collection
    {
        return $this->model::query()
            ->get(['source_path', 'target', 'status_code'])
            ->mapWithKeys(fn (Redirect $r) => [
                $r->source_path => ['target' => $r->target, 'status' => $r->status_code],
            ]);
    }

    public function findBySource(string $sourcePath): ?Redirect
    {
        return $this->model::query()->where('source_path', $sourcePath)->first();
    }

    public function find(int $id): ?Redirect
    {
        return $this->model::query()->find($id);
    }

    /**
     * Create or update a redirect keyed by source_path.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function upsertBySource(string $sourcePath, array $attributes): Redirect
    {
        return $this->model::query()->updateOrCreate(
            ['source_path' => $sourcePath],
            $attributes,
        );
    }

    public function incrementHits(string $sourcePath): void
    {
        $this->model::query()->where('source_path', $sourcePath)->increment('hits');
    }

    public function paginateFiltered(?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->model::query()
            ->when($search, fn ($q) => $q->where('source_path', 'like', '%'.$search.'%')
                ->orWhere('target', 'like', '%'.$search.'%'))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function remove(Redirect $redirect): void
    {
        $redirect->delete();
    }
}
