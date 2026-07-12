# P10 — Testing Mandate, Coverage & CI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Pest 4 the canonical test runner, enforce the controller→service→repository→model layering with Pest `arch()` presets, migrate the Dusk browser suite to Pest 4 browser testing, document a per-layer test-status table with no layer at zero tests, and stand up a CI pipeline (lint → analyse → test → build → e2e) that measures ≥80% coverage where a driver is available.

**Architecture:** Pest 4 runs *on top of* PHPUnit (it requires **PHPUnit ^12** — so this migration also bumps `phpunit/phpunit` from `^11` to `^12`; Laravel 11.54 + PHP 8.3 tolerate this since Pest manages the PHPUnit layer). The 70 existing class-based PHPUnit tests keep running unchanged; we add a `tests/Pest.php` (hand-written, NOT via `--init`, to avoid clobbering the safety-pinned `phpunit.xml`) plus new Pest-function-style files for `arch()` and browser. Coverage and the browser e2e run in **CI** (GitHub Actions) because this sandbox has no PCOV/Xdebug and no MySQL; locally we can only run the unit/feature suite under SQLite.

**Tech Stack:** Laravel 11.54 / PHP 8.3, Pest 4 (`pestphp/pest`), `pestphp/pest-plugin-browser` + Playwright (Chromium), Larastan level 5, Pint, GitHub Actions, PCOV (CI only).

## Global Constraints

- **Branch:** `refactor/canon-convergence`. Commit each verified slice with a plain message — **NO `Co-Authored-By` / Claude attribution trailer**.
- **Baseline to preserve:** `php artisan test` → **290 passed (720 assertions)**. Every phase must keep the suite green and SHOW real output; never claim passing without the run.
- **Test DB isolation is sacred:** `tests/CreatesApplication.php` force-pins SQLite `:memory:`; `phpunit.xml` uses `force="true"` on `DB_*`. Do NOT run `pest --init` (it rewrites `phpunit.xml`). Do NOT weaken these pins — Docker injects `DB_CONNECTION=mysql` and would wipe the dev DB.
- **Models live in `app/Http/Models/`** (admin under `CPanel/`), NOT `app/Models/`.
- **No coverage driver in this sandbox** (no PCOV/Xdebug). `--coverage` cannot run here — it runs in CI with PCOV. NEVER assert a coverage number that wasn't actually measured.
- **No MySQL in this sandbox** — the Pest browser e2e suite (like Dusk) needs a served app + MySQL; it is authored here but MEASURED in CI/local. Do not delete `laravel/dusk` until the Pest browser suite has reached parity in an environment that can actually run it.
- Static analysis (Larastan level 5 + baseline) and Pint stay clean; new code adds **no** baseline entries.
- HARD layering rules (the thing `arch()` encodes): controllers = pure HTTP boundary (no Eloquent/DB); services access data only through repositories (no `DB` facade / query builder / raw SQL); chain controller → service → repository → model.
- After each slice: TDD (characterization first where behavior changes) → suite green (show output) → 2–3 adversarial Opus skeptics → fix → commit → refresh `HANDOFF.md`.

---

## File Structure

| File | Responsibility | Phase |
|------|----------------|-------|
| `composer.json` | add `pestphp/pest` + browser plugin to `require-dev`; repoint `test` / `test:coverage` / `check` scripts | 1, 3 |
| `tests/Pest.php` | bind `Tests\TestCase` to `Feature`/`Unit`; global expectations/helpers (hand-written) | 1 |
| `phpunit.xml` | unchanged except adding PCOV-friendly `<coverage>`/`<source>` if needed; KEEP the `force="true"` pins | 1, 6 |
| `tests/Arch/LayeringTest.php` | Pest `arch()` presets enforcing controller/service/repository boundaries | 2 |
| `tests/Browser/*` (rewritten) | Pest 4 browser tests replacing the 3 Dusk classes; `data-testid`, a11y, no-smoke, mobile | 3 |
| `tests/Pest.php` (browser block) | `uses()` browser base + Playwright config wiring | 3 |
| `playwright.config.*` / `package.json` | Playwright (Chromium) dependency + install script | 3 |
| `REFACTOR_PLAN.md` | per-layer test-status table; sync/async event classification stays | 4 |
| new `tests/**` gap fillers | tests for any layer currently at zero | 4 |
| `.github/workflows/ci.yml` | lint → analyse → test(+coverage PCOV) → build → e2e(MySQL+Playwright) | 5 |
| `HANDOFF.md` | refreshed after each slice | all |

Each phase below is an **independently shippable, separately committed slice**. Execute in order (Phase 2 needs Pest from Phase 1; Phase 3 needs Pest; Phase 5 ties them together).

---

## Phase 1 — Pest 4 as canonical runner

**Files:**
- Modify: `composer.json` (require-dev + scripts)
- Create: `tests/Pest.php`
- Verify-only: `phpunit.xml` (must remain force-pinned)

**Interfaces:**
- Produces: a working `./vendor/bin/pest` that runs all 290 existing tests green; `tests/Pest.php` binding `Tests\TestCase::class` to `Feature` and `Unit` (later phases add `arch()` and browser `uses()` here).

- [ ] **Step 1: Snapshot the green baseline**

Run: `php artisan test 2>&1 | tail -5`
Expected: `Tests:    290 passed (720 assertions)`

- [ ] **Step 2: Install Pest 4 (network required; do NOT run `pest --init`)**

Pest 4 requires PHPUnit `^12`, so first bump the constraint in `composer.json` `require-dev`: `"phpunit/phpunit": "^11.0"` → `"^12.0"`. Then:
```bash
composer require pestphp/pest:^4.0 --dev --with-all-dependencies
```
Expected: Pest + `pestphp/pest-plugin-*` resolved; `phpunit/phpunit` resolves to `^12.x`; `nunomaduro/collision` bumps to `^8`. Confirm the binary exists:
```bash
ls vendor/bin/pest
```
Expected: `vendor/bin/pest`

> **PHPUnit 11→12 risk:** PHPUnit 12 dropped some deprecated APIs (e.g. metadata in doc-comments, a few assertion signatures). If MANY existing tests now error with PHPUnit-12 API messages (not assertion failures), report BLOCKED with the exact errors — the fallback is Pest 3 (PHPUnit-11 compatible, still supports `arch()`/`uses()`/browser). Do not mass-rewrite tests to chase PHPUnit 12 without escalating first.

- [ ] **Step 3: Verify `phpunit.xml` was NOT modified**

Run: `git diff --stat phpunit.xml`
Expected: **no output** (Pest reads the existing `phpunit.xml`; `--init` was deliberately skipped). If it changed, `git checkout phpunit.xml` to restore the force-pins.

- [ ] **Step 4: Hand-write `tests/Pest.php`**

Create `tests/Pest.php`:
```php
<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case binding
|--------------------------------------------------------------------------
| Pest function-style tests in Feature/ and Unit/ resolve against the
| project TestCase (which pins SQLite :memory: via CreatesApplication).
| Existing class-based PHPUnit tests are unaffected — they already extend
| Tests\TestCase directly.
*/
uses(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/
expect()->extend('toBeOne', fn () => $this->toBe(1));
```

- [ ] **Step 5: Run the full suite under Pest**

Run: `./vendor/bin/pest 2>&1 | tail -8`
Expected: `Tests:    290 passed` (same count, possibly reordered). If any class-based test errors under Pest, it is almost always a missing `tests/Pest.php` binding or a `Pest\` autoload issue — fix `tests/Pest.php`, do not edit the failing test.

- [ ] **Step 6: Repoint composer scripts to Pest**

In `composer.json` `scripts`, change:
```json
"test": "pest",
"test:coverage": "pest --coverage --min=80",
"check": ["@lint", "@analyse", "@php artisan test"]
```
(Keep `@php artisan test` in `check` OR switch it to `pest` — pick `pest` for consistency: `"check": ["@lint", "@analyse", "pest"]`.) Note: `artisan test` already proxies to Pest once installed, so both invocations work; the canonical command is `./vendor/bin/pest`.

- [ ] **Step 7: Confirm scripts + lint + analyse still clean**

Run:
```bash
composer lint && composer analyse 2>&1 | tail -3 && ./vendor/bin/pest 2>&1 | tail -5
```
Expected: Pint clean, PHPStan `[OK] No errors`, Pest 290 passed.

- [ ] **Step 8: Commit**

```bash
git add composer.json composer.lock tests/Pest.php
git commit -m "test: adopt Pest 4 as the canonical runner (suite stays 290 green)"
```

---

## Phase 2 — `arch()` layering presets

**Files:**
- Create: `tests/Arch/LayeringTest.php`
- Modify: `tests/Pest.php` (add `->in('Arch')` only if you want Pest-style tests there; `arch()` tests need no TestCase binding, so this is optional)

**Interfaces:**
- Consumes: Pest 4 from Phase 1.
- Produces: `arch()` rules that FAIL the build if a controller touches Eloquent/DB, or a service imports the `DB` facade / query builder. These run inside the normal `pest` invocation.

- [ ] **Step 1: Write the arch presets as failing-by-default guards**

Create `tests/Arch/LayeringTest.php`:
```php
<?php

/*
|--------------------------------------------------------------------------
| Architecture layering presets
|--------------------------------------------------------------------------
| Encodes the HARD rules: controller -> service -> repository -> model.
| Controllers are a pure HTTP boundary; services never touch the ORM.
*/

arch('controllers do not touch the ORM or the DB facade')
    ->expect('App\Http\Controllers')
    ->not->toUse([
        'Illuminate\Support\Facades\DB',
        'Illuminate\Database\Eloquent\Builder',
        'Illuminate\Database\Query\Builder',
    ]);

arch('services never touch the DB facade or query builder')
    ->expect('App\Services')
    ->not->toUse([
        'Illuminate\Support\Facades\DB',
        'Illuminate\Database\Query\Builder',
    ]);

arch('repositories are the only home for Eloquent query building')
    ->expect('App\Repositories')
    ->toOnlyBeUsedIn([
        'App\Services',
        'App\Repositories',
        'App\Providers',
        'App\Mcp',
        'App\Console',
    ]);

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'die'])
    ->not->toBeUsed();
```

- [ ] **Step 2: Run the arch tests — they reveal real violations or pass**

Run: `./vendor/bin/pest tests/Arch/LayeringTest.php 2>&1 | tail -30`
Expected: PASS if the architecture refactor is as clean as `HANDOFF.md` claims. If a rule fails, READ the violation:
  - A genuine layering leak → fix the offending class (move data access into a repository / drop the facade).
  - A legitimate exception (e.g. `App\Services\ContactService` sends mail directly; `App\Services\Concerns` helpers) → narrow the `->expect()` target or add `->ignoring('App\Services\Captcha')`-style exclusions with a one-line comment explaining why. Do NOT delete a rule to make it pass.

- [ ] **Step 3: Tighten `toOnlyBeUsedIn` against reality**

The repository allow-list in Step 1 is a hypothesis. Run Step 2; if it reports repositories used in an unlisted-but-legitimate namespace (e.g. a test helper or a job), add that namespace to the array with a comment. Re-run until green WITHOUT loosening the controller/service rules.

- [ ] **Step 4: Full suite still green**

Run: `./vendor/bin/pest 2>&1 | tail -5`
Expected: `Tests:    29X passed` (290 + the arch test count).

- [ ] **Step 5: Lint + analyse**

Run: `composer lint && composer analyse 2>&1 | tail -3`
Expected: clean.

- [ ] **Step 6: Commit**

```bash
git add tests/Arch/LayeringTest.php tests/Pest.php
git commit -m "test: enforce controller/service/repository layering via Pest arch presets"
```

---

## Phase 3 — Dusk → Pest 4 browser testing

> **ENV FLAG:** This phase is AUTHORED here but can only be MEASURED where a real served app + MySQL + a Playwright Chromium browser exist (CI, or a local MySQL host). This sandbox has no MySQL, so the browser suite will not run green here. Do NOT delete `laravel/dusk` until parity is proven in such an environment (Phase 5 CI is that environment).

**Files:**
- Modify: `composer.json` (`pestphp/pest-plugin-browser` in require-dev), `package.json` (`playwright`)
- Modify: `tests/Pest.php` (browser base binding + base URL)
- Create: `tests/Browser/HomepageTest.php`, `tests/Browser/AuthAdminTest.php`, `tests/Browser/GeoSettingsTest.php` (Pest-style, replacing the 3 Dusk classes)
- Reference (preserve scenarios, then retire): `tests/Browser/AuthAndAdminTest.php`, `tests/Browser/PublicSiteTest.php`, `tests/Browser/GeoSettingsBrowserTest.php`

**Interfaces:**
- Consumes: Pest 4 from Phase 1; the 6 Dusk test methods (scenarios to preserve): login+admin sign-in, admin sidebar readability, homepage styled + post links carry port, language switcher → Russian, post opens from homepage, geo settings → llms.txt.
- Produces: equivalent Pest browser tests using `data-testid` selectors, `assertNoAccessibilityIssues()` (WCAG 2.1 AA), `assertNoSmoke()`, and mobile device simulation.

- [ ] **Step 1: Install the browser plugin + Playwright**

Run:
```bash
composer require pestphp/pest-plugin-browser:^4.0 --dev --with-all-dependencies
npm install --save-dev playwright
npx playwright install chromium
```
Expected: plugin resolved; Chromium downloaded. (If `npx playwright install` is blocked by network/sandbox, FLAG it — the install + run happen in CI.)

- [ ] **Step 2: Add `data-testid` hooks the tests will target**

The Dusk tests asserted on computed styles and link hrefs. Pest browser tests should select by `data-testid`. Add stable hooks to the Blade views the scenarios touch (homepage post link, language switcher, admin login form, admin sidebar, geo settings form fields). For each scenario, grep the current Dusk test for its selectors and add a matching `data-testid` to the corresponding Blade element. Example (homepage post link in `resources/views/default/posts/` index partial):
```blade
<a data-testid="post-link" href="{{ $post->url }}">{{ $post->title }}</a>
```
Keep the change minimal and behavior-preserving (attributes only).

- [ ] **Step 3: Write the Pest browser test for the public site**

Create `tests/Browser/HomepageTest.php`:
```php
<?php

use App\Http\Models\Post;

it('renders a styled homepage whose post links open', function () {
    $page = visit('/');

    $page->assertNoSmoke()                 // no console / JS errors
        ->assertNoAccessibilityIssues()    // WCAG 2.1 AA
        ->assertSee(config('app.name'))
        ->click('[data-testid="post-link"]')
        ->assertPathBeginsWith('/');
})->skip(! env('BROWSER_TESTS'), 'browser env (served app + Playwright) required');

it('switches language to Russian', function () {
    $page = visit('/');

    $page->click('[data-testid="lang-ru"]')
        ->assertQueryStringHas('locale', 'ru')
        ->assertNoSmoke();
})->skip(! env('BROWSER_TESTS'), 'browser env required');
```
> The `->skip(! env('BROWSER_TESTS'), ...)` guard keeps these inert in the SQLite-only sandbox/CI-unit job and active only in the e2e job (which sets `BROWSER_TESTS=1`). Mirror this guard in every browser test.

- [ ] **Step 4: Write the admin + auth browser test**

Create `tests/Browser/AuthAdminTest.php`:
```php
<?php

it('lets a seeded admin sign in and shows a readable dark sidebar', function () {
    $page = visit('/agentic-cms-laravel-admin');

    $page->fill('[data-testid="login-username"]', 'admin')
        ->fill('[data-testid="login-password"]', 'agentic-cmsadmin123')
        ->click('[data-testid="login-submit"]')
        ->assertPathContains('agentic-cms-laravel-admin')
        ->assertVisible('[data-testid="admin-sidebar"]')
        ->assertNoAccessibilityIssues()
        ->assertNoSmoke();
})->skip(! env('BROWSER_TESTS'), 'browser env required');
```

- [ ] **Step 5: Write the geo-settings → llms.txt browser test**

Create `tests/Browser/GeoSettingsTest.php` mirroring `GeoSettingsBrowserTest::test_admin_fills_geo_settings_and_it_reaches_llms_txt` — sign in, navigate to Settings → GEO, fill the business-identity fields via `data-testid`, save, then `visit('/llms.txt')->assertSee(<the value>)`. Guard with the same `->skip()`.

- [ ] **Step 6: Add a mobile-device simulation assertion**

To satisfy the "mobile device sim" requirement, add one mobile variant (Pest browser supports `->on()->mobile()` / device presets — confirm exact API from `pest-plugin-browser` docs at author time):
```php
it('homepage is usable on a mobile viewport', function () {
    visit('/')->on()->mobile()
        ->assertNoAccessibilityIssues()
        ->assertNoSmoke();
})->skip(! env('BROWSER_TESTS'), 'browser env required');
```

- [ ] **Step 7: Verify the SQLite suite is unaffected (browser tests skip)**

Run: `./vendor/bin/pest 2>&1 | tail -6`
Expected: existing 29X pass; the new browser tests report as **skipped** (no `BROWSER_TESTS` env). No errors.

- [ ] **Step 8: Keep Dusk for now; commit the authored browser suite**

Do NOT remove `laravel/dusk` yet (parity unproven without a browser env). Commit:
```bash
git add composer.json composer.lock package.json package-lock.json tests/Browser/ tests/Pest.php resources/views
git commit -m "test: author Pest 4 browser suite (parity with Dusk scenarios; runs in CI e2e job)"
```

- [ ] **Step 9 (deferred to CI, Phase 5): retire Dusk after parity**

Once the Phase 5 e2e job runs the Pest browser suite green, remove `laravel/dusk`, `tests/DuskTestCase.php`, the old `tests/Browser/*Test.php` Dusk classes, `scripts/dusk.sh`, and the `make dusk` target — in a dedicated commit, with the CI run as evidence. Until then, leave them.

---

## Phase 4 — Per-layer test-status table + zero-coverage gap fillers

**Files:**
- Modify: `REFACTOR_PLAN.md` (add the table)
- Create: tests for any layer found at zero

**Interfaces:**
- Consumes: the full `tests/` inventory.
- Produces: a table in `REFACTOR_PLAN.md` enumerating every layer (models, controllers, middleware, form requests, policies, repositories, services, observers/events/listeners, jobs, console commands, providers/bindings, Blade components, factories) with a test-status cell — and at least one test per previously-untested layer.

- [ ] **Step 1: Inventory current tests per layer (delegate to a subagent)**

Dispatch a read-only subagent to map every `app/` layer to the test files that exercise it, and to list layers with ZERO tests. Output: a markdown table draft + a list of untested layers with the highest-value candidate to test in each.

- [ ] **Step 2: Paste the table into `REFACTOR_PLAN.md`**

Add a `## Per-layer test status (P10)` section with the table. Columns: `Layer | Location | Test files | Status (✅ covered / ⚠️ partial / ❌ none)`.

- [ ] **Step 3: For each ❌ layer, write one characterization test (TDD)**

For every layer at zero, add at least one focused test. Typical gaps in this codebase: middleware (e.g. `Localization`, `AdminPanelMiddleware`), providers/bindings, Blade components, console commands without a test, factories. Example for a middleware:
```php
<?php

use App\Http\Middleware\Localization;

it('resolves the locale from the session', function () {
    session(['locale' => 'ru']);
    $this->get('/')->assertOk();
    expect(app()->getLocale())->toBe('ru');
});
```
Write the test to characterize CURRENT behavior; if it reveals a bug, fix per systematic-debugging and note it.

- [ ] **Step 4: Run the suite**

Run: `./vendor/bin/pest 2>&1 | tail -6`
Expected: all green, count increased by the gap-fillers. No layer left at ❌ in the table.

- [ ] **Step 5: Lint + analyse, then commit**

```bash
composer lint && composer analyse 2>&1 | tail -3
git add REFACTOR_PLAN.md tests/
git commit -m "test: per-layer status table + fill zero-coverage layers"
```

---

## Phase 5 — CI pipeline (lint → analyse → test → build → e2e)

**Files:**
- Create: `.github/workflows/ci.yml`

**Interfaces:**
- Consumes: composer scripts (`lint`, `analyse`), `pest`, npm build, the Pest browser suite.
- Produces: a GitHub Actions workflow that runs on push/PR, measures coverage with PCOV (≥80% on services/repos), builds assets, and runs the browser e2e against a MySQL service + Playwright.

- [ ] **Step 1: Author the workflow**

Create `.github/workflows/ci.yml`:
```yaml
name: CI

on:
  push:
    branches: [master, refactor/canon-convergence]
  pull_request:

jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: imagick, pdo_sqlite, pdo_mysql
          coverage: pcov
      - uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
      - run: composer install --no-interaction --prefer-dist
      - name: Lint (Pint)
        run: composer lint
      - name: Static analysis (Larastan)
        run: composer analyse
      - name: Tests + coverage (SQLite, PCOV)
        run: ./vendor/bin/pest --coverage --min=80

  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: npm
      - run: npm ci
      - run: npm run build

  e2e:
    runs-on: ubuntu-latest
    needs: [quality, build]
    services:
      mysql:
        image: mysql:8
        env:
          MYSQL_DATABASE: agentic_cms_laravel_dusk
          MYSQL_ROOT_PASSWORD: root
        ports: ['3306:3306']
        options: >-
          --health-cmd="mysqladmin ping" --health-interval=10s
          --health-timeout=5s --health-retries=5
    env:
      BROWSER_TESTS: 1
      DB_CONNECTION: mysql
      DB_HOST: 127.0.0.1
      DB_DATABASE: agentic_cms_laravel_dusk
      DB_USERNAME: root
      DB_PASSWORD: root
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: imagick, pdo_mysql
      - uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: npm
      - run: composer install --no-interaction --prefer-dist
      - run: npm ci && npm run build
      - run: npx playwright install --with-deps chromium
      - run: cp .env.example .env && php artisan key:generate
      - run: php artisan migrate --seed --force
      - name: Serve app
        run: php artisan serve --port=8000 &
      - name: Pest browser e2e
        run: ./vendor/bin/pest tests/Browser --browser
```
> Confirm the exact browser invocation flag (`--browser` vs a `Browser` suite) against the installed `pest-plugin-browser` version at author time; adjust the last step accordingly.

- [ ] **Step 2: Validate the YAML**

Run: `php -r "echo 'yaml authored';"` then eyeball, or use `npx --yes yaml-lint .github/workflows/ci.yml` if available.
Expected: well-formed YAML, no tabs.

- [ ] **Step 3: Note coverage reality in `HANDOFF.md`**

Coverage cannot be measured in this sandbox (no PCOV); it is asserted by the `quality` job's `--min=80`. Record this in `HANDOFF.md` — never paste a coverage % that wasn't produced by an actual CI run.

- [ ] **Step 4: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "ci: add lint/analyse/test+coverage/build/e2e pipeline (GitHub Actions)"
```

---

## Phase 6 — Coverage targets (CI-gated)

> Coverage is enforced by Phase 5's `quality` job (`pest --coverage --min=80`). There is NOTHING to run locally here (no driver). This phase is about reaching the number, using CI output as the source of truth.

- [ ] **Step 1: Read the first CI coverage report**

After Phase 5 is pushed, read the `quality` job log for the per-file coverage table.

- [ ] **Step 2: Raise services/repos to ≥80% and critical paths to 100%**

Critical paths (must be 100%): auth (login/register/social/email-verification), content CRUD (posts/pages/categories), publishing (scheduled publish-due), media. For each under-covered service/repository, add targeted tests (TDD). Re-push; read the new report. Iterate until the `--min=80` gate passes and the critical-path files read 100%.

- [ ] **Step 3: Record the MEASURED numbers in `HANDOFF.md` + `REFACTOR_PLAN.md`**

Only the numbers the CI run actually produced. Commit.

---

## Self-Review

**Spec coverage (vs P10 in HANDOFF.md):**
- Migrate suite to Pest 4 → Phase 1. ✅
- Pest `arch()` presets (controllers ⊄ Eloquent; services ⊄ DB/query builder; chain) → Phase 2. ✅
- Migrate Dusk → Pest 4 browser (3 scenarios, `data-testid`, `assertNoAccessibilityIssues`, `assertNoSmoke`, mobile sim) + remove `laravel/dusk` once parity reached → Phase 3 (+ deferred Step 9). ✅
- Per-layer test-status table in `REFACTOR_PLAN.md`, no layer at zero → Phase 4. ✅
- Coverage ≥80% services/repos + 100% critical paths; flag no-PCOV-here → Phase 6 + Global Constraints. ✅
- CI pipeline (lint → analyse → test → build → e2e) → Phase 5. ✅

**Placeholder scan:** Browser API specifics (`->on()->mobile()`, the `pest` browser run flag) are explicitly marked "confirm against installed plugin version at author time" rather than left as silent TODOs — they depend on the resolved plugin version and cannot be pinned before install. All other steps carry concrete code/commands.

**Type/name consistency:** `BROWSER_TESTS` env guard is used identically across all Phase 3 browser tests and set in the Phase 5 e2e job. `tests/Pest.php` is created in Phase 1 and only appended to in Phases 2–3 (never redefined). Composer scripts repointed once (Phase 1) and consumed by CI (Phase 5).

**Known env limits (flagged, not failures):** PCOV coverage and the browser e2e cannot run in this sandbox (no driver, no MySQL); both are authored here and measured in CI. `laravel/dusk` removal is deferred until parity is proven in CI.
