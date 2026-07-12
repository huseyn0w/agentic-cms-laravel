<?php

/**
 * Pest 4 browser tests — GEO settings form → llms.txt end-to-end.
 *
 * Replaces the scenario from tests/Browser/GeoSettingsBrowserTest.php (Laravel Dusk).
 * These tests run ONLY when BROWSER_TESTS=1 is set (CI e2e job).
 * The Playwright Chromium binary is installed in CI via `npx playwright install chromium`.
 *
 * Scenario:
 *   Admin signs in → navigates to Settings → GEO → fills business-identity
 *   fields via data-testid selectors → submits → sees the success flash →
 *   visits /llms.txt and sees the saved service text.
 *
 * API used: pest-plugin-browser v4.3.1
 *   visit(url)                        → PendingAwaitablePage
 *   ->fill(selector, value)           → fills an input / textarea
 *   ->select(selector, value)         → selects a <select> option
 *   ->check(selector)                 → checks a checkbox
 *   ->click(selector)                 → clicks an element
 *   ->assertSee(text)                 → visible text assertion
 *   ->assertPresent(selector)         → element exists in DOM
 *   ->assertNoSmoke()                 → no console / JS errors
 *   ->assertNoAccessibilityIssues(1)  → WCAG 2.1 AA (level 1 = serious+critical)
 */
$browserEnv = (bool) env('BROWSER_TESTS', false);

it('admin fills geo settings and the data reaches llms.txt', function () {
    // Sign in with seeded admin credentials.
    visit('/login')
        ->fill('[data-testid="login-username"]', 'admin')
        ->fill('[data-testid="login-password"]', 'agentic-cmsadmin123')
        ->click('[data-testid="login-submit"]');

    // Navigate to the GEO settings page.
    $page = visit('/agentic-cms-laravel-admin/geo-settings');

    $page->assertSee('GEO')
        ->assertPresent('[data-testid="geo-business-name"]')
        ->assertPresent('[data-testid="geo-services"]')
        ->assertNoAccessibilityIssues(1);

    // Fill the form fields.
    $page->fill('[data-testid="geo-business-name"]', 'Elman Group')
        ->select('[data-testid="geo-business-type"]', 'ProfessionalService')
        ->fill('[data-testid="geo-description"]', 'Custom Laravel CMS and AI integration studio.')
        ->fill('[data-testid="geo-services"]', "Laravel development\nAI / MCP integration")
        ->fill('[data-testid="geo-service-area"]', 'Baku, Azerbaijan; Remote, EU')
        ->check('[data-testid="geo-include-in-llms"]');

    // Submit and assert the success flash.
    $page->click('[data-testid="geo-submit"]')
        ->assertSee('updated')
        ->assertNoSmoke();

    // Verify the saved data is reflected in the public /llms.txt endpoint.
    visit('/llms.txt')
        ->assertSee('## Services')
        ->assertSee('AI / MCP integration')
        ->assertNoSmoke();
})->skip(! $browserEnv, 'browser env (served app + Playwright Chromium) required — set BROWSER_TESTS=1');

it('geo settings page is accessible on a mobile viewport', function () {
    // Sign in.
    visit('/login')
        ->fill('[data-testid="login-username"]', 'admin')
        ->fill('[data-testid="login-password"]', 'agentic-cmsadmin123')
        ->click('[data-testid="login-submit"]');

    $page = visit('/agentic-cms-laravel-admin/geo-settings')->on()->mobile();

    $page->assertSee('GEO')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(1);
})->skip(! $browserEnv, 'browser env required — set BROWSER_TESTS=1');
