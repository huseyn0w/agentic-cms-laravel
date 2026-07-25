<?php

use App\Support\I18n\TranslationDictionary;

function dict(): TranslationDictionary
{
    return app(TranslationDictionary::class);
}

it('flattens a slash-nested group into a verbatim dotted key', function () {
    $messages = dict()->forLocale('en');

    expect($messages)->toHaveKey('cpanel/categories.add_new_category')
        ->and($messages['cpanel/categories.add_new_category'])->toBe('Add new category');
});

it('flattens nested arrays with dot separators', function () {
    $messages = dict()->forLocale('en');

    expect($messages)->toHaveKey('validation.min.string');
});

it('normalizes Laravel :placeholders to i18next {{placeholders}}', function () {
    $messages = dict()->forLocale('en');

    expect($messages['validation.accepted'])
        ->toContain('{{attribute}}')
        ->not->toContain(':attribute');
});

it('builds a non-empty dictionary for every supported locale', function (string $locale) {
    $messages = dict()->forLocale($locale);

    expect($messages)->not->toBeEmpty()
        ->and($messages)->toHaveKey('cpanel/categories.add_new_category');
})->with(['en', 'de', 'ru']);

it('returns an empty array for an unknown locale', function () {
    expect(dict()->forLocale('zz'))->toBe([]);
});
