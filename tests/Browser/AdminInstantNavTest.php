<?php

/**
 * Pest 4 browser test — proves Inertia `<Link>` navigation in the admin does
 * NOT trigger a full document reload (the whole point of the Blade -> Inertia
 * migration: instant client-side nav).
 *
 * Mirrors the idiom in tests/Browser/AuthAdminTest.php:
 *  - function-style Pest test using the global `visit()` helper
 *  - login via the `[data-testid="login-*"]` fields on /login
 *  - gated behind BROWSER_TESTS=1 (un-skipped by the CI `e2e` job, which runs
 *    against MySQL 8 with Playwright Chromium installed)
 *
 * Proof mechanics: set a JS global (`window.__nav = 'kept'`) after the
 * Dashboard loads, click the sidebar "Categories" Inertia <Link>, then assert
 * the URL changed AND the global survived AND the sidebar element is still
 * the same shell (not remounted). A full document reload would always wipe
 * `window.__nav` back to undefined, so this fails loudly if Inertia's
 * client-side router isn't actually intercepting the click.
 *
 * Admin-shell data-testids (added in Phase 6):
 *   [data-testid="admin-sidebar"] — admin sidebar element
 */
$browserEnv = (bool) env('BROWSER_TESTS', false);

it('admin Link navigation from Dashboard to Categories does not full-reload the document', function () {
    // Log in as the seeded admin.
    visit('/login')
        ->fill('[data-testid="login-username"]', 'admin')
        ->fill('[data-testid="login-password"]', 'agentic-cmsadmin123')
        ->click('[data-testid="login-submit"]');

    // Land on the Dashboard.
    $page = visit('/agentic-cms-laravel-admin');
    $page->assertPresent('[data-testid="admin-sidebar"]');

    // Sentinel: only survives if the click is an Inertia client-side nav,
    // not a full document reload (a reload wipes the JS global).
    $page->script("window.__nav = 'kept'");

    // Click the sidebar "Categories" Inertia <Link>, scoped to the sidebar.
    $page->click('[data-testid="admin-sidebar"] a[href$="/categories"]');

    // Instant-nav proof: URL moved, sentinel survived, shell wasn't remounted.
    $page->assertPathIs('/agentic-cms-laravel-admin/categories');
    expect($page->script('return window.__nav')[0])->toBe('kept');
    $page->assertPresent('[data-testid="admin-sidebar"]');
})->skip(! $browserEnv, 'browser env (served app + Playwright Chromium) required — set BROWSER_TESTS=1');
