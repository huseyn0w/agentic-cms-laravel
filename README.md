<p align="center">
<img alt="Cmstack-Laravel Logo" src="https://raw.githubusercontent.com/huseyn0w/cmstack-laravel/master/public/front/default/img/readme.png">
</p>

# Cmstack-Laravel

**Cmstack-Laravel — a modern, open-source CMS built on Laravel.** A multilingual, SEO/GEO-ready
content management system built on **Laravel 12 / PHP 8.3**, with a Tailwind + Vite
front-end and a first-class admin panel. It plays the same role as a traditional content
management system, but on a clean, testable Laravel codebase that developers actually enjoy extending.

Built and maintained by **[Elman Group](https://elman.group)**.

---

## Table of contents

- [Features](#features)
- [Tech stack](#tech-stack)
- [Architecture](#architecture)
- [Design system](#design-system)
- [Requirements](#requirements)
- [Quick start (Docker + Makefile)](#quick-start-docker--makefile)
- [Manual setup (no Docker)](#manual-setup-no-docker)
- [Testing](#testing)
- [SEO / GEO](#seo--geo)
- [AI / MCP connector (manage your site from Claude)](#ai--mcp-connector-manage-your-site-from-claude)
- [Multilingual configuration](#multilingual-configuration)
- [Admin credentials](#admin-credentials)
- [Deployment](#deployment)
  - [Hostinger (shared hosting, no Docker)](#hostinger-shared-hosting-no-docker)
  - [VPS (nginx + php-fpm + mysql)](#vps-nginx--php-fpm--mysql)
- [Is it ready to deploy?](#is-it-ready-to-deploy)
- [Author & license](#author--license)

---

## Features

- **Full CMS**: pages, posts, categories, menus, comments, media / file manager
- **Tags taxonomy** — posts carry many-to-many tags; `/tag/{slug}` archives with pagination
- **Content revisions** — every page/post save snapshots a revision; diff view and one-click restore in the admin
- **Soft-delete** — pages and posts use `SoftDeletes`; trashed items are recoverable from the admin
- **Scheduled publishing** — set a future `scheduled_at` date on any post; the `posts:publish-due` scheduler command promotes it automatically (runs every minute; safe with `--without-overlapping`)
- **RSS / Atom feeds** — `/rss.xml` and `/atom.xml` served by `SeoController`
- **Plugin / hook system** — `App\Support\Hooks` provides `action / filter / region` hooks; in-repo plugins under `app/Plugins/` are auto-discovered by `PluginManager` and managed from the admin panel (`CPanelPluginController`); the `@hook` Blade directive renders hook regions in templates
- **Membership toggle + email-verification enforcement** — public registration and email-verification requirements are both runtime settings, toggled from the admin panel (no code changes needed)
- **Category tree admin UI** — nested category picker that excludes circular references
- **Modern responsive UI** (Tailwind CSS 3 + Alpine.js) for both the public site and the admin panel, including **dark mode** (system-preference aware with an admin toggle)
- **Built-in SEO/GEO** — Open Graph, Twitter cards, canonical + `hreflang`, JSON-LD structured data, dynamic `sitemap.xml`, `robots.txt`, and `llms.txt`
- **Multilingual content** via `astrotomic/laravel-translatable` (en/ru out of the box, easily extended)
- **Social-media authentication** (Facebook, GitHub, LinkedIn, and other Socialite providers)
- **Users, roles & granular permissions** (custom role/permission middleware — one capability per middleware)
- **Custom fields** and a **flexible template-switching system**
- **Website search**
- **Spam protection** via Google reCAPTCHA v3 (gracefully disabled when no keys are set)
- **Database / model caching**
- **AI / MCP connector** — manage the live site from Claude (posts, pages, users, settings, theme) over an authenticated MCP server (see [AI / MCP connector](#ai--mcp-connector-manage-your-site-from-claude))

---

## Tech stack

- **Laravel 12** (PHP 8.3)
- **Tailwind CSS 3** + **Alpine.js**, bundled with **Vite** — lightweight front-end, no jQuery/plugin bloat
- **MySQL 8** with `astrotomic/laravel-translatable` for multilingual content
- Repository pattern + service layer, custom role/permission middleware, model caching
- Google reCAPTCHA (v3) for spam protection
- Docker stack for local development (nginx + php-fpm + MySQL 8)
- `ext-imagick` for image processing

---

## Architecture

Cmstack-Laravel enforces **strict layering** throughout the codebase:

```
HTTP request → Controller → Service → Repository → Model
```

- **Controllers** are a pure HTTP boundary. They resolve request data and delegate to services; they never touch the ORM or the `DB` facade directly.
- **Services** (`App\Services`) contain business logic. They call repositories for all data access and never query the database themselves.
- **Repositories** (`App\Repositories`) are the single home for Eloquent query building. They are consumed by services, MCP tools, and model observers — nothing else.
- **Models** (`App\Http\Models`) define Eloquent relationships, casts, and fillable attributes.

These rules are **machine-enforced** by Pest `arch()` presets in `tests/Arch/LayeringTest.php` — a build failure, not a convention — so the layering can't silently erode as the codebase grows.

### Why this structure?

- Easy to test: services and repositories are injected via the container; controllers become thin and trivial to mock.
- Easy to extend: the plugin/hook system, MCP tools, and observers can add behaviour without touching core controller or service logic.
- Easy to read: every code path has a predictable home; no business logic hidden in views or models.

---

## Design system

The UI implements a **canonical "quiet-luxury" editorial design system** shared across the cmstack multi-stack project (spec: `../DESIGN_SYSTEM.md`).

### Tokens and theming

All colors, spacing, and typography scale are expressed as **CSS custom properties** in `resources/css/tokens.css`. Tailwind utilities reference these vars. Two themes are defined:

- **Light** (`:root`) — warm paper background (`#FBFAF7`), warm near-black ink, garnet/terracotta primary accent
- **Dark** (`.dark`) — full dark-mode counterpart; toggled at runtime with no FOUC via an inline script that reads `localStorage` before first paint; also respects `prefers-color-scheme`

### Self-hosted variable fonts

No font CDN. Three variable fonts are self-hosted via `@fontsource-variable/*` packages, imported in `resources/css/fonts.css`:

| Font | Role |
| --- | --- |
| **Newsreader** | Serif display / editorial headings |
| **Inter** | UI body text, admin panel |
| **Geist Mono** | Code, technical content |

Font subsets are locked to Latin to minimize transfer size; `font-display: swap` is used throughout.

### Blade component library

A full set of token-driven Blade components lives in `resources/views/components/`:

`alert`, `avatar`, `badge`, `breadcrumb`, `button`, `card`, `dropdown`, `empty-state`, `eyebrow`, `field`, `icon`, `modal`, `pagination`, `tabs`, `toast-region`

All components flip correctly under `.dark` via token inheritance — no component has hard-coded colors.

### Accessibility

WCAG 2.1 AA is the enforced floor: semantic landmarks, visible focus rings, sufficient contrast at both light/dark tokens, `aria-*` attributes on interactive components, a skip-link, and single `<h1>` per page (including archive views). `prefers-reduced-motion` is respected in transitions.

### Performance budget

Lighthouse CI runs on every push (`.lighthouserc.json`, mobile preset):

| Metric | Gate |
| --- | --- |
| Performance | ≥ 95 |
| SEO | ≥ 95 |
| Accessibility | ≥ 95 |
| Best Practices | ≥ 95 |
| LCP | < 2.0 s |
| CLS | < 0.05 |

A build that misses any threshold fails CI.

---

## Requirements

- **PHP 8.2+** (developed and tested on PHP 8.3) with the `imagick` extension
- **Composer 2**
- **MySQL 8** — SQLite is used only for the test suite (in-memory)
- **Node.js 20+** and npm (for the Vite/Tailwind asset build)
- _(optional)_ **Docker + Docker Compose** for the one-command local stack

---

## Quick start (Docker + Makefile)

The fastest path. The Docker stack (nginx + PHP 8.3-FPM with imagick + MySQL 8) is a
**local development convenience** — it is **not** required at runtime in production.

```bash
git clone <your-repo-url> cmstack-laravel && cd cmstack-laravel
make dev
```

**`make dev` is the single command to launch this stack — the same entry point as every
other `cmstack-*` stack.** It bootstraps everything end-to-end, then follows the container
logs (Ctrl-C stops following; the stack keeps running in the background):

1. copies `.env.example` → `.env` (if missing),
2. builds and starts the Docker stack (`docker compose up -d --build`),
3. `composer install`,
4. `php artisan key:generate`,
5. `php artisan migrate --seed`,
6. `php artisan storage:link`,
7. `npm install && npm run build` (assets, on the host),
8. follows the container logs.

When it is up:

- **App:** http://localhost:8080
- **Admin:** http://localhost:8080/cmstack-laravel-admin (see [credentials](#admin-credentials))

### Available `make` targets

Every `cmstack-*` stack shares the same core commands — `make dev`, `up`, `down`, `reset`,
`logs`, `seed`, `migrate`, `test`, `clean`, `kill` — so you launch and manage them all the
same way. Laravel-specific extras are listed below the shared ones.

| Target       | Description                                                 |
| ------------ | ----------------------------------------------------------- |
| `make dev`   | **One-command launch** (the primary entry): bootstrap the stack, then follow logs |
| `make up`    | Start the Docker stack (already bootstrapped)               |
| `make down`  | Stop the stack (keeps the DB volume)                        |
| `make reset` | Wipe the DB volume and re-bootstrap from scratch            |
| `make logs`  | Tail container logs                                         |
| `make seed`  | Run database seeders                                        |
| `make migrate` | Run database migrations                                   |
| `make test`  | Run the Pest suite inside the container                     |
| `make clean` | Stop the stack **and remove the DB volume** (destroys data) |
| `make kill`  | Release the stack's host port (:8080) — downs its own containers, warns on a foreign holder; runs automatically before `make up`/`make dev` |
| `make setup` | Bootstrap only, without following logs (`make dev` runs this then tails logs) |
| `make fresh` | `migrate:fresh --seed` (rebuild the database)               |
| `make build` | Build front-end assets (Vite production build)              |
| `make shell` | Open a shell in the app container                           |

Run `make help` to see all targets.

> **Ports.** This repo lives beside sibling `cmstack-*` stacks. Host ports are deduplicated so
> they can all run at once — this stack uses **app 8080 / MySQL 33060 / Vite 5173 / Mailpit
> 8025 + 1025** (`WEB_PORT` / `DB_FORWARD_PORT` / `VITE_PORT` / `MAILPIT_*` in `.env`). See
> [`../PORTS.md`](../PORTS.md) for the cross-stack allocation.

### Optional Docker services

- **Mailpit** (catches local outgoing mail, UI on http://localhost:8025):
  ```bash
  docker compose --profile mail up -d mailpit
  ```
  Then set in `.env`: `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025`.
- **Vite HMR** (live reload instead of a one-off build):
  ```bash
  docker compose --profile dev up node     # Vite on http://localhost:5173
  ```

### Live code reload (no container restart needed)

The project directory is bind-mounted into the containers (`./:/var/www/html`), so:

- **PHP, Blade, routes, config, helpers, classes** — changes apply **immediately** on
  the next request. No `docker compose restart` needed.
- **Tailwind CSS / JS** — bundled by Vite, so run **`npm run dev`** (or the `node`
  service above) for hot reload, or `npm run build` for a one-off rebuild.
- **`.env` infrastructure vars** (DB host, etc.) are injected at container start —
  re-run `docker compose up -d` after changing those.
- Avoid `php artisan config:cache` / `route:cache` in dev (they freeze edits until
  `php artisan config:clear`); they're a production-only optimization.

> **Docker-only files:** `Dockerfile`, `docker-compose.yml`, `docker/nginx/default.conf`,
> `docker/php/php.ini`, `.dockerignore`, `Makefile`. **None of these are part of the
> production runtime.**

---

## Manual setup (no Docker)

Requires a local PHP 8.3 + Composer + Node + a running MySQL 8 server.

1. Copy the env file and fill in your settings (database, mail, API keys, default language):

   ```bash
   cp .env.example .env
   ```

   For a **local MySQL** server, set `DB_HOST=127.0.0.1` (the `.env.example` default of
   `DB_HOST=mysql` is the Docker service name). Create a database named `cmstack_laravel`
   (or update `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` accordingly).

2. Install PHP and front-end dependencies:

   ```bash
   composer install
   npm install
   ```

3. Generate the app key, run migrations + seeders, link storage, and build assets:

   ```bash
   php artisan key:generate
   composer setup            # = migrate --seed --force + storage:link
   npm run build             # or `npm run dev` for the Vite dev server with HMR
   ```

   The `composer setup` script bundles `key:generate`, `migrate --seed`, and `storage:link`
   for the non-Docker path. You can also run those artisan commands individually.

4. Serve the app:
   ```bash
   php artisan serve         # http://127.0.0.1:8000
   ```

> **Media uploads:** the file manager writes to `public/uploads`, and the Laravel `public`
> disk maps to `public/storage` → `storage/app/public`. Run `php artisan storage:link`
> (included in `composer setup` / `make setup`) so uploaded media on the public disk is
> served correctly.

### Spam protection (reCAPTCHA)

The contact, search, and password-change forms support Google reCAPTCHA v3. It is
**disabled by default** (forms work without it). To enable it, set in `.env`:

```bash
CAPTCHA_ENABLED=true
RECAPTCHA_SITE_KEY=your_site_key
RECAPTCHA_SECRET_KEY=your_secret_key
```

### Scheduled publishing

The `posts:publish-due` command promotes future-dated posts once their `scheduled_at`
timestamp is reached. In production, register it with the OS scheduler (cron or Supervisor)
to run every minute:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

The command is already wired in `app/Console/Kernel.php` (`everyMinute()->withoutOverlapping()`).
It is optional — posts simply stay in draft until the command runs.

---

## Testing

**Runner: [Pest 4](https://pestphp.com)** — the canonical test runner for this project.

```bash
# Run the full suite (host)
./vendor/bin/pest

# Equivalent shorthands
composer test
php artisan test          # proxies to Pest via phpunit.xml

# With coverage (80 % minimum enforced in CI)
composer test:coverage    # = ./vendor/bin/pest --coverage --min=80

# Run all quality checks (lint → analyse → test)
composer check

# Inside Docker
docker compose exec app php artisan test
make test
```

The suite runs on an **isolated in-memory SQLite** database — no MySQL / Docker setup
needed to run tests.

### Lint and static analysis

```bash
composer lint             # Pint (check only)
composer lint:fix         # Pint (auto-fix)
composer analyse          # Larastan level 5
```

### Architecture presets

`tests/Arch/LayeringTest.php` contains Pest `arch()` rules that enforce the strict
controller → service → repository → model layering at the test level. A violation is a
build failure in CI.

### CI pipeline (`.github/workflows/ci.yml`)

Every push to `master` or `refactor/canon-convergence` runs four jobs in order:

| Job | What it does |
| --- | --- |
| **Lint · Analyse · Test** | Pint → Larastan → `./vendor/bin/pest --coverage --min=80` on in-memory SQLite + PCOV |
| **Build assets** | `npm ci && npm run build` (produces the Vite manifest for downstream jobs) |
| **Browser e2e** | Pest 4 browser suite (`tests/Browser/`) via `pest-plugin-browser` + Playwright Chromium against a real MySQL 8 database |
| **Lighthouse perf gate** | LHCI mobile against the served app; fails if any score < 95 or LCP ≥ 2 s / CLS ≥ 0.05 |

### Browser / e2e tests (Pest 4 + Playwright)

Real headless-Chromium tests that verify **functionality and rendered output** (login, admin
sidebar, language switch, GEO settings, link ports) — things the HTTP-level suite can't see.

```bash
# In CI: automatically run as the "Browser e2e" job
BROWSER_TESTS=1 ./vendor/bin/pest tests/Browser
```

Tests in `tests/Browser/` are guarded with `->skip()` unless `BROWSER_TESTS=1` is set,
so the default `composer test` run stays fast.

> **Dusk:** Laravel Dusk (`laravel/dusk ^8`) is still present in `composer.json` and the
> `make dusk` target still works, but CI now runs the Pest 4 browser suite exclusively.
> Dusk will be removed once full CI parity is confirmed.

---

## SEO / GEO

Cmstack-Laravel ships SEO/GEO support out of the box, built to stay fast (no
script bloat — public pages load only the Vite bundle and, optionally, a single async
analytics tag):

- Per-page `<title>` / `<meta description>`, `<link rel="canonical">`, per-entity `noindex` / canonical overrides.
- **Open Graph** + **Twitter Card** tags (with a configurable default social image).
- **`hreflang`** alternates (en/ru + `x-default`) for multilingual content.
- **JSON-LD structured data** (schema.org) — `WebSite` + `SearchAction` + `Organization` on the homepage,
  `BlogPosting` + `BreadcrumbList` on posts, `CollectionPage` on categories, `ProfilePage` / `Person`
  on profiles — which also helps generative engines (GEO).
- Dynamic **`/sitemap.xml`** (pages/posts/categories with `hreflang`), **`/robots.txt`**, and **`/llms.txt`**.
- **RSS / Atom feeds** — `/rss.xml` and `/atom.xml` for post syndication.
- Lazy-loaded images with width/height (CLS-safe), `preconnect` fonts with `display=swap`, deferred/module scripts.

Configure global SEO defaults in the admin panel under **Settings → SEO** (title separator,
default description, default OG image, social handles, Google/Bing verification tags, optional
async GA4/GTM id, a global "discourage search engines" toggle, sitemap toggle, and extra
`robots.txt` lines).

---

## AI / MCP connector (manage your site from Claude)

Cmstack-Laravel includes a built-in **Model Context Protocol (MCP) server**, so you can manage
your **live** site from an AI client such as **Claude** (Claude Code CLI, the VS Code
extension, or claude.ai) using natural language — _"create a draft post about X"_,
_"update the SEO meta description"_, _"add a partial to the theme"_.

It is built on the official [`laravel/mcp`](https://laravel.com/docs/12.x/mcp) package and
runs **inside the Laravel app** (no separate service). Security is first-class:

- **OAuth 2.1** authentication via Laravel Passport — endpoint `POST /mcp/cmstack-laravel`.
- Every tool runs as the authenticated admin and is **gated by the same `manage_*`
  permissions** as the admin panel.
- **No raw code execution.** The only code surface is editing theme Blade templates,
  restricted to `*.blade.php` files inside the active theme with path allow-listing.

Tool coverage: **posts, pages, categories** (full CRUD, multilingual), **users & roles**,
**general + SEO settings**, and **theme templates** (list/read/write).

Enable it on a deployment (Passport is already pulled in via `composer install`):

```bash
php artisan migrate          # adds Passport oauth_* tables
php artisan passport:keys    # generate encryption keys (once per environment)
# ensure APP_URL is your real https URL, then: php artisan config:clear
```

Connect from Claude Code:

```bash
claude mcp add --transport http cmstack-laravel https://your-site.com/mcp/cmstack-laravel
# then run /mcp in Claude and authenticate in the browser
```

**Full guide:** [`docs/mcp.md`](docs/mcp.md) — complete tool list, VS Code / claude.ai
setup, the OAuth consent flow, and how to extend the toolset.

---

## Multilingual configuration

1. Edit the language list in `config/app.php`:
   ```php
   'languages_list' => [
       'en' => ['title' => 'English', 'icon' => env('APP_URL').'/admin/img/flags/en.png'],
       'ru' => ['title' => 'Русский', 'icon' => env('APP_URL').'/admin/img/flags/ru.png'],
   ],
   ```
2. Manage the localization strings under `resources/lang/`.

The active locale is resolved per request from `session('locale')`, falling back to
`config('app.locale')` (set via the `LOCALE` env key).

---

## Admin credentials

After seeding, the admin panel lives at:

```
<APP_URL>/cmstack-laravel-admin
```

Seeded login:

```
Username: admin
Password: cmstackadmin123
```

> Change this password immediately in any non-local environment.

---

## Deployment

Cmstack-Laravel runs on traditional PHP hosting with **no runtime dependency on Docker**. Two
supported targets:

### Hostinger (shared hosting, no Docker)

1. Upload / pull the code to your Hostinger account.
2. Install PHP dependencies (production, optimized):
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Build front-end assets **locally or in CI**, then upload the generated `public/build/`
   directory (shared hosting usually has no Node toolchain):
   ```bash
   npm install && npm run build
   ```
4. Set the domain's **document root to `/public`** in hPanel.
5. Configure `.env` with your Hostinger MySQL credentials:
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   DB_CONNECTION=mysql
   DB_HOST=localhost          # Hostinger MySQL is local to the account
   DB_PORT=3306
   DB_DATABASE=your_db_name
   DB_USERNAME=your_db_user
   DB_PASSWORD=your_db_password
   ```
6. Via SSH (or a one-off cron job), generate the key, migrate, cache config/routes, and link storage:
   ```bash
   php artisan key:generate
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan storage:link
   ```
7. Enable the **`imagick` PHP extension** in the hPanel PHP-extensions panel (required by the
   file manager / image features).

**Shared-hosting friendliness:** the app needs no queue workers, no websockets, and no
always-on processes (`QUEUE_CONNECTION=sync`). `robots.txt`/`sitemap.xml` are served
dynamically by Laravel. The `posts:publish-due` scheduler is **optional** — add a cron entry
if you use scheduled publishing, otherwise posts stay draft until manually published.
`php artisan config:cache` and `route:cache` are **safe** — the app reads no `env()` in views,
so cached config does not break rendering (verified: home returns `200`, admin returns `302` with
caches enabled).

### VPS (nginx + php-fpm + mysql)

You have two options on a VPS:

**A) Reuse the included Docker stack** (simplest):

```bash
git clone <repo> && cd cmstack-laravel
make dev              # single-command launch (or: docker compose up -d ...)
```

Then put your real reverse proxy / TLS in front (or expose port 8080 behind one).

**B) Native nginx + php-fpm + MySQL** (no Docker). Install PHP 8.3-FPM (with `imagick`,
`gd`, `intl`, `zip`, `bcmath`, `mbstring`, `pdo_mysql`), MySQL 8, and Node (for the build).
Deploy the code, then:

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
cp .env.example .env        # set APP_ENV=production, APP_DEBUG=false, DB_HOST=127.0.0.1, etc.
php artisan key:generate
php artisan migrate --force
php artisan config:cache && php artisan route:cache
php artisan storage:link
```

Example nginx server block (adapted from `docker/nginx/default.conf`, which is a working
reference config):

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/cmstack-laravel/public;
    index index.php index.html;
    charset utf-8;
    client_max_body_size 64M;            # sized for the media/file manager

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    # robots.txt is served dynamically by Laravel — do NOT add a static location for it.

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;   # or 127.0.0.1:9000
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* { deny all; }       # block .env, .git, etc.
}
```

Make sure `storage/` and `bootstrap/cache/` are writable by the php-fpm user.

### Is it ready to deploy?

**Yes.** Cmstack-Laravel runs cleanly **locally** (Docker via `make dev`, or fully manual), and
deploys to both **Hostinger shared hosting** and a **VPS** with no required background
services. A clean Docker bring-up was verified end to end: migrations + seeders succeed
against a fresh `cmstack_laravel` MySQL database, the home page returns `200`, `/cmstack-laravel-admin`
returns `302`, and `/sitemap.xml` returns `200` — and these stay correct with
`config:cache` + `route:cache` enabled (the production code path).

**Caveats / things to do per environment:**

- Provide real production `.env` values (`APP_KEY`, DB credentials, mail, and reCAPTCHA keys
  if you enable captcha) and set `APP_ENV=production`, `APP_DEBUG=false`.
- On shared hosting, **build assets off-host** (`npm run build`) and upload `public/build/`.
- Ensure the **`imagick`** PHP extension is enabled on the target.
- Run `php artisan storage:link` so public-disk media is served.
- Change the seeded admin password.
- If you use scheduled publishing, add a Laravel scheduler cron entry.

---

## Author & license

**Author**

- **Elman Hüseynov** — [huseyn0w](https://linkedin.com/in/huseyn0w) · [Elman Group](https://elman.group)

**License**

This project is licensed under the GNU General Public License v3 — see the
[LICENSE](LICENSE) file for details.
