<?php

namespace App\Repositories;

use App\Http\Models\ContentRecord;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Generic ORM access for plugin content types. One repository serves every
 * registered type by binding the generic ContentRecord model to the type's
 * table at runtime. The only layer that touches Eloquent for content types
 * (arch LayeringTest). Does not extend BaseRepository — no translatable
 * machinery is needed and the base's typed signatures don't fit a table-agnostic
 * store.
 */
class ContentRepository
{
    /**
     * @param  list<string>  $searchable  columns to LIKE-match against $search
     */
    public function paginate(string $table, ?string $search, array $searchable, int $perPage): LengthAwarePaginator
    {
        return ContentRecord::forTable($table)->newQuery()
            ->when($search && $searchable !== [], fn ($q) => $q->where(function ($sub) use ($search, $searchable) {
                foreach ($searchable as $i => $col) {
                    $i === 0
                        ? $sub->where($col, 'like', '%'.$search.'%')
                        : $sub->orWhere($col, 'like', '%'.$search.'%');
                }
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(string $table, int $id): ?ContentRecord
    {
        return ContentRecord::forTable($table)->newQuery()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(string $table, array $data): ContentRecord
    {
        $record = ContentRecord::forTable($table);
        $record->forceFill($data)->save();

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ContentRecord $record, array $data): void
    {
        $record->forceFill($data)->save();
    }

    public function delete(string $table, int $id): void
    {
        ContentRecord::forTable($table)->newQuery()->where('id', $id)->delete();
    }
}
