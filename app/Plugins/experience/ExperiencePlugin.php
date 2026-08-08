<?php

namespace App\Plugins\experience;

use App\Plugins\Contracts\PluginInterface;
use App\Support\Content\ContentType;
use App\Support\Content\Field;
use App\Support\Content\RegistersContentTypes;
use App\Support\Hooks;

/**
 * Work experience / resume as an OPTIONAL content type. Disabled by default; a
 * personal-brand fork (e.g. helloelman) enables it to edit its resume in the
 * admin instead of hardcoding it in the theme. A thin schema declaration on the
 * content-type framework; public rendering is the theme's job.
 */
class ExperiencePlugin implements PluginInterface, RegistersContentTypes
{
    public function slug(): string
    {
        return 'experience';
    }

    public function name(): string
    {
        return 'Experience';
    }

    public function description(): string
    {
        return 'Adds an Experience (resume) content type: company, role, period, and description.';
    }

    public function boot(Hooks $hooks): void
    {
        // Registered via the ContentTypeRegistry, not through content hooks.
    }

    /**
     * @return list<ContentType>
     */
    public function contentTypes(): array
    {
        return [
            new ContentType(
                slug: 'experience',
                labels: ['en' => 'Experience', 'de' => 'Erfahrung', 'ru' => 'Опыт'],
                table: 'experiences',
                fields: [
                    new Field('company', 'Company', Field::TEXT, ['required', 'string', 'max:255'], listVisible: true),
                    new Field('position', 'Position', Field::TEXT, ['required', 'string', 'max:255'], listVisible: true),
                    new Field('company_url', 'Company URL', Field::URL, ['nullable', 'string', 'max:2000']),
                    new Field('period', 'Period', Field::TEXT, ['nullable', 'string', 'max:120'], listVisible: true),
                    new Field('description', 'Description', Field::RICHTEXT, ['nullable', 'string']),
                    new Field('sort_order', 'Sort order', Field::NUMBER, ['nullable', 'integer', 'min:0']),
                ],
            ),
        ];
    }
}
