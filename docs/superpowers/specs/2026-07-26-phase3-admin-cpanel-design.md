# Phase 3 — Admin cpanel on Inertia (shell + Dashboard + Categories) — Design

**Date:** 2026-07-26
**Branch:** `feat/inertia-migration` (HEAD after Phase 2 = `00dda4f`)
**Depends on:** Phase 1 (frontend i18n), Phase 2 (auth on Inertia)
**Plan:** `~/.claude/plans/wild-percolating-allen.md` (Phase 3 section)

## Goal

Migrate the first slice of the admin panel from Blade to Inertia + React: a persistent app shell (`CpanelLayout`), the Dashboard home, and the Categories resource end to end (list + create + edit + bulk-delete + soft-delete). Prove instant client-side navigation on real admin screens early, and unify the design tokens to the monochrome (Vercel) palette across the whole system.

All other cpanel resources (Posts, Pages, Services, Comments, Users, Roles, Menus, Media, Settings) are follow-on plans, one vertical slice each. They stay on Blade and keep working while this slice lands (strangler).

## Scope

In scope:
1. `CpanelLayout.tsx` — persistent Inertia layout: sidebar (grouped nav, permission-gated), translucent sticky topbar, flash area.
2. Dashboard home (`cpanel_home`) → Inertia, light content (latest 5 posts / users / comments) so the instant-nav chain Dashboard → Categories → Form is real.
3. Categories vertical slice → Inertia: List + Form (shared new/edit), bulk-delete, soft-delete, all three locales.
4. Monochrome token unification: redefine `resources/css/tokens.css` to the Vercel monochrome palette (whole-system). Re-verify Phase 2 auth screens read well in black/white.
5. Instant navigation: Inertia `<Link prefetch>` on sidebar + list links, verified to feel instant.
6. Test migration for the touched screens (AssertableInertia, Vitest, browser testids preserved).

Out of scope (explicit):
- All other cpanel resources (follow-on plans).
- Public site re-theming as separate work — not needed: the public Blade site reads the same `resources/css/tokens.css` (scope item 4 above), so it already flips to monochrome with the token swap. Remaining public work is a sweep for hardcoded warm classes (`bg-brand-*`, terracotta hexes) that bypass the tokens. Tracked in the backlog.
- Rich editor (`@tinymce/tinymce-react`) and the media picker — not needed for Categories; arrive with Posts/Pages/Services.
- Deleting legacy Blade admin views — happens in Phase 5.

## Architecture

### 1. App shell — `CpanelLayout.tsx`

A persistent Inertia layout assigned via the page component's `Page.layout` so the shell does NOT remount on navigation (state preserved, no flash). Structure mirrors the locked mockups (`admin-shell-mono-v3.html`):

- **Sidebar** (`data-testid="admin-sidebar"`): brand mark (solid black, white glyph, no gradient), grouped nav with small uppercase group labels (Main / Content / Settings). Active item = gradient-bevel border + `#f4f4f5` bg + black text/icon. Items use Inertia `<Link prefetch>`.
- **Topbar**: translucent sticky (`backdrop-filter: blur(14px)`), breadcrumb, search field, icon buttons, avatar.
- **Flash**: renders `flash.success` / `flash.error` / `flash.status` from shared props.

**Permission gating (no backend change needed):** `HandleInertiaRequests::share()` ALREADY exposes `auth.can` with every policy ability (`see_admin_panel`, `manage_post_categories`, `manage_posts`, `manage_pages`, `manage_services`, `manage_menus`, `manage_comments`, `manage_user_roles`, `manage_users`, `manage_general_settings`) — added in Phase 2. The React sidebar reads `auth.can[ability]` and hides (not disables) items the user cannot access, matching the old `@can` behavior. Note the alias mismatch that already exists in the codebase: the route middleware alias is `manage_categories` / `manage_roles`, but the policy/shared-prop ability is `manage_post_categories` / `manage_user_roles`. The sidebar keys off the POLICY names in `auth.can`.

**Theme:** same `agentic-cms-theme` localStorage key + `.dark` class established in Phase 2 (AuthLayout bootstrap). No new mechanism.

### 2. Design tokens — whole-system monochrome

Decision (2026-07-26): monochrome REPLACES the warm terracotta palette everywhere, not admin-only.

`resources/css/tokens.css` is the single source of truth; Phase 2 auth components (`Button`, `TextField`, `GoogleButton`, `AuthLayout`) and the Tailwind semantic bridge (`tailwind.config.js`) all read these variables. Redefining the token VALUES flips the whole system. New monochrome values (light):

```
--bg:#ffffff; --surface:#ffffff; --surface-2:#fafafa; --surface-3:#f4f4f5;
--text:#0a0a0a; --text-muted:#3f3f46; --text-subtle:#71717a; --text-faint:#a1a1aa;
--primary:#0a0a0a; --primary-hover:#242427; --primary-contrast:#ffffff;
--accent:#0a0a0a; --border:#e4e4e7; --border-strong:#d4d4d8; --ring:#0a0a0a;
--success:#15803d; --error:#dc2626;   /* semantic only — delete/error/success, never decoration */
```

Dark: near-black surfaces `#0a0a0a` / `#17171a`, zinc borders, white becomes the accent (white primary buttons). Radius/spacing/motion/container tokens are unchanged.

Premium edge treatment (the "not cheap 1px grey" requirement) is expressed as reusable utility classes / component styles, NOT raw token values:
- gradient-bevel border: `linear-gradient(180deg, rgba(9,9,11,.11), rgba(9,9,11,.045) 40%, rgba(9,9,11,.03))` via a padding-box / border-box background trick.
- layered floating shadow: `0 1px 2px rgba(9,9,11,.04), 0 10px 30px -12px rgba(9,9,11,.14)`.
- inset top highlight: `inset 0 1px 0 rgba(255,255,255,.7)`.
- table row separators very faint (`rgba(9,9,11,.06)`), rely on hover.

After the token swap, re-check the Phase 2 auth split-screen reads well in black/white (short visual pass; no structural change). Any auth screen that hard-coded a warm hex instead of a token is fixed to use the token.

### 3. Instant navigation (the core driver)

Sidebar links and the Categories list "Edit" links use Inertia `<Link prefetch>` (prefetch on hover/mount + client-side cache). The persistent layout means only the content region swaps. Verify the "feels instant" effect manually on Dashboard → Categories list → Category form, and add a browser assertion that a `<Link>` navigation does not trigger a full document reload.

### 4. Categories vertical slice

Backend routes and controller method names stay identical — only the controller return values change from `view(...)` to `Inertia::render(...)`, passing the same data as props. Routes (group `categories`, middleware `manage_categories`), all confirmed present:

| Method | URI | Name | Controller method |
| --- | --- | --- | --- |
| GET | `/` | `cpanel_category_list` | `index` |
| GET | `/new` | `cpanel_add_new_category` | `addCategory` |
| POST | `/new/{id?}` | `cpanel_save_new_category` | `createCategory` |
| GET | `/{id}/{lang}` | `cpanel_edit_category` | `edit` |
| PUT | `/{id}/update` | `cpanel_update_category` | `updateCategory` |
| DELETE | `/multipleDelete` | `cpanel_category_bulk_delete` | `multipleDelete` |
| DELETE | `/{id}/delete` | `cpanel_ajax_soft_delete_category` | `deleteAjax` |

- **List** → `Inertia::render('cpanel/categories/List', ['categories_list' => …])`. Paginated (`$per_page`). Renders the data table from the mockup: select-all + per-row checkbox, Title, Slug (mono font), Parent pill, row actions (Edit / Delete). Bulk bar appears when rows are selected; confirm control keeps `data-testid="bulk-delete-confirm"`. Row Delete and bulk Delete call Inertia `router.delete(...)` (replacing the legacy jQuery `.delete_*` / `.destroy_*` AJAX for this resource).
- **Form** (shared new + edit) → `Inertia::render('cpanel/categories/Form', ['entity' => …|null, 'parent_options' => …, 'translation_links' => …])`. Fields: title (required), slug (required, prefixed, auto-from-title), parent_category_id (select, depth-indented options), description (textarea), SEO rail (meta_description with counter, meta_keywords). Header carries the EN/DE/RU locale switcher wired to `translation_links`. Create posts to `cpanel_save_new_category`; edit PUTs to `cpanel_update_category`. Content is translatable → writes land in `category_translations` (title/slug/description/locale) exactly as today.
- **Layering preserved:** Controller → Service (`CPanelCategoryService`) → Repository. `tests/Arch/LayeringTest.php` must stay green (controllers never touch repositories). No business logic moves into the controller or React.

`CategoryRequest` / `CategoryListRequest` FormRequests are reused unchanged (same validation).

### 5. Dashboard home

`CPanelHomeController@index` (`cpanel_home`) → `Inertia::render('cpanel/Dashboard', …)` with light data (latest 5 posts / users / comments as already available). Uses `CpanelLayout`. This exists so the instant-nav chain and the admin browser test run through the React shell rather than a Blade page.

## Data flow

Request → `auth` + `see_admin_panel` middleware → resource permission middleware → thin controller → Service → Repository → controller returns `Inertia::render(component, props)` → `HandleInertiaRequests` merges shared props (`auth.user`, `auth.can`, `locale`, `messages`, `flash`) → React page renders inside persistent `CpanelLayout`. Mutations POST/PUT/DELETE via Inertia `router.*`; server redirects back with a flash; the shell shows it without remounting.

## Error handling

- Validation errors: FormRequest → Inertia validation-error bag → surfaced inline on the Form fields (Phase 2 pattern).
- Authorization: unchanged middleware returns 403; sidebar hides items the user cannot reach.
- Flash: server `->with('success'|'error'|'message', …)` → shared `flash` → shell banner.
- Bulk/soft delete: `router.delete` with confirm; on success redirect-back + flash; on failure the flash error banner.

## Testing strategy

- **Backend (Pest Feature, AssertableInertia):** for `index`, `addCategory`, `edit`, and the Dashboard — assert the correct component, presence/shape of props, permission gating, and validation, across en/de/ru. Convert `tests/Feature/Phase5AdminRenderTest.php` markup assertions to component/props assertions. `tests/Feature/Admin/*CrudTest` are transport-agnostic (assert DB rows / redirects) and stay green.
- **Frontend (Vitest + RTL):** `CpanelLayout` (renders sidebar, `data-testid="admin-sidebar"`, hides items when `auth.can` is false), Categories `List` (rows, select-all, bulk bar + `bulk-delete-confirm` testid), Categories `Form` (fields render, required markers, locale switcher).
- **Browser (Pest browser / Dusk):** preserve `admin-sidebar` + Phase 2 login testids; one scenario asserting `<Link>` navigation does not full-reload the document (instant-nav proof).
- Coverage gate consistent with prior phases.

## Follow-on (not this spec)

Per-resource slices after this one, same pattern: Posts, Pages, Services (these three bring `@tinymce/tinymce-react` + the LFM media bridge), Comments, Users, Roles, Menus, Media, Settings. Phase 4 (public + SSR) re-themes the public site to monochrome. Family canon (`DESIGN_SYSTEM.md` / per-stack instruction files) updated to reflect whole-system monochrome (was flagged admin-only).
