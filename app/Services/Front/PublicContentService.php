<?php

namespace App\Services\Front;

use App\Http\Models\ContentRecord;
use App\Repositories\ContentRepository;
use App\Support\Content\ContentType;
use App\Support\Content\ContentTypeRegistry;
use App\Support\Content\Field;

/**
 * Front read-model for public content types. Resolves a public+enabled type from
 * the registry and maps its rows to plain arrays for the generic public renderer.
 * The only DB touch is delegated to ContentRepository (arch LayeringTest).
 */
class PublicContentService
{
    public function __construct(
        private ContentTypeRegistry $registry,
        private ContentRepository $repo,
    ) {}

    /**
     * The content type for $slug when it is both enabled (registry) and public;
     * null otherwise (disabled plugin, unknown slug, or admin-only type).
     */
    public function publicType(string $slug): ?ContentType
    {
        $type = $this->registry->get($slug);

        return $type !== null && $type->isPublic ? $type : null;
    }

    /**
     * Published rows for the index, ordered by sort_order (when present) then id.
     * Richtext is omitted to keep the listing payload small.
     *
     * @return list<array<string, mixed>>
     */
    public function listItems(ContentType $type): array
    {
        $rows = $this->repo->publicList($type->table, $this->orderColumn($type), $this->onlyPublished($type));

        return array_map(fn (ContentRecord $r) => $this->present($type, $r, includeRichtext: false), $rows);
    }

    /**
     * A single published row by id, with every field (including richtext).
     *
     * @return array<string, mixed>|null
     */
    public function findItem(ContentType $type, int $id): ?array
    {
        $row = $this->repo->publicFind($type->table, $id, $this->onlyPublished($type));

        return $row !== null ? $this->present($type, $row, includeRichtext: true) : null;
    }

    private function orderColumn(ContentType $type): string
    {
        return $type->field('sort_order') !== null ? 'sort_order' : 'id';
    }

    private function onlyPublished(ContentType $type): bool
    {
        // Only filter when the schema actually has a status column; types without
        // one (e.g. experience) show every row.
        return $type->field('status') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ContentType $type, ContentRecord $record, bool $includeRichtext): array
    {
        $item = ['id' => $record->getKey()];

        foreach ($type->fields as $field) {
            if (! $includeRichtext && $field->type === Field::RICHTEXT) {
                continue;
            }
            $item[$field->name] = $record->{$field->name};
        }

        return $item;
    }
}
