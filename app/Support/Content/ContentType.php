<?php

namespace App\Support\Content;

/**
 * A plugin-declared content type: its own table plus a field schema. The core
 * generic CRUD (controller/service/repository + the schema-driven React admin)
 * operates on any registered ContentType by slug, so a new optional content
 * type ships as a plugin with no frontend rebuild.
 */
class ContentType
{
    /**
     * @param  string  $slug  URL/registry key, e.g. "projects".
     * @param  array<string, string>  $labels  locale=>label (must include a fallback).
     * @param  string  $table  Backing table name.
     * @param  list<Field>  $fields  Field schema.
     * @param  string  $permission  Ability gating admin access (default manage_content).
     * @param  bool  $isPublic  Whether the type has a public front-end (index +
     *                          detail rendered by the core generic renderer). Off
     *                          by default: most types are admin/data only.
     */
    public function __construct(
        public string $slug,
        public array $labels,
        public string $table,
        public array $fields,
        public string $permission = 'manage_content',
        public bool $isPublic = false,
    ) {}

    /** Does the schema have a richtext field, i.e. is a detail page meaningful? */
    public function hasDetail(): bool
    {
        foreach ($this->fields as $field) {
            if ($field->type === Field::RICHTEXT) {
                return true;
            }
        }

        return false;
    }

    /**
     * The label for the given locale, falling back to English then any value.
     */
    public function label(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->labels[$locale]
            ?? $this->labels['en']
            ?? (array_values($this->labels)[0] ?? $this->slug);
    }

    /** @return list<string> field names shown as list columns */
    public function listColumns(): array
    {
        $columns = array_values(array_filter(
            $this->fields,
            fn (Field $f) => $f->listVisible,
        ));

        // Default to the first two text-ish fields when none are flagged.
        if ($columns === []) {
            $columns = array_slice(array_filter(
                $this->fields,
                fn (Field $f) => in_array($f->type, [Field::TEXT, Field::URL], true),
            ), 0, 2);
        }

        return array_map(fn (Field $f) => $f->name, $columns);
    }

    public function field(string $name): ?Field
    {
        foreach ($this->fields as $field) {
            if ($field->name === $name) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Validation rules keyed by field name, from the schema.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach ($this->fields as $field) {
            $rules[$field->name] = $field->rules;
        }

        return $rules;
    }

    /**
     * Client-safe projection (labels resolved for the current locale).
     *
     * @return array{slug: string, label: string, fields: list<array<string, mixed>>, columns: list<string>}
     */
    public function toArray(?string $locale = null): array
    {
        return [
            'slug' => $this->slug,
            'label' => $this->label($locale),
            'fields' => array_map(fn (Field $f) => $f->toArray(), $this->fields),
            'columns' => $this->listColumns(),
        ];
    }
}
