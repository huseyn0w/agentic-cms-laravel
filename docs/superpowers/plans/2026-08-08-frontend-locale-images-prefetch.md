# Frontend Locale / Prefetch / SVG Covers Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:executing-plans (inline TDD, user preference). Steps use checkbox (`- [ ]`) syntax.

**Goal:** Make the public locale URL-driven (fixes 404/500 on language switch and enables prefetch of other-language pages), and replace post placeholders with original deterministic SVG covers.

**Architecture:** The `Localization` middleware resolves the front locale from the URL `locale` route param (admin keeps its session `lang` path); `get_current_lang()` returns the app locale; `BaseController` drops the session/redirect language switch; `get_translation_links()` scopes its lookup to the current locale; the switcher becomes an Inertia prefetch `<Link>`; a `PostCover` React component renders SVG art where a thumbnail is absent.

**Tech Stack:** Laravel 12 / PHP 8.3, Inertia 3 + React 19 (TS), Pest, Vitest 4 + RTL, Tailwind.

## Global Constraints

- Branch `fix/frontend-locale-images-prefetch` (off `main`).
- Admin locale mechanism (session + `lang` route param, `CPanelBaseController::setLang`, `CPanelLanguageController`) stays UNCHANGED. Only the public front changes.
- Repository→Service→Controller layering; only repositories touch the ORM.
- Verify: backend `php artisan test --filter=<Class>`; arch `php -d memory_limit=1G artisan test tests/Arch/LayeringTest.php`; frontend `npx vitest run <file>`; style `vendor/bin/pint <files>`; static `composer analyse`; build `npm run build`.
- TDD: failing test first. Frequent commits. NO `Co-Authored-By` trailer.
- Design tokens: violet gradient `--g1/--g2/--g3`, theme `.theme-default`. SVG covers self-contained (no external requests).

---

## Task 1: URL-driven locale in middleware + `get_current_lang()`

**Files:**
- Modify: `app/Http/Middleware/Localization.php`
- Modify: `bootstrap/agentic-cms-laravel-helpers.php:489-499` (`get_current_lang`)
- Test: `tests/Feature/Front/LocaleResolutionTest.php` (new)

**Interfaces:**
- Produces: front requests resolve locale from the `locale` route param; `get_current_lang()` returns `app()->getLocale()`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Front/LocaleResolutionTest.php`:

```php
<?php

namespace Tests\Feature\Front;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_front_default_locale_ignores_stale_session(): void
    {
        // A stale session locale must NOT bleed into an un-prefixed front URL.
        $this->withSession(['locale' => 'ru'])->get('/')->assertStatus(200);

        $this->assertSame(config('app.locale'), app()->getLocale());
    }

    public function test_front_locale_comes_from_url_prefix(): void
    {
        $this->get('/ru')->assertStatus(200);

        $this->assertSame('ru', app()->getLocale());
    }

    public function test_get_current_lang_reflects_app_locale(): void
    {
        app()->setLocale('ru');

        $this->assertSame('ru', get_current_lang());
    }
}
```

- [ ] **Step 2: Run — expect fail**

Run: `php artisan test --filter=LocaleResolutionTest`
Expected: FAIL (stale session leaks; `get_current_lang` returns session).

- [ ] **Step 3: Rewrite the middleware**

Replace the body of `app/Http/Middleware/Localization.php` `handle()`:

```php
    public function handle($request, Closure $next)
    {
        $default = \Config::get('app.locale');
        $isAdmin = $request->is('agentic-cms-laravel-admin', 'agentic-cms-laravel-admin/*');

        // Admin editor routes carry a `lang` route param and persist it (admin
        // language switching stays session-based).
        if (! empty($request->route('lang'))) {
            $locale = $request->route('lang');
            \Session::put('locale', $locale);
        } elseif (! $isAdmin && lang_exist((string) $request->route('locale'))) {
            // Front: the URL prefix is the source of truth. Do NOT touch session.
            $locale = $request->route('locale');
        } elseif ($isAdmin) {
            // Admin list pages (no lang param): fall back to the stored locale.
            $locale = session('locale') ?: $default;
        } else {
            // Front, no locale prefix: default locale, ignoring any stale session.
            $locale = $default;
        }

        if (lang_exist($locale)) {
            \App::setLocale($locale);
        } else {
            \App::setLocale($default);
        }

        return $next($request);
    }
```

- [ ] **Step 4: Make `get_current_lang()` return the app locale**

In `bootstrap/agentic-cms-laravel-helpers.php`, replace `get_current_lang()`:

```php
function get_current_lang()
{
    // The Localization middleware sets the app locale per request: from the URL
    // prefix on the public front, from the session in the admin panel. Reading
    // it here (instead of the session) keeps the front locale URL-driven.
    return app()->getLocale();
}
```

- [ ] **Step 5: Run — expect pass**

Run: `php artisan test --filter=LocaleResolutionTest`
Expected: PASS.

- [ ] **Step 6: Guard against regressions in the admin locale path**

Run: `php artisan test --filter="Cpanel|CPanel|Admin|Locale"`
Expected: PASS (admin language switching still works via session).

- [ ] **Step 7: Pint + commit**

Run: `vendor/bin/pint app/Http/Middleware/Localization.php bootstrap/agentic-cms-laravel-helpers.php tests/Feature/Front/LocaleResolutionTest.php`

```bash
git add app/Http/Middleware/Localization.php bootstrap/agentic-cms-laravel-helpers.php tests/Feature/Front/LocaleResolutionTest.php
git commit -m "Front locale is URL-driven: middleware reads the locale prefix, get_current_lang returns the app locale"
```

---

## Task 2: Drop the session/redirect language switch in the front controllers

**Files:**
- Modify: `app/Http/Controllers/BaseController.php`
- Modify: `app/Http/Controllers/ServiceController.php:20-30`
- Test: `tests/Feature/Front/LanguageSwitchTest.php` (new)

**Interfaces:**
- Consumes: URL-driven locale (Task 1).
- Produces: `BaseController::index()` resolves the slug in the URL locale with no redirect.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Front/LanguageSwitchTest.php`. This is the core 404 regression — a default-locale post fetched while a stale non-default session exists must resolve, not 404:

```php
<?php

namespace Tests\Feature\Front;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_default_locale_post_resolves_despite_stale_session(): void
    {
        // Seeded post slug "introducing-the-cms" (en). A stale ru session must
        // not cause the en slug to be looked up in ru → 404.
        $this->withSession(['locale' => 'ru'])
            ->get('/posts/introducing-the-cms')
            ->assertStatus(200);
    }

    public function test_localized_post_route_does_not_redirect(): void
    {
        // The localized route renders directly (200), no 302 language redirect.
        $this->get('/en/posts/introducing-the-cms')->assertStatus(200);
    }
}
```

- [ ] **Step 2: Run — expect fail**

Run: `php artisan test --filter=LanguageSwitchTest`
Expected: the stale-session test FAILS (404) on current code.

- [ ] **Step 3: Simplify `BaseController`**

Replace `app/Http/Controllers/BaseController.php` methods `setLang` / `index` / `modifyTranslatedSlug` with:

```php
    protected function index(string $slug, ?string $locale = null)
    {
        if (is_null($locale)) {
            $locale = get_current_lang();
        }

        $slug = $this->modifyTranslatedSlug($locale, $slug);

        $this->data = $this->service->resolveBySlug($slug);

        if (is_null($this->data)) {
            throwNotFound();
        }

        return true;
    }

    /**
     * Catch-all disambiguation for /{locale?}/{slug?}: when the first segment is
     * NOT a language prefix and no slug was given, treat that segment as the
     * slug (a default-locale page like /about). The locale itself is already
     * resolved from the URL by the Localization middleware, so there is no
     * language switch / redirect here.
     */
    protected function modifyTranslatedSlug($locale, $slug)
    {
        if (! in_array($locale, $this->lang_prefixes) && $slug === '/') {
            $slug = $locale;
        }

        return $slug;
    }
```

Remove the `setLang()` method entirely.

- [ ] **Step 4: Update `ServiceController`**

In `app/Http/Controllers/ServiceController.php`, the `show()` method calls `$this->setLang($locale)` when the locale differs. Remove that branch — the middleware sets the locale from the URL. Read the current method and delete the `setLang` call and its surrounding conditional, keeping the slug resolution. (The exact lines: the `if (... !== get_current_lang()) return $this->setLang($locale);` block around line 27.)

- [ ] **Step 5: Run — expect pass**

Run: `php artisan test --filter=LanguageSwitchTest`
Expected: PASS.

- [ ] **Step 6: Broader front regression**

Run: `php artisan test tests/Feature/Front`
Expected: PASS (existing localized-route tests still green; any test asserting a 302 language redirect is updated to expect 200 — fix those in place).

- [ ] **Step 7: Pint + commit**

Run: `vendor/bin/pint app/Http/Controllers/BaseController.php app/Http/Controllers/ServiceController.php tests/Feature/Front/LanguageSwitchTest.php`

```bash
git add app/Http/Controllers/BaseController.php app/Http/Controllers/ServiceController.php tests/Feature/Front/LanguageSwitchTest.php
git commit -m "Front: resolve slug in the URL locale without the session redirect dance"
```

---

## Task 3: Locale-scope `get_translation_links()`

**Files:**
- Modify: `bootstrap/agentic-cms-laravel-helpers.php:1047`
- Test: `tests/Feature/Front/TranslationLinksTest.php` (new)

**Interfaces:**
- Produces: `get_translation_links()` resolves the current entity within the current locale.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Front/TranslationLinksTest.php`:

```php
<?php

namespace Tests\Feature\Front;

use App\Http\Models\PostTranslation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_translation_links_pick_the_entity_in_the_current_locale(): void
    {
        // Two different posts share the slug "shared" across locales: post A in en,
        // post B in ru. Viewing the en one must produce the ru alternate for post A,
        // not post B.
        PostTranslation::where('locale', 'en')->limit(1)->update(['slug' => 'shared']);
        $enPostId = PostTranslation::where('locale', 'en')->where('slug', 'shared')->value('post_id');
        // Give post A a distinct ru slug, and make a *different* ru row also "shared".
        PostTranslation::where('post_id', $enPostId)->where('locale', 'ru')->update(['slug' => 'obshchij']);
        PostTranslation::where('locale', 'ru')->where('post_id', '!=', $enPostId)->limit(1)->update(['slug' => 'shared']);

        $this->get('/posts/shared')->assertStatus(200);

        $links = $this->get('/posts/shared')->viewData('data') !== null
            ? get_translation_links_for_test()
            : [];

        // Instead of internals, assert the rendered page exposes the correct ru
        // alternate via the shell language switcher.
        $this->assertTrue(true);
    }
}

if (! function_exists('get_translation_links_for_test')) {
    function get_translation_links_for_test(): array
    {
        return get_translation_links();
    }
}
```

NOTE: the internal-call approach is brittle. Prefer asserting via the shared shell. Replace the test body with a direct unit-style assertion by simulating the request context:

```php
    public function test_translation_links_pick_the_entity_in_the_current_locale(): void
    {
        $en = PostTranslation::where('locale', 'en')->orderBy('post_id')->first();
        $en->slug = 'shared';
        $en->save();

        $ru = PostTranslation::where('locale', 'ru')->where('post_id', $en->post_id)->first();
        $ru->slug = 'obshchij-a';
        $ru->save();

        // A different post's ru row also uses "shared" — the un-scoped lookup
        // would wrongly match this one.
        $otherRu = PostTranslation::where('locale', 'ru')->where('post_id', '!=', $en->post_id)->first();
        $otherRu->slug = 'shared';
        $otherRu->save();

        $response = $this->get('/posts/shared')->assertStatus(200);

        // The page's shared shell carries the language switcher; the ru alternate
        // must point at post A's ru slug (obshchij-a), not the other post.
        $response->assertInertia(fn ($p) => $p->where(
            'shell.languages',
            fn ($langs) => collect($langs)->contains(fn ($l) => str_contains((string) ($l['url'] ?? ''), 'obshchij-a'))
        ));
    }
```

- [ ] **Step 2: Run — expect fail**

Run: `php artisan test --filter=TranslationLinksTest`
Expected: FAIL (un-scoped lookup matches the wrong post → ru URL is wrong or falls back).

- [ ] **Step 3: Scope the lookup to the current locale**

In `bootstrap/agentic-cms-laravel-helpers.php` around line 1047, change:

```php
        $entity_id = $model->select($field_name)->where('slug', $slug)->first();
```

to:

```php
        $entity_id = $model->select($field_name)
            ->where('slug', $slug)
            ->where('locale', get_current_lang())
            ->first();
```

- [ ] **Step 4: Run — expect pass**

Run: `php artisan test --filter=TranslationLinksTest`
Expected: PASS.

- [ ] **Step 5: Pint + commit**

Run: `vendor/bin/pint bootstrap/agentic-cms-laravel-helpers.php tests/Feature/Front/TranslationLinksTest.php`

```bash
git add bootstrap/agentic-cms-laravel-helpers.php tests/Feature/Front/TranslationLinksTest.php
git commit -m "Front: scope translation-link entity lookup to the current locale (fixes wrong alternates + hreflang)"
```

---

## Task 4: Prefetchable language switcher

**Files:**
- Modify: `resources/js/layouts/PublicLayout.tsx`
- Modify: `resources/js/layouts/PublicLayout.test.tsx`

**Interfaces:**
- Consumes: `shell.languages[].url` (now real Inertia pages).
- Produces: switcher links are Inertia `<Link prefetch="hover">`.

- [ ] **Step 1: Add a failing assertion**

In `resources/js/layouts/PublicLayout.test.tsx`, add a test that the locale switcher renders Inertia `<Link>` (the mock `Link` renders `<a data-inertia>`). First update the inertia mock so `Link` marks itself:

```tsx
    Link: ({ children, prefetch, cacheFor, ...p }: any) => <a data-inertia="true" {...p}>{children}</a>,
```

Then add:

```tsx
    it('renders the language switcher as prefetching Inertia links', () => {
        render(<PublicLayout shell={makeShell()}>{null}</PublicLayout>);
        const switcher = screen.getByTestId('locale-switcher');
        const links = switcher.querySelectorAll('a[data-inertia="true"]');
        expect(links.length).toBeGreaterThan(0);
    });
```

- [ ] **Step 2: Run — expect fail**

Run: `npx vitest run resources/js/layouts/PublicLayout.test.tsx`
Expected: FAIL (switcher renders plain `<a>`, no `data-inertia`).

- [ ] **Step 3: Convert the switcher to `<Link prefetch>`**

In `resources/js/layouts/PublicLayout.tsx`, the desktop locale switcher maps `shell.languages` to `<a href={lang.url} …>`. Change each to:

```tsx
                                    <Link
                                        key={lang.code}
                                        href={lang.url}
                                        prefetch="hover"
                                        data-testid={`lang-${lang.code.toLowerCase()}`}
                                        aria-current={lang.current ? 'true' : undefined}
                                        className={/* unchanged classes */}
                                    >
                                        {lang.code}
                                    </Link>
```

Keep `Link` imported (already imported at the top). The current-locale entry may have `url === null`; guard: render a non-link `<span>` when `lang.current` (its `url` is null) to avoid an Inertia visit to `null`.

- [ ] **Step 4: Run — expect pass**

Run: `npx vitest run resources/js/layouts/PublicLayout.test.tsx`
Expected: PASS.

- [ ] **Step 5: Build + commit**

Run: `npm run build`

```bash
git add resources/js/layouts/PublicLayout.tsx resources/js/layouts/PublicLayout.test.tsx
git commit -m "Front: language switcher uses Inertia prefetch links (other-language pages preload on hover)"
```

---

## Task 5: `PostCover` SVG component

**Files:**
- Create: `resources/js/components/public/PostCover.tsx`
- Create: `resources/js/components/public/PostCover.test.tsx`

**Interfaces:**
- Produces: `PostCover({ seed: string; title?: string; className?: string })` → deterministic inline `<svg>`.

- [ ] **Step 1: Write the failing test**

Create `resources/js/components/public/PostCover.test.tsx`:

```tsx
import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/react';
import { PostCover } from './PostCover';

describe('PostCover', () => {
  it('renders an svg cover', () => {
    const { container } = render(<PostCover seed="hello-world" />);
    expect(container.querySelector('svg')).toBeTruthy();
  });

  it('is deterministic for the same seed and differs across seeds', () => {
    const a = render(<PostCover seed="alpha" />).container.innerHTML;
    const b = render(<PostCover seed="alpha" />).container.innerHTML;
    const c = render(<PostCover seed="beta" />).container.innerHTML;
    expect(a).toBe(b);
    expect(a).not.toBe(c);
  });
});
```

- [ ] **Step 2: Run — expect fail**

Run: `npx vitest run resources/js/components/public/PostCover.test.tsx`
Expected: FAIL (module missing).

- [ ] **Step 3: Implement `PostCover`**

Create `resources/js/components/public/PostCover.tsx`:

```tsx
interface PostCoverProps {
  seed: string;
  title?: string;
  className?: string;
}

/** Small deterministic string hash (FNV-1a-ish) → 32-bit unsigned int. */
function hash(seed: string): number {
  let h = 2166136261;
  for (let i = 0; i < seed.length; i++) {
    h ^= seed.charCodeAt(i);
    h = Math.imul(h, 16777619);
  }
  return h >>> 0;
}

/**
 * Original, copyright-safe cover art for posts without a thumbnail. A violet
 * diagonal gradient (theme tokens) plus a few geometric shapes whose positions
 * derive deterministically from the seed (post slug), so a post always gets the
 * same cover. Fully self-contained — no external requests.
 */
export function PostCover({ seed, title, className }: PostCoverProps) {
  const h = hash(seed);
  const gid = `pc-${(h % 100000).toString(36)}`;
  // Derive a few stable shape params from disjoint bit-slices of the hash.
  const cx1 = 15 + (h % 40);
  const cy1 = 20 + ((h >> 4) % 50);
  const r1 = 12 + ((h >> 8) % 22);
  const cx2 = 55 + ((h >> 12) % 40);
  const cy2 = 40 + ((h >> 16) % 45);
  const r2 = 8 + ((h >> 20) % 18);
  const rot = (h >> 24) % 360;

  return (
    <svg
      viewBox="0 0 100 56"
      preserveAspectRatio="xMidYMid slice"
      role="img"
      aria-label={title ? `${title} cover` : 'Post cover'}
      className={className}
    >
      <defs>
        <linearGradient id={gid} x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="var(--g1)" />
          <stop offset="45%" stopColor="var(--g2)" />
          <stop offset="100%" stopColor="var(--g3)" />
        </linearGradient>
      </defs>
      <rect width="100" height="56" fill={`url(#${gid})`} />
      <g opacity="0.18" fill="#ffffff" transform={`rotate(${rot} 50 28)`}>
        <circle cx={cx1} cy={cy1} r={r1} />
        <circle cx={cx2} cy={cy2} r={r2} />
        <rect x={cx1} y={cy2} width={r2 * 1.5} height={r2 * 1.5} rx="2" />
      </g>
    </svg>
  );
}
```

- [ ] **Step 4: Run — expect pass**

Run: `npx vitest run resources/js/components/public/PostCover.test.tsx`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/public/PostCover.tsx resources/js/components/public/PostCover.test.tsx
git commit -m "Front: deterministic SVG PostCover component (copyright-safe post art)"
```

---

## Task 6: Wire `PostCover` into the post hero + related strip

**Files:**
- Modify: `app/Http/Controllers/PostController.php` (hero + related payload)
- Modify: `resources/js/pages/public/Post.tsx`

**Interfaces:**
- Consumes: `PostCover` (Task 5).
- Produces: `post.coverSeed` and `related[].thumbnail` + `related[].coverSeed` props.

- [ ] **Step 1: Add cover seeds to the payload**

In `app/Http/Controllers/PostController.php` `renderPostPage()`:
- Add to the `post` array: `'coverSeed' => $post->slug ?? (string) $post->id,`.
- In the `related` map, replace `'image' => image_src($r->thumbnail),` with:
  `'thumbnail' => $r->thumbnail ?: null,` and `'coverSeed' => $r->slug,`.

- [ ] **Step 2: Render the cover in `Post.tsx`**

Import at the top: `import { PostCover } from '@/components/public/PostCover';`

Update the `Post` prop type: add `coverSeed: string;` to the `post` shape; change related item type from `image: string` to `thumbnail: string | null; coverSeed: string`.

Hero (around line 102) — replace the `{post.thumbnail && (…<img…/>…)}` block so it always shows something:

```tsx
                <figure className="mb-8 overflow-hidden rounded-lg border border-[var(--border)]">
                    {post.thumbnail ? (
                        <img src={post.thumbnail} alt={post.title} width={1280} height={720} className="aspect-[16/9] w-full object-cover" />
                    ) : (
                        <PostCover seed={post.coverSeed} title={post.title} className="aspect-[16/9] w-full" />
                    )}
                </figure>
```

Related strip — where each related card renders `r.image`, render:

```tsx
                {r.thumbnail ? (
                    <img src={r.thumbnail} alt={r.title} className="aspect-[16/9] w-full object-cover" />
                ) : (
                    <PostCover seed={r.coverSeed} title={r.title} className="aspect-[16/9] w-full" />
                )}
```

- [ ] **Step 3: Update the Post test fixtures**

`resources/js/pages/public/Post.test.tsx` builds a `post` prop and related items. Add `coverSeed: 'x'` to the post fixture and swap related `image` → `thumbnail: null, coverSeed: 'y'`. Keep assertions green.

- [ ] **Step 4: Run tests + build**

Run: `npx vitest run resources/js/pages/public/Post.test.tsx`
Expected: PASS.
Run: `php artisan test --filter="Post|Preview"` (server payload still renders)
Expected: PASS.
Run: `npm run build`

- [ ] **Step 5: Pint + commit**

Run: `vendor/bin/pint app/Http/Controllers/PostController.php`

```bash
git add app/Http/Controllers/PostController.php resources/js/pages/public/Post.tsx resources/js/pages/public/Post.test.tsx
git commit -m "Front: post hero + related use PostCover when no thumbnail"
```

---

## Task 7: Wire `PostCover` into Home + Archive cards

**Files:**
- Modify: `app/Http/Controllers/PageController.php` (home post cards, ~line 170-178)
- Modify: `app/Services/Front/PublicArchive.php` (archive cards, ~line 32-37)
- Modify: `resources/js/pages/public/Home.tsx`
- Modify: `resources/js/pages/public/Archive.tsx`

**Interfaces:**
- Consumes: `PostCover` (Task 5).
- Produces: card props carry `thumbnail: string|null` + `coverSeed: string` instead of a resolved `image`.

- [ ] **Step 1: Home controller payload**

In `app/Http/Controllers/PageController.php` (~line 171-177), replace
`'image' => image_src($post->thumbnail),` with:
`'thumbnail' => $post->thumbnail ?: null,` and `'coverSeed' => $post->slug,`.

- [ ] **Step 2: Archive service payload**

In `app/Services/Front/PublicArchive.php` (~line 32-37), replace
`'image' => image_src($post->thumbnail),` with:
`'thumbnail' => $post->thumbnail ?: null,` and `'coverSeed' => $post->slug,`.

- [ ] **Step 3: Render covers in `Home.tsx` and `Archive.tsx`**

Import `PostCover` in both. Change the post-card type `image: string` → `thumbnail: string | null; coverSeed: string`. Replace each `<img src={post.image} …/>` with:

```tsx
                {post.thumbnail ? (
                    <img src={post.thumbnail} alt="" className={/* unchanged */} />
                ) : (
                    <PostCover seed={post.coverSeed} className={/* same sizing classes as the img */} />
                )}
```

(Home hero `hero.background` image is separate — leave it unchanged.)

- [ ] **Step 4: Update Home + Archive test fixtures**

`Home.test.tsx` and `Archive.test.tsx` build post cards with `image`. Swap to `thumbnail: null, coverSeed: 's'` (and keep any test that used a real image by setting `thumbnail: 'http://x/y.jpg'`). Keep assertions green.

- [ ] **Step 5: Run tests + build**

Run: `npx vitest run resources/js/pages/public/Home.test.tsx resources/js/pages/public/Archive.test.tsx`
Expected: PASS.
Run: `php artisan test --filter="PublicPages|Archive|Home|Category|Tag"`
Expected: PASS.
Run: `npm run build`

- [ ] **Step 6: Pint + commit**

Run: `vendor/bin/pint app/Http/Controllers/PageController.php app/Services/Front/PublicArchive.php`

```bash
git add app/Http/Controllers/PageController.php app/Services/Front/PublicArchive.php resources/js/pages/public/Home.tsx resources/js/pages/public/Archive.tsx resources/js/pages/public/Home.test.tsx resources/js/pages/public/Archive.test.tsx
git commit -m "Front: Home + Archive cards use PostCover when no thumbnail"
```

---

## Task 8: Full regression

- [ ] **Step 1: Backend**

Run: `php artisan test --exclude-group=arch`
Expected: PASS (fix any localized-route/redirect assertions left over).
Run: `php -d memory_limit=1G artisan test tests/Arch/LayeringTest.php`
Expected: PASS.

- [ ] **Step 2: Frontend + static + build**

Run: `npx vitest run`
Expected: PASS.
Run: `composer analyse`
Expected: PASS.
Run: `vendor/bin/pint --test`
Expected: PASS.
Run: `npm run build`
Expected: succeeds.

- [ ] **Step 3: Manual smoke (report only)**

Note for the user to verify locally: switch language on a post and a page (no 404/500), hover a language link (network shows prefetch), and confirm posts without thumbnails show the SVG cover.

---

## Self-Review

- **Issue 3 (404/500):** Tasks 1-3 make locale URL-driven, drop the redirect, and scope translation-link lookups. ✅
- **Issue 2 (prefetch):** Task 4 switches the language switcher to Inertia prefetch links, enabled by Tasks 1-2. ✅
- **Issue 1 (images):** Tasks 5-7 add `PostCover` and wire it into hero, related, Home, and Archive. ✅
- **Admin preserved:** Task 1 keeps the admin session/`lang` path; Task 8 runs the admin suite. ✅
- **Type consistency:** `coverSeed: string` + `thumbnail: string | null` are used identically across controllers, services, and the four React pages. ✅
- **No placeholders:** every code step shows the code. Tasks 2/6/7 name the exact files/lines to touch where the surrounding code is read in place.
