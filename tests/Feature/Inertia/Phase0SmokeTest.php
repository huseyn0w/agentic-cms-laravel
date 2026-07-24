<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

/**
 * Phase 0 of the Blade -> Inertia migration: prove the Inertia pipeline is
 * wired end to end — the demo route returns an Inertia response with the right
 * component, a page prop, and the shared props from HandleInertiaRequests.
 *
 * AssertableInertia asserts against the full-page render (the `page` view
 * data), so this exercises the real root template path:
 *  - withoutVite()  stubs @vite/@viteReactRefresh (no manifest needed in tests)
 *  - RefreshDatabase gives the global `*` view composer (get_data) real tables
 *    so it returns null instead of aborting.
 * See ~/.claude/plans/wild-percolating-allen.md
 */
it('renders the Inertia demo page with component, page prop and shared props', function () {
    $this->withoutVite();

    $this->get('/inertia-demo')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Demo')
            ->where('message', 'Inertia is wired.')
            ->has('locale.current')
            ->has('auth')
            ->where('auth.user', null)
        );
});
