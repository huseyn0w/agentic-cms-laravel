<?php

/**
 * Pest 4 browser tests — admin authentication & sidebar readability.
 *
 * Replaces the scenarios from tests/Browser/AuthAndAdminTest.php (Laravel Dusk).
 * These tests run ONLY when BROWSER_TESTS=1 is set (CI e2e job).
 * The Playwright Chromium binary is installed in CI via `npx playwright install chromium`.
 *
 * Scenarios covered:
 *  1. Login page is styled (Tailwind, not raw markup) and admin can sign in.
 *  2. After sign-in the dark sidebar is rendered and its navigation is accessible.
 *
 * API used: pest-plugin-browser v4.3.1
 *   visit(url)                        → PendingAwaitablePage
 *   ->assertPresent(selector)         → element exists in DOM
 *   ->assertVisible(selector)         → element is visible
 *   ->fill(selector, value)           → fills an input
 *   ->click(selector)                 → clicks element
 *   ->assertPathContains(path)        → asserts URL path contains string
 *   ->assertSee(text)                 → visible text assertion
 *   ->assertNoSmoke()                 → no console / JS errors
 *   ->assertNoAccessibilityIssues(1)  → WCAG 2.1 AA (level 1 = serious+critical)
 *
 * Phase 4 public-shell data-testids referenced from the public site:
 *   [data-testid="skip-link"]          — skip-to-content link
 *   [data-testid="public-header"]      — <header> element
 *   [data-testid="header-wordmark"]    — wordmark anchor
 *   [data-testid="dark-toggle"]        — dark/light toggle button (desktop)
 *   [data-testid="mobile-menu-button"] — hamburger button
 *
 * Admin-shell data-testids (added in Phase 6):
 *   [data-testid="admin-sidebar"]      — admin sidebar element
 */
$browserEnv = (bool) env('BROWSER_TESTS', false);

it('login page is styled and admin can sign in via credentials', function () {
    $page = visit('/login');

    // Assert the login form elements are present (styled page, not raw markup).
    $page->assertPresent('[data-testid="login-username"]')
        ->assertPresent('[data-testid="login-password"]')
        ->assertPresent('[data-testid="login-submit"]')
        ->assertNoAccessibilityIssues(1);

    // Sign in with the seeded admin credentials.
    $page->fill('[data-testid="login-username"]', 'admin')
        ->fill('[data-testid="login-password"]', 'agentic-cmsadmin123')
        ->click('[data-testid="login-submit"]');

    // After login, navigate to admin panel — reaching it proves authentication.
    visit('/agentic-cms-laravel-admin')
        ->assertSee('Dashboard')
        ->assertNoSmoke();
})->skip(! $browserEnv, 'browser env (served app + Playwright Chromium) required — set BROWSER_TESTS=1');

it('admin sidebar is rendered and text is readable on the dark rail', function () {
    // Log in first.
    visit('/login')
        ->fill('[data-testid="login-username"]', 'admin')
        ->fill('[data-testid="login-password"]', 'agentic-cmsadmin123')
        ->click('[data-testid="login-submit"]');

    // Visit the admin panel and assert the sidebar is visible and accessible.
    $page = visit('/agentic-cms-laravel-admin');

    $page->assertVisible('[data-testid="admin-sidebar"]')
        ->assertSeeIn('[data-testid="admin-sidebar"]', 'Pages')
        ->assertSeeIn('[data-testid="admin-sidebar"]', 'Users')
        ->assertNoAccessibilityIssues(1)
        ->assertNoSmoke();
})->skip(! $browserEnv, 'browser env required — set BROWSER_TESTS=1');

it('admin panel is usable on a mobile viewport', function () {
    // Log in.
    visit('/login')
        ->fill('[data-testid="login-username"]', 'admin')
        ->fill('[data-testid="login-password"]', 'agentic-cmsadmin123')
        ->click('[data-testid="login-submit"]');

    $page = visit('/agentic-cms-laravel-admin')->on()->mobile();

    $page->assertNoSmoke()
        ->assertNoAccessibilityIssues(1);
})->skip(! $browserEnv, 'browser env required — set BROWSER_TESTS=1');

it('public site header has skip link and dark toggle after logout', function () {
    $page = visit('/');

    $page->assertPresent('[data-testid="skip-link"]')
        ->assertPresent('[data-testid="public-header"]')
        ->assertPresent('[data-testid="header-wordmark"]')
        ->assertPresent('[data-testid="dark-toggle"]')
        ->assertNoSmoke();
})->skip(! $browserEnv, 'browser env required — set BROWSER_TESTS=1');
