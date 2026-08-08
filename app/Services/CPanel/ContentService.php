<?php

namespace App\Services\CPanel;

use App\Http\Models\ContentRecord;
use App\Repositories\ContentRepository;
use App\Support\Content\ContentType;
use App\Support\Content\Field;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Generic domain service for plugin content types. Builds validation rules and
 * searchable columns from the type's schema, and delegates all persistence to
 * ContentRepository (arch keeps the ORM out of here).
 */
class ContentService
{
    public function __construct(private ContentRepository $repo) {}

    public function list(ContentType $type, ?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->repo->paginate($type->table, $search, $this->searchable($type), $perPage);
    }

    public function find(ContentType $type, int $id): ?ContentRecord
    {
        return $this->repo->find($type->table, $id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(ContentType $type, array $data): ContentRecord
    {
        return $this->repo->create($type->table, $this->onlySchema($type, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ContentType $type, ContentRecord $record, array $data): void
    {
        $this->repo->update($record, $this->onlySchema($type, $data));
    }

    public function delete(ContentType $type, int $id): void
    {
        $this->repo->delete($type->table, $id);
    }

    /**
     * Validation rules keyed by field name (from the schema).
     *
     * @return array<string, list<string>>
     */
    public function rules(ContentType $type): array
    {
        return $type->rules();
    }

    /**
     * Keep only known schema fields, and coerce booleans so an unchecked box
     * persists as false rather than being dropped.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function onlySchema(ContentType $type, array $data): array
    {
        $clean = [];

        foreach ($type->fields as $field) {
            if ($field->type === Field::BOOLEAN) {
                $clean[$field->name] = (bool) ($data[$field->name] ?? false);
            } elseif (array_key_exists($field->name, $data)) {
                $clean[$field->name] = $data[$field->name];
            }
        }

        return $clean;
    }

    /**
     * @return list<string>
     */
    private function searchable(ContentType $type): array
    {
        return array_values(array_map(
            fn (Field $f) => $f->name,
            array_filter($type->fields, fn (Field $f) => in_array($f->type, [Field::TEXT, Field::TEXTAREA, Field::URL], true)),
        ));
    }
}
