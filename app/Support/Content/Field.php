<?php

namespace App\Support\Content;

/**
 * One field in a content-type schema. Drives BOTH the admin form (the field
 * renderer picks a widget from $type) and backend validation ($rules), so a
 * plugin declares a field once and gets a form + validation for free.
 */
class Field
{
    public const TEXT = 'text';

    public const TEXTAREA = 'textarea';

    public const RICHTEXT = 'richtext';

    public const IMAGE = 'image';

    public const URL = 'url';

    public const DATE = 'date';

    public const NUMBER = 'number';

    public const BOOLEAN = 'boolean';

    public const SELECT = 'select';

    /**
     * @param  string  $name  Column name (also the form field name).
     * @param  string  $label  Human label shown in the admin.
     * @param  string  $type  One of the self:: constants.
     * @param  list<string>  $rules  Laravel validation rules for this field.
     * @param  array<string, string>  $options  value=>label map for SELECT.
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $type = self::TEXT,
        public array $rules = ['nullable', 'string'],
        public array $options = [],
        public bool $listVisible = false,
    ) {}

    /**
     * JSON shape handed to the React form/list — no validation rules leak to the
     * client (they are enforced server-side).
     *
     * @return array{name: string, label: string, type: string, options: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'options' => $this->options,
        ];
    }
}
