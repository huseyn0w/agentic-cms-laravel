<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

it('shares the translation dictionary as a messages prop', function () {
    $this->withoutVite();

    $this->get('/inertia-demo')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('messages')
            // Illuminate\Testing\Fluent\Concerns\Matching::where() wraps array
            // props in a Collection before invoking the closure, so `$messages`
            // is a Collection here, not a raw array.
            ->where('messages', fn ($messages) => $messages->get('cpanel/categories.add_new_category') === 'Add new category')
        );
});

it('shares the dictionary for the session locale', function (string $locale) {
    $this->withoutVite();

    $this->withSession(['locale' => $locale])
        ->get('/inertia-demo')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('messages', fn ($messages) => $messages->has('cpanel/categories.add_new_category'))
        );
})->with(['en', 'de', 'ru']);
