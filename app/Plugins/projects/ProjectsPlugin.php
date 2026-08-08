<?php

namespace App\Plugins\projects;

use App\Plugins\Contracts\PluginInterface;
use App\Support\Content\ContentType;
use App\Support\Content\Field;
use App\Support\Content\RegistersContentTypes;
use App\Support\Hooks;

/**
 * Portfolio / projects as an OPTIONAL content type. Disabled by default; a fork
 * (e.g. helloelman) enables it to edit its portfolio in the admin instead of
 * hardcoding it in the theme. Declares a schema on the content-type framework —
 * no bespoke controller/UI. Public rendering is the theme's job.
 */
class ProjectsPlugin implements PluginInterface, RegistersContentTypes
{
    public function slug(): string
    {
        return 'projects';
    }

    public function name(): string
    {
        return 'Projects';
    }

    public function description(): string
    {
        return 'Adds a Projects (portfolio) content type: title, image, link, and description.';
    }

    public function boot(Hooks $hooks): void
    {
        // No content-filter hooks; the content type is registered via the
        // ContentTypeRegistry (RegistersContentTypes), not here.
    }

    /**
     * @return list<ContentType>
     */
    public function contentTypes(): array
    {
        return [
            new ContentType(
                slug: 'projects',
                labels: ['en' => 'Projects', 'de' => 'Projekte', 'ru' => 'Проекты'],
                table: 'projects',
                fields: [
                    new Field('title', 'Title', Field::TEXT, ['required', 'string', 'max:255'], listVisible: true),
                    new Field('category', 'Category', Field::TEXT, ['nullable', 'string', 'max:120'], listVisible: true),
                    new Field('excerpt', 'Excerpt', Field::TEXTAREA, ['nullable', 'string', 'max:1000']),
                    new Field('content', 'Description', Field::RICHTEXT, ['nullable', 'string']),
                    new Field('thumbnail', 'Image', Field::IMAGE, ['nullable', 'string', 'max:2000']),
                    new Field('external_url', 'Link', Field::URL, ['nullable', 'string', 'max:2000']),
                    new Field('sort_order', 'Sort order', Field::NUMBER, ['nullable', 'integer', 'min:0']),
                    new Field('status', 'Status', Field::SELECT, ['nullable', 'in:published,draft'], options: [
                        'published' => 'Published',
                        'draft' => 'Draft',
                    ]),
                ],
            ),
        ];
    }
}
