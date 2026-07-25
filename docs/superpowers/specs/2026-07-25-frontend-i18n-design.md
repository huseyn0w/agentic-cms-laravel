# Frontend i18n layer (Inertia migration — Phase 1)

**Status:** Approved design. Part of the strangler migration of `agentic-cms-laravel`
from Blade to Inertia + React. See root plan `~/.claude/plans/wild-percolating-allen.md`.

## Context

The Blade views use 825 `__()`/`@lang()`/`trans()` calls across 38 lang files per
locale (`en`, `de`, `ru`). React pages rendered through Inertia cannot call Laravel's
`__()`. Before any Blade page can be migrated, React needs a working `t()` that reads
the same translations on all three locales. This phase builds that enabling layer and
migrates **zero** pages. The temporary `/inertia-demo` route (from Phase 0) is the
smoke check that a translated string renders in React.

## Decisions (locked)

- **Library:** `react-i18next` (+ `i18next`).
- **Source of truth:** the existing `resources/lang/{locale}/**/*.php` files. No JSON
  duplication, no build step. A middleware builds the current-locale dictionary at
  request time and shares it as an Inertia prop. Backend code and email templates keep
  using the same PHP files.
- **Pluralization:** not supported. `trans_choice` usage in views is 0; ICU/plurals are
  out of scope (YAGNI). Revisit only if a real plural string appears.

## Components

### 1. `TranslationDictionary` (backend, `app/Support/I18n/TranslationDictionary.php`)

- Input: a locale string. Output: a **flat** `array<string,string>`.
- Enumerates `resources/lang/{locale}/**/*.php`, loads each group through Laravel's
  translator, and flattens to keys **identical to the Blade keys**:
  `cpanel/categories.add_new_category` (directory via `/`, array depth via `.`).
- Normalizes Laravel placeholders `:name` → `{{name}}` so i18next interpolates natively.
  Capitalized variants (`:Attribute`) are lowercased to `{{attribute}}`; auto-ucfirst is
  not reproduced (not needed for the current strings — noted as a known limitation).
- Cached per locale (in-memory / config cache). PHP files remain the single source.

### 2. `HandleInertiaRequests::share()` (extends Phase 0)

Adds to the already-shared `auth` / `flash`:
- `locale`: current locale, from the existing locale resolution (the `{locale?}` route
  segment / `App::getLocale()`), not reinvented.
- `messages`: the flat dictionary for the current locale from `TranslationDictionary`.

The full current-locale dictionary is shared on every request. Per-namespace lazy
loading is deferred (YAGNI); recorded as a future optimization if payload grows.

### 3. `resources/js/lib/i18n.ts` (frontend init)

- Initializes `i18next` + `react-i18next` with `resources = { [locale]: { translation: messages } }`.
- **`keySeparator: false`, `nsSeparator: false`** — keys contain `.` and `/`, so
  `cpanel/categories.add_new_category` must be treated as one literal key.
- `interpolation.escapeValue: false` (React escapes; we render text).
- `fallbackLng: false`; missing key returns the key itself (parity with Laravel).
- On every Inertia visit, `app.tsx` calls `addResourceBundle(locale, 'translation', messages, true, true)`
  then `changeLanguage(locale)` — locale changes via the URL, fresh `messages` arrive
  per visit. Init is synchronous from `resources` (no async backend). Note for Phase 4:
  server-side rendering must NOT reuse this module-level singleton across concurrent
  requests — `ssr.tsx` must create a per-request instance via `i18next.createInstance()`,
  otherwise `changeLanguage` would bleed locale between requests.

### 4. Usage in pages

`const { t } = useTranslation()` → `t('cpanel/categories.add_new_category')`,
with interpolation `t('validation.min.string', { attribute: 'Name', min: 3 })`.

## Data flow

request → locale resolved → `HandleInertiaRequests` builds dict (cached) → Inertia shares
`{ locale, messages }` → `app.tsx` inits/updates i18next → React pages call `t()`.

## Testing (TDD — test before code)

- **Backend unit (Pest):** `TranslationDictionary` produces a flat key from a nested dir,
  normalizes `:attr` → `{{attr}}`, and resolves for `en`/`de`/`ru`.
- **Backend feature (Pest + `AssertableInertia`):** a probe route renders an Inertia page;
  shared props include `locale` and `messages` with a known key, asserted on all 3 locales.
- **Frontend unit (Vitest + React Testing Library — new to the project):** init i18n with a
  sample dictionary; assert key lookup, `{{placeholder}}` interpolation, and missing-key
  returns the key. This phase adds Vitest config and the first frontend test.

## Out of scope for Phase 1

- Migrating any Blade page (Auth/cpanel/public) — later phases.
- Pluralization / ICU.
- Per-namespace lazy dictionary loading.
- SSR wiring (Phase 4) — the init is designed to be compatible, not enabled here.

## Verification

- `composer test` (Pest unit+feature) green, including the new i18n tests on en/de/ru.
- `vitest run` green for the `t()` unit tests.
- `npm run dev` + open `/inertia-demo`: a `t('...')` string renders translated; switching
  the locale segment renders the other language without a key leak.
