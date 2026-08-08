<?php

namespace Tests\Unit\Support;

use App\Support\Content\ContentType;
use App\Support\Content\Field;
use Tests\TestCase;

/**
 * The content-type schema drives both the admin form and validation, so its
 * shape (fields, rules, labels, list columns) must be exact.
 */
class ContentTypeTest extends TestCase
{
    private function type(): ContentType
    {
        return new ContentType(
            slug: 'projects',
            labels: ['en' => 'Projects', 'de' => 'Projekte', 'ru' => 'Проекты'],
            table: 'projects',
            fields: [
                new Field('title', 'Title', Field::TEXT, ['required', 'string', 'max:255'], listVisible: true),
                new Field('body', 'Body', Field::RICHTEXT, ['nullable', 'string']),
                new Field('featured', 'Featured', Field::BOOLEAN, ['boolean']),
            ],
        );
    }

    public function test_field_to_array_hides_rules_from_the_client(): void
    {
        $field = new Field('title', 'Title', Field::TEXT, ['required', 'string']);

        $this->assertSame(
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'options' => []],
            $field->toArray(),
        );
    }

    public function test_rules_are_collected_from_the_schema(): void
    {
        $rules = $this->type()->rules();

        $this->assertSame(['required', 'string', 'max:255'], $rules['title']);
        $this->assertSame(['boolean'], $rules['featured']);
    }

    public function test_label_falls_back_across_locales(): void
    {
        $type = $this->type();

        $this->assertSame('Проекты', $type->label('ru'));
        $this->assertSame('Projects', $type->label('en'));
        // Unknown locale → English fallback.
        $this->assertSame('Projects', $type->label('fr'));
    }

    public function test_list_columns_use_flagged_fields(): void
    {
        // Only `title` is listVisible.
        $this->assertSame(['title'], $this->type()->listColumns());
    }

    public function test_to_array_projects_a_client_shape(): void
    {
        $array = $this->type()->toArray('en');

        $this->assertSame('projects', $array['slug']);
        $this->assertSame('Projects', $array['label']);
        $this->assertSame(['title'], $array['columns']);
        $this->assertCount(3, $array['fields']);
        $this->assertSame('richtext', $array['fields'][1]['type']);
    }
}
