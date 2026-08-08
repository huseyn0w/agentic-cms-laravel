# Frontend: URL-driven locale, prefetch, SVG post covers — Design

**Status:** approved 2026-08-08

## Goal

Fix three public-frontend defects, on branch `fix/frontend-locale-images-prefetch`
(off `main`):

1. **Posts have no images** — a placeholder is shown. Render original,
   copyright-safe SVG cover art instead.
2. **No prefetch for other-language pages** — the language switcher does a full
   reload and cannot be prefetched.
3. **404/500 on language switch** for some posts/pages.

Issues 2 and 3 share a root cause: the public locale is **session-driven** with a
redirect dance. The fix makes the public locale **URL-driven**.

## Root cause (issues 2 & 3)

- `get_current_lang()` reads `Session::get('locale')` first
  (`bootstrap/agentic-cms-laravel-helpers.php:489`), falling back to the app
  locale.
- `Localization` middleware sets the locale from `session('locale')` (and the
  admin `lang` route param), never from the front `locale` route param.
- `BaseController::modifyTranslatedSlug()` switches language by writing the
  session and issuing `redirect()->refresh()` (`setLang()`).

Consequences:
- Switching to the **default** locale uses an un-prefixed URL
  (`/posts/<en-slug>`); that route carries no locale, so the stale session locale
  (e.g. `de`) survives and the English slug is looked up in the wrong locale →
  `resolveBySlug()` returns null → **404**.
- `get_translation_links()` finds the current entity with
  `where('slug', $slug)` **without** scoping to the current locale, so it can pick
  the wrong `post_id`/`page_id` and build a broken alternate URL → 404 (and wrong
  SEO hreflang).
- Because a language-switch URL mutates the session and 302-redirects,
  Inertia `<Link prefetch>` cannot be used on the switcher (hovering would change
  the language), so other-language pages are plain `<a>` full reloads.

## Design

### A. URL-driven public locale

**`Localization` middleware** (`app/Http/Middleware/Localization.php`) resolves the
locale with this precedence, and only the admin path touches the session:

1. Admin editor `lang` route param present → use it + `Session::put('locale', …)`
   (unchanged admin behavior).
2. Else, front `locale` route param present and a valid language → use it, **do
   not write the session**.
3. Else, request is an **admin** route (prefix `agentic-cms-laravel-admin`) →
   use `session('locale')` ?: default (preserves the admin language switcher).
4. Else (front route, no locale param) → use the **default** locale, ignoring any
   stale session value.

Then `App::setLocale($locale)` when the locale is valid.

**`get_current_lang()`** returns `app()->getLocale()` (set correctly per request
by the middleware — from the URL on the front, from the session in the admin).
The session-first read is removed.

**`BaseController`** (`app/Http/Controllers/BaseController.php`):
- Remove `setLang()` and the `redirect()->refresh()`.
- `modifyTranslatedSlug($locale, $slug)` keeps only the catch-all
  locale-vs-slug disambiguation: when `$locale` is not a language prefix and
  `$slug === '/'`, treat `$locale` as the slug. No redirect; the middleware has
  already set the locale from the URL.

**`ServiceController::show()`** (`app/Http/Controllers/ServiceController.php:27`)
uses the same `setLang()` pattern — apply the same removal so services behave
consistently.

**Admin unchanged:** `CPanelBaseController::setLang()`,
`CPanelLanguageController`, and the `lang` route param keep writing/reading the
session. The admin panel is not SEO- or prefetch-sensitive.

### B. `get_translation_links()` locale scoping

`bootstrap/agentic-cms-laravel-helpers.php:1047` — scope the initial entity lookup
to the current locale:

```php
$entity_id = $model->select($field_name)
    ->where('slug', $slug)
    ->where('locale', get_current_lang())
    ->first();
```

This makes the language switcher and the SEO hreflang alternates resolve the
correct entity. The rest of the URL building (target-locale slug, prefix) is
unchanged.

### C. Prefetchable language switcher

With other-language URLs now real Inertia pages (no session mutation, no
redirect), the switcher in `resources/js/layouts/PublicLayout.tsx` changes from
`<a href={lang.url}>` to Inertia `<Link href={lang.url} prefetch="hover">`. The
mobile drawer switcher (if any) changes identically. Post cards already use
`<Link prefetch="hover">`.

### D. SVG post covers (issue 1)

A React component `resources/js/components/public/PostCover.tsx`:

- Props: `{ seed: string; title?: string; className?: string }`.
- Renders a self-contained inline `<svg>` cover: a violet-family diagonal
  gradient (theme tokens `--g1/--g2/--g3`) plus a few geometric shapes whose
  positions/sizes derive **deterministically** from a hash of `seed` (the post
  slug), so a given post always gets the same cover. No external requests, no
  copyright exposure.
- Used wherever a post/page image renders and no real thumbnail exists:
  - `public/Post.tsx` hero (when `post.thumbnail` is null).
  - Home post cards, Archive post cards, and the related-posts strip.

**Server payload change:** the card/related props currently send
`image: image_src($x->thumbnail)` (already resolved to a placeholder path). To let
the client decide, send the **raw nullable thumbnail** plus a **seed**:
`thumbnail: $x->thumbnail ?: null` and `coverSeed: $x->slug`. The React card
shows `<img>` when `thumbnail` is set, else `<PostCover seed={coverSeed} …>`.
Affected: `PostController` (hero + related), `PageController` and the
Home/Archive controllers that build post cards.

## Testing (TDD)

**Backend (Pest feature):**
- On the default locale, requesting a non-default post via its localized URL
  (`/de/posts/<de-slug>`) renders the correct post; requesting the default post
  via `/posts/<en-slug>` while a stale `de` session exists still renders the
  English post (no 404).
- Language switch from `de` back to `en` resolves the correct entity (regression
  for the 404).
- `get_translation_links()` returns the alternate URL for the **correct** entity
  when two entities share a slug across locales.
- Admin language switching still works (session path unchanged): an admin editor
  route with a `lang` param sets that locale.

**Frontend (Vitest + RTL):**
- `PostCover` renders an `<svg>` and is deterministic: the same `seed` yields the
  same markup; different seeds differ.
- A post card with a `thumbnail` renders `<img>`; without one renders
  `<PostCover>`.
- The language switcher renders Inertia `<Link>` (prefetch) elements, not plain
  `<a>`.

## Out of scope

- Admin locale mechanism (stays session-based).
- OG/`og:image` generation for covers (SVG covers are client-rendered; the
  server `og:image` behavior is unchanged).
- Real stock photography (chosen approach is generated SVG).

## Risks

- Removing the session-first read in `get_current_lang()` could affect any code
  that set `session('locale')` expecting the front to honor it. Verified writers:
  `Localization`, `BaseController::setLang` (front, being removed),
  `CPanelBaseController::setLang` (admin, kept). Admin reads go through the
  session branch of the middleware, so the admin switcher is preserved. The plan
  re-greps consumers before finalizing.
