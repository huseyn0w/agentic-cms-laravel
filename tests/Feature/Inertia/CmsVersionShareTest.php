<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

/**
 * The core version is shared to every Inertia page so the admin can display it
 * and (later phases) surface an "update available" banner against it.
 */
it('shares the cms version as a global prop', function () {
    $this->withoutVite();
    config(['cms.version' => '1.4.2']);

    $this->get('/inertia-demo')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('cms.version', '1.4.2')
        );
});
