# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Agentic CMS for Laravel: an open-source, AI-first CMS with a built-in MCP server (manage the site from Claude or any MCP client), built on **Laravel 12 / PHP 8.3** (min PHP 8.2). Requires the `ext-imagick` PHP extension and **MySQL 8** (SQLite is used only for the test suite). Front-end is **Tailwind CSS 3 + Alpine.js bundled with Vite** (no Bootstrap/jQuery/Vue — those were removed in the modernization). Local dev can run via Docker Compose; production runs on plain PHP-FPM (Hostinger/VPS) with **no** Docker dependency.

## Commands

```bash
# --- Local dev via Docker (recommended) ---
make dev                         # primary one-command entry: bootstrap the stack (= setup), then follow logs
make setup                       # first-time bootstrap only: docker up + composer install + key:generate + migrate --seed + storage:link + npm build
make up / make down / make reset # start / stop / wipe-and-rebootstrap the stack
make fresh                       # rebuild the DB from scratch (migrate:fresh --seed)
make test                        # run the suite inside the container
# App: http://localhost:8080   Admin: http://localhost:8080/agentic-cms-laravel-admin

# --- Manual (no Docker) ---
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed       # seeds are REQUIRED for a working install (roles/users/pages/posts/settings)
npm run build                    # or `npm run dev` for the Vite dev server (HMR)
php artisan serve

# --- Tests ---
php artisan test                              # full suite (isolated in-memory SQLite — never touches MySQL)
php artisan test --filter=SeoMetaTest         # a single test class
docker compose exec -T app php artisan test   # same, inside the container

# --- Browser / e2e ---
# Canonical CI gate: Pest 4 browser suite (pest-plugin-browser + Playwright Chromium),
# in tests/Browser/, run by the `e2e` job in .github/workflows/ci.yml against MySQL 8.
#   BROWSER_TESTS=1 ./vendor/bin/pest tests/Browser   # un-skips the scenarios
# Legacy Laravel Dusk (host Chrome) is still wired and runnable until it is retired:
make dusk                                     # serves app on :8000 + runs headless-Chrome suite
make dusk ARGS="--filter=AuthAndAdminTest"
```

Test isolation is pinned in `tests/CreatesApplication.php` (forces SQLite `:memory:`, array cache, `app.env=testing`, model-caching off) because the Docker container injects `DB_CONNECTION=mysql` via `$_SERVER` — without the pin the suite would run against (and wipe) the live MySQL dev DB. Don't weaken it. **Exception**: that same file early-returns (skips the pin) when `env('DUSK')` is true, so the **Dusk** browser/e2e suite can share a real DB with the served app. Dusk runs on the host against a dedicated `agentic-cms_laravel_dusk` MySQL DB (`.env.dusk.local`, gitignored; example committed), served on `:8000` — orchestrated by `scripts/dusk.sh` (`make dusk`). Browser tests live in `tests/Browser/` and assert computed styles (not just markup) so they catch CSS regressions the HTTP suite can't. See `docs/e2e-testing.md`.

Code style is enforced by **Laravel Pint** (`pint.json`) — run `composer lint` (`pint --test`, check-only) locally and in CI (the `quality` job in `.github/workflows/ci.yml`). `composer analyse` runs Larastan (level 5). The legacy `.styleci.yml` is retained for reference but Pint is now canonical.

Admin panel lives at `/agentic-cms-laravel-admin` (seeded credentials `admin` / `agentic-cmsadmin123` — change in production).

## Architecture

### Models live in a non-standard location
Eloquent models are in **`app/Http/Models/`** (not `app/Models/`). Admin-only models are under `app/Http/Models/CPanel/`. Keep this convention when adding models.

### Repository pattern
All DB access goes through repositories in `app/Repositories/`, extending the abstract `BaseRepository` (implements `BaseRepositoryInterface`). There are **two parallel families**: front-facing repos (e.g. `PostRepository`, `CategoryRepository`) and admin repos prefixed `CPanel*` (e.g. `CPanelPostRepository`). Controllers stay thin and delegate to repositories. `BaseRepository` has built-in logic for swapping to the `*Translation` model based on the current locale (`checkForTranslation`).

### Multilingual content (astrotomic/laravel-translatable)
Translatable models (`Post`, `Page`, `Category`, `Menu`) implement `TranslatableContract`, use the `Translatable` trait, and declare `$translatedAttributes`. Each has a companion `*Translation` model + `*_translations` table. The active locale is resolved per-request by the `Localization` middleware from `session('locale')`, falling back to `config('app.locale')`. Available languages are defined in `config/app.php` under `languages_list` (read via the `get_languages()` / `lang_exist()` helpers).

### Routing & access control are split front vs. admin
- **Front routes** (`routes/web.php`, default namespace): `PageController`, `PostController`, `CategoryController`, `UserController`, `PostCommentController`. Note the catch-all `/{locale?}/{slug?}` → `PageController@languageIndex` must stay last.
- **Admin routes** are all under the `agentic-cms-laravel-admin` prefix + `CPanel` namespace, guarded by `auth` + `see_admin_panel`, with each section gated by a custom permission middleware.

The permission system is **custom** (not a package): `UserRoles` + `UserPermissions` models, and one middleware per capability (`ManageUsers`, `ManagePosts`, `ManagePages`, `ManageServices`, `ManageCategories`, `ManageComments`, `ManageRoles`, `ManageGeneralSettings`, `ManageMenu`). These are aliased in `app/Http/Kernel.php` as `manage_*`. The `see_admin_panel` alias maps to `AdminPanelMiddleware` (which checks the `see_admin_panel` permission and returns 403 when missing).

### Service content type (first-class, translatable)
`Service` is a full content type mirroring Post/Page but simpler (no categories/tags/likes/comments/scheduling/revisions). Model `app/Http/Models/Service.php` (+ `ServiceTranslation`), tables `services` (id, `sort_order`, soft-deletes) + `service_translations` (title, slug, icon, excerpt, content, thumbnail, SEO fields, status). Front family: `ServiceRepository` + `App\Services\Front\ServiceViewService`; admin family: `CPanelServiceRepository` + `App\Services\CPanel\CPanelServiceService`. Admin CRUD: `CPanelServiceController` under `agentic-cms-laravel-admin/services` gated by the **`manage_services`** permission (its own `ManageServices` middleware + `UserPolicy::manage_services()` + a row in `UserPermissionsSeeder`). Public: `ServiceController` serves `/services` (grid, `listing()`) and `/{locale?}/services/{slug}` (detail, `show()`); views under `resources/views/default/services/`. `ServiceTranslationObserver` sanitises content/excerpt. Services feed `sitemap.xml`, an additive `## Service pages` section in `/llms.txt`, and a schema.org `ItemList`/`Service` JSON-LD block on the index. MCP: 5 tools in `app/Mcp/Tools/Services/` (list/get/create/update/delete, gated on `manage_services`). Sample data: `CPanelServicesSeeder`. NOTE: this is distinct from the `geo_settings` free-text "services" business summary (GEO/SEO identity) — that singleton remains and drives the homepage Organization JSON-LD.

### Theming / template selection
Front views live under `resources/views/default/` (`pages/`, `posts/`, `categories/`, `users/`). A page's blade is chosen dynamically from a DB column: `PageController` does `view('default.pages.' . $this->data->template, ...)`. Adding a page template means adding a blade under `resources/views/default/pages/` and exposing its name to the page editor. `default` is effectively the active theme folder.

### Observers
Registered in `app/Providers/ObserverServiceProvider.php` (`PostObserver`, `PostTranslationObserver`, `PageObserver`) — they handle derived data like slug generation on model events. Add new observers there, not in `EventServiceProvider`.

### Model caching
`Post` and `Category` use the `genealabs/laravel-model-caching` `Cachable` trait. Writes auto-flush the model's cache, but be aware reads are cached when debugging stale data.

### Asset pipeline (Vite + Tailwind + Alpine)
Entry points `resources/css/app.css` (+ `resources/css/admin.css`) and `resources/js/app.js` (+ `resources/js/admin.js`) are bundled by Vite (`vite.config.js`, `tailwind.config.js`). Blade loads them with `@vite([...])`. Tailwind **preflight is disabled globally**; each theme scopes its own reset under a wrapper class (`.theme-default` for the public site, `.theme-admin` for the panel) so the two themes don't bleed into each other. The legacy `public/front/**` and `public/admin/**` *CSS* is no longer loaded, but a handful of legacy `public/admin/js/*.js` files (e.g. `post.js`, `page.js`, `user.js`, `menu.js`, `category.js`, `role.js`, `comments.js`) are **still loaded** via `@push('finalscripts')` in the admin *list* views — they own the row-level delete/destroy/restore AJAX (jQuery hooks on the `.delete_*` / `.destroy_*` / `.restore_*` classes, with per-page `@lang` confirmation strings inlined above the `<script>`). Don't delete those JS files or rename those hook classes without porting the behaviour.

### SEO / GEO
A single head partial `resources/views/default/partials/seo-meta.blade.php` emits title/description, canonical, Open Graph, Twitter cards, `hreflang` alternates, robots, and JSON-LD (via the `json_ld()` / `get_seo_settings()` helpers). `SeoController` serves dynamic `/sitemap.xml`, `/robots.txt`, `/llms.txt` (routes are registered before the front catch-all). Global SEO defaults live in the `seo_settings` singleton (admin **Settings → SEO**); per-entity `canonical_url` / `meta_noindex` live on the `*_translations` tables. Note: `/robots.txt` is dynamic, so nginx must NOT have a static `location = /robots.txt` block. **GEO**: a separate `geo_settings` singleton (admin **Settings → GEO**, model `CPanelGeoSettings`, helper `get_geo_settings()`) captures business identity/services/FAQ/sameAs and auto-feeds the homepage schema.org JSON-LD (Organization/LocalBusiness/Service + FAQPage) in `seo-meta.blade.php` and the `/llms.txt` output in `SeoController` (both gated by the `emit_jsonld` / `include_in_llms` toggles).

### AI / MCP server
A built-in MCP server (`laravel/mcp`) lets an authenticated AI client manage the live site. Route is `routes/ai.php` (`Mcp::oauthRoutes()` + `Mcp::web('/mcp/agentic-cms-laravel', …)` behind `auth:api` + throttle). Auth is **OAuth 2.1 via Laravel Passport** — the `api` guard in `config/auth.php` uses the `passport` driver and `User` implements `OAuthenticatable` + `HasApiTokens`. Server is `app/Mcp/Servers/AgenticCmsLaravelServer.php`; tools live in `app/Mcp/Tools/<Domain>/` and **delegate to the existing `CPanel*Repository` classes** (don't re-implement business logic). Cross-cutting behavior is in `app/Mcp/Concerns/`: `AuthorizesAccess` (per-tool permission gate read from the bearer-token user, mirroring `UserPolicy` flags — every tool must call `deny()`), `ResolvesLocale` (translatable writes), `HydratesRequest` (merges validated input onto the global request because PostObserver/PageObserver/PostTranslationObserver read `content`/`preview`/`category` from `app('request')`), and `ResolvesThemePath` (theme-file path allow-listing — no code execution). `Response::structured()` returns a `ResponseFactory`, so tool `handle()` signatures are typed `Response|ResponseFactory`. There is **no plugin system** — "theme code" = Blade templates under the active theme only. Full guide: `docs/mcp.md`. Deployment needs `php artisan migrate` (Passport tables) + `php artisan passport:keys`.

### Other conventions
- **Validation**: dedicated Form Request classes in `app/Http/Requests/`. The base `AgenticCmsLaravelRequest` must **not** redeclare a `$locale` property (Symfony 7's `Request` already declares a typed `?string $locale`); it uses `$currentLocale` instead.
- **Custom helpers**: globally available, autoloaded via `bootstrap/agentic-cms-laravel-helpers.php` (composer `files` autoload) — e.g. `get_languages()`, `lang_exist()`, `get_current_lang()`.
- **Config over env() in views**: never call `env()` at runtime in Blade (breaks under `php artisan config:cache`); use `config()` — e.g. the active theme is `config('app.template_name')`.
- **Authorization policies**: `app/Policies/UserPolicy.php` (registered in `AuthServiceProvider`).
- **Integrations**: HTML sanitization via `mews/purifier`, media via `unisharp/laravel-filemanager` (config built by `app/Handlers/LfmConfigHandler.php`), social login via `laravel/socialite` (Twitter/Facebook/LinkedIn/Google/GitHub), spam protection via a `captcha` service (`App\Services\Captcha`, Google reCAPTCHA v3 — disabled when no keys), image processing via `intervention/image`.

<!-- ADMIN-STYLE:BEGIN (canonical: DESIGN_SYSTEM.md → "Admin panel visual style") -->
## Visual style — Vercel monochrome (whole system: public, auth, admin) (REQUIRED)

The entire product surface — public site, auth screens, and admin panel/dashboard — uses the agentic-cms marketing-site visual language (reference: https://elman.group/agentic-cms/): clean, glossy, and **strictly monochrome** — black, white, and zinc grey only. NO colored brand accent (no violet/blue/terracotta), NO warm/cream tones, NO red as an accent.

- **Surfaces:** white `#ffffff` app background; subtle panels `#fafafa` and `#f4f4f5`.
- **Neutrals (zinc):** borders `#e4e4e7` / `#d4d4d8`; text `#0a0a0a` (primary), `#3f3f46` (secondary), `#71717a` (muted), `#a1a1aa` (faint).
- **Accent is black `#0a0a0a`.** Primary buttons = black bg / white text. Active nav item = subtle `#f4f4f5` bg + black text/icon + a thin black inset indicator. Selected checkbox = black.
- **Premium edges (do NOT use flat 1px grey lines — they look cheap):** give cards/inputs/nav a **gradient bevel border** (lighter at top → darker at bottom, e.g. `linear-gradient(180deg, rgba(9,9,11,.11), rgba(9,9,11,.045) 40%, rgba(9,9,11,.03))` via a padding-box/border-box background trick), a **layered floating shadow** (`0 1px 2px rgba(9,9,11,.04), 0 10px 30px -12px rgba(9,9,11,.14)`), and an **inset top highlight** (`inset 0 1px 0 rgba(255,255,255,.7)`). Table row separators stay very faint (`rgba(9,9,11,.06)`); rely on hover background, not hard lines.
- **Semantic status colors only:** destructive/error red `#dc2626` and success green `#15803d`, used ONLY for delete affordances, validation errors, and success states — never decoration.
- **Typography:** Geist / Geist Mono (system-ui / ui-monospace fallback). Tight negative letter-spacing on headings.
- **Structure (Vercel):** left sidebar (light `#fafafa`, grouped nav with small uppercase group labels) + translucent sticky topbar (`backdrop-filter: blur(12–14px)`, breadcrumb, search, icon buttons, avatar) + content area (page header = title + primary action, then bordered white cards / data tables). Airy spacing, clear hierarchy, restraint.
- **Logo/mark:** monochrome only — solid black mark with a white glyph. No gradient.
- **Dark mode:** invert to near-black surfaces (`#0a0a0a` / `#17171a`) keeping the same zinc/white/black logic; on dark, white becomes the accent (white primary buttons).

Apply across the entire product surface — public site (marketing pages, blog/content), auth screens (login, register, password reset), and admin (shell, list/table screens, forms, buttons, modals, and the stats dashboard). Implement natively per stack (idiomatic components + your CSS/Tailwind), tokens defined once and shared across public, auth, and admin. This monochrome look is the canonical style for the whole family, not just the admin. Full spec: `DESIGN_SYSTEM.md` → "Visual style — Vercel monochrome (whole system)".
<!-- ADMIN-STYLE:END -->
