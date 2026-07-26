# Posts cpanel slice — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Migrate the admin Posts resource (list + trashed + form + revisions) from Blade to Inertia+React, with a TinyMCE React rich editor and an LFM media bridge, and fix the `title`/`slug` `max:20` bug.

**Architecture:** Follows the Categories slice (persistent `AdminLayout`, monochrome tokens, `useForm`/`router`, `tr()` i18n, `AssertableInertia`). Two new reusable pieces: `RichText` (TinyMCE v4 via `@tinymce/tinymce-react`, self-hosted, no CDN) and `useLfmPicker`/`MediaField` (popup + `window.SetUrl` bridge to laravel-filemanager). Controllers change return-only (`view()`→`Inertia::render()`); observers/service/repository untouched.

**Tech Stack:** Laravel 12/PHP 8.3, inertiajs/inertia-laravel, React 19 + TS, Vite, Tailwind, `@tinymce/tinymce-react` + bundled `tinymce@^4.9.11`, react-i18next, Vitest, Pest.

## Global Constraints

- **Monochrome** design tokens/classes only (`.admin-card`/`.admin-bevel`/`.admin-sep`/`.admin-nav-active`, `bg-primary`, `text-fg/muted/faint`, `text-error/success`). No new color literals; red/green semantic only.
- **No CDN** — TinyMCE loads from the locally bundled `tinymce` v4 package (import theme/plugins/skins), never a cloud script.
- **Preserve backend contracts:** route names, controller method names, and the `/{id}/restore` before `/{id}/{lang}` ordering. Controllers change return value only; `CPanelPostService`, `CPanelPostRepository`, `PostObserver`, `PostTranslationObserver`, `ManagesRevisions` UNTOUCHED. Fields `content`/`preview`/`category`/`tags` travel as real POST fields (observers read `app('request')`).
- **Reuse FormRequests:** `ValidatePostData`, `PostListRequest` — only change is the `max:20`→`max:255` on `title`/`slug`.
- **Field names verbatim** from `ValidatePostData`: `title, slug, content, preview, author_id, meta_keywords, meta_description, canonical_url, meta_noindex, created_at, updated_at, category (array), tags, thumbnail, status, scheduled_at`.
- **Permission** gating via shared `auth.can.manage_posts`. Preserve testids (`admin-sidebar`, `bulk-delete-confirm`); new post testids `post-title`, `post-slug`, `post-submit`.
- **Delete flow:** row + bulk go through the redirecting bulk endpoints (like Categories); `deleteAjax` (plain-text echo) stays untouched, dead for posts (Phase 5). Trashed tab uses `cpanel_posts_bulk_action` (`posts_action` restore/destroy).
- **i18n:** `useTranslation()` + `tr(key, fallback)`; add referenced keys to `resources/lang/{en,de,ru}/cpanel/posts.php` (and reuse `cpanel/menu.php` from the Categories slice).
- **Commits:** plain messages, NO `Co-Authored-By` trailer. TDD, frequent commits.

---

## File Structure

**Create (frontend):**
- `resources/js/components/RichText.tsx` (+ test) — TinyMCE React wrapper.
- `resources/js/lib/lfm.ts` (`useLfmPicker`) + `resources/js/components/MediaField.tsx` (+ tests) — LFM bridge + featured-image field.
- `resources/js/pages/cpanel/posts/List.tsx` (+ test)
- `resources/js/pages/cpanel/posts/Form.tsx` (+ test)
- `resources/js/pages/cpanel/posts/Revisions.tsx`, `RevisionDiff.tsx` (+ tests)

**Modify (backend):**
- `app/Http/Requests/ValidatePostData.php` — `max` fix.
- `app/Http/Controllers/CPanel/CPanelPostController.php` — `index`, `trashedPosts`, `addPost`, `editPost`, `revisions`, `revisionDiff` → `Inertia::render`; add `flash.success` on create/update/delete/restore.
- `tests/Feature/Phase5AdminRenderTest.php` — convert posts route assertions.
- `resources/lang/{en,de,ru}/cpanel/posts.php` — add UI keys.
- `package.json` — add `@tinymce/tinymce-react`.

**Create (tests):** `tests/Feature/CPanel/PostInertiaRenderTest.php`, `tests/Feature/CPanel/PostFlashTest.php`, a validation-fix unit/feature test.

**Untouched (stay green):** `tests/Feature/Admin/PostCrudTest.php`, `PostScheduleFormTest.php`; the service/repo/observers; `deleteAjax`.

---

## Task 1: RichText component (de-risk the editor first)

**Files:** Modify `package.json`; Create `resources/js/components/RichText.tsx`, `RichText.test.tsx`.

**Interfaces:**
- Produces: `RichText({ id, name, value, onChange, height }: { id: string; name: string; value: string; onChange: (html: string) => void; height?: number })` — a controlled TinyMCE editor.

- [ ] **Step 1: Add the dependency**

Install a `@tinymce/tinymce-react` version compatible with the bundled TinyMCE **v4** (`tinymce@^4.9.11` is already a dependency). The 3.x line of `@tinymce/tinymce-react` supports TinyMCE 4/5. Run: `npm install @tinymce/tinymce-react@^3.14.0` (verify it resolves against React 19 with `.npmrc legacy-peer-deps=true` already present; if the 3.x peer range rejects React 19 even with legacy-peer-deps, report back before forcing).

- [ ] **Step 2: Write the failing test**

`resources/js/components/RichText.test.tsx` — mock `@tinymce/tinymce-react` at the module boundary (the real editor can't boot in jsdom); assert `RichText` renders the mocked editor, passes the initial `value`, and forwards editor changes to `onChange`.

```tsx
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@tinymce/tinymce-react', () => ({
  Editor: ({ value, onEditorChange, id }: any) => (
    <textarea data-testid={`tinymce-${id}`} value={value}
      onChange={(e) => onEditorChange(e.target.value)} />
  ),
}));
// Prevent the self-hosted tinymce side-effect imports from loading in jsdom:
vi.mock('tinymce/tinymce', () => ({}));
vi.mock('tinymce/themes/modern', () => ({}), { virtual: true });

import { RichText } from './RichText';

describe('RichText', () => {
  it('renders with the initial value and emits changes', () => {
    const onChange = vi.fn();
    render(<RichText id="content" name="content" value="<p>hi</p>" onChange={onChange} />);
    const ed = screen.getByTestId('tinymce-content');
    expect(ed).toHaveValue('<p>hi</p>');
    fireEvent.change(ed, { target: { value: '<p>bye</p>' } });
    expect(onChange).toHaveBeenCalledWith('<p>bye</p>');
  });
});
```

- [ ] **Step 3: Run it — FAIL** (`npx vitest run resources/js/components/RichText.test.tsx`) — "Cannot find module './RichText'".

- [ ] **Step 4: Implement `RichText.tsx`**

Import the self-hosted TinyMCE v4 core + the theme/plugins the config uses so nothing loads from a CDN, then render `<Editor>` in bundled mode. Port the legacy init (plugins, toolbar, `relative_urls:false`, `file_browser_callback` → `/filemanager`). Exact side-effect import paths for TinyMCE v4 (`tinymce/tinymce`, `tinymce/themes/modern/theme`, `tinymce/plugins/*`, `tinymce/skins/...`) must be resolved by the implementer against the installed package — if a plugin path doesn't exist in the v4 package layout, trim it and report which. The `file_browser_callback` body is copied from `public/admin/js/agentic-cms-laravel.js` (opens `/filemanager?field_name=...&type=Images|Files` in `tinymce.activeEditor.windowManager`).

```tsx
import { Editor } from '@tinymce/tinymce-react';
// Self-hosted TinyMCE v4 — no CDN. (Implementer: confirm these paths against the installed v4 package.)
import 'tinymce/tinymce';
import 'tinymce/themes/modern/theme';
// import the plugins referenced in the toolbar/plugins list...

const PLUGINS = 'advlist autolink lists link image charmap print preview hr anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking save table contextmenu directionality emoticons template paste textcolor colorpicker textpattern';
const TOOLBAR = 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media';

export function RichText({ id, name, value, onChange, height = 300 }: {
  id: string; name: string; value: string; onChange: (html: string) => void; height?: number;
}) {
  return (
    <Editor
      id={id}
      value={value}
      onEditorChange={(html) => onChange(html)}
      init={{
        height, menubar: false, branding: false, relative_urls: false, plugins: PLUGINS, toolbar: TOOLBAR,
        file_browser_callback: (field_name: string, _url: string, type: string, win: any) => {
          const cmsURL = '/filemanager?field_name=' + field_name + (type === 'image' ? '&type=Images' : '&type=Files');
          (window as any).tinyMCE.activeEditor.windowManager.open({
            file: cmsURL, title: 'Filemanager',
            width: window.innerWidth * 0.8, height: window.innerHeight * 0.8, resizable: 'yes', close_previous: 'no',
          });
        },
      }}
    />
  );
}
```

- [ ] **Step 5: Run it — PASS** (`npx vitest run resources/js/components/RichText.test.tsx`).

- [ ] **Step 6: No-CDN + build smoke**

Run `npm run build`. Confirm it succeeds and that the TinyMCE assets are bundled locally (grep the build manifest / assets for tinymce; there must be no request to `cloud.tinymce.com`/`cdn.tiny.cloud` — grep the built JS: `grep -ri 'tiny.cloud\|tinymce.com' public/build/assets || echo "no CDN refs"`). Report bundle-size delta.

- [ ] **Step 7: Commit** — `feat(posts): RichText TinyMCE v4 React wrapper (self-hosted, LFM file browser)`.

---

## Task 2: LFM media bridge (`useLfmPicker` + `MediaField`)

**Files:** Create `resources/js/lib/lfm.ts`, `resources/js/components/MediaField.tsx` (+ tests).

**Interfaces:**
- Produces: `useLfmPicker(onPick: (url: string) => void): { open: (type?: 'Images'|'Files', field?: string) => void }`; `MediaField({ value, onChange, label }: { value: string; onChange: (url: string) => void; label?: string })`.

- [ ] **Step 1: Write the failing test for the bridge**

`resources/js/lib/lfm.test.ts` — LFM's picker calls `window.SetUrl(url)` on the opener. Assert `open()` sets `window.SetUrl` and calls `window.open`, and that invoking `window.SetUrl('/storage/x.jpg')` fires `onPick` with the url.

```ts
import { renderHook, act } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { useLfmPicker } from './lfm';

describe('useLfmPicker', () => {
  beforeEach(() => { (window as any).open = vi.fn(() => ({ close: vi.fn() })); delete (window as any).SetUrl; });
  it('opens the LFM popup and receives the picked url via SetUrl', () => {
    const onPick = vi.fn();
    const { result } = renderHook(() => useLfmPicker(onPick));
    act(() => result.current.open('Images', 'thumbnail'));
    expect(window.open).toHaveBeenCalledWith(
      expect.stringContaining('/filemanager?type=Images&field_name=thumbnail'),
      expect.any(String), expect.any(String));
    expect(typeof (window as any).SetUrl).toBe('function');
    act(() => (window as any).SetUrl('/storage/x.jpg'));
    expect(onPick).toHaveBeenCalledWith('/storage/x.jpg');
  });
});
```

- [ ] **Step 2: Run it — FAIL.**

- [ ] **Step 3: Implement `lfm.ts`**

```ts
import { useCallback } from 'react';

export function useLfmPicker(onPick: (url: string) => void) {
  const open = useCallback((type: 'Images' | 'Files' = 'Images', field = 'thumbnail') => {
    (window as any).SetUrl = (url: string) => { onPick(url); popup?.close?.(); };
    const popup = window.open(
      `/filemanager?type=${type}&field_name=${field}`,
      'lfm', `width=${Math.round(window.innerWidth * 0.7)},height=${Math.round(window.innerHeight * 0.7)}`,
    );
  }, [onPick]);
  return { open };
}
```

- [ ] **Step 4: Run it — PASS.**

- [ ] **Step 5: Write `MediaField.test.tsx`** — asserts: renders the preview `<img>` when `value` set (else a placeholder); "Choose image" calls the picker (mock `useLfmPicker`); "Remove" calls `onChange('')`.

- [ ] **Step 6: Implement `MediaField.tsx`** — preview + "Choose image" (calls `useLfmPicker(onChange).open('Images')`) + "Remove" (`onChange('')`), monochrome classes only.

- [ ] **Step 7: Run tests — PASS; `npx tsc --noEmit` 0.**

- [ ] **Step 8: Commit** — `feat(posts): LFM media bridge (useLfmPicker + MediaField)`.

---

## Task 3: Fix `ValidatePostData` title/slug max

**Files:** Modify `app/Http/Requests/ValidatePostData.php`; Test `tests/Feature/Admin/PostValidationMaxTest.php`.

- [ ] **Step 1: Failing test** — POST a valid post payload with a 100-char title/slug as admin; assert `assertSessionHasNoErrors()` and the `PostTranslation` persists. (Mirror `PostCrudTest`'s setup: `withoutMiddleware(VerifyCsrfToken)`, seed `DatabaseSeeder`, admin `username='admin'`.)

- [ ] **Step 2: Run — FAIL** (`max:20` rejects the 100-char title → `assertSessionHasErrors`). Run `php artisan test --filter=PostValidationMaxTest`.

- [ ] **Step 3: Fix** — in `ValidatePostData::rules()`, change `'title' => ['string','required','max:20']` → `max:255` and `'slug' => ['required','string','max:20']` → `max:255`. Leave the unique-rule append and everything else unchanged.

- [ ] **Step 4: Run — PASS.** Also run `php artisan test --filter=PostCrudTest` → still green.

- [ ] **Step 5: Commit** — `fix(posts): raise title/slug max length 20->255`.

---

## Task 4: Posts List (+ Trashed) → Inertia

**Files:** Modify `CPanelPostController.php` (`index`, `trashedPosts`); Create `resources/js/pages/cpanel/posts/List.tsx` (+ test), `tests/Feature/CPanel/PostInertiaRenderTest.php`; Modify `Phase5AdminRenderTest.php`.

**Interfaces:**
- Produces: component `cpanel/posts/List` with props `{ posts_list: { data: Array<{id:number,title:string,slug:string,status:number,author:string|null,updated_at:string}>, current_page, last_page, total }, is_trash: boolean }`.
- Delete: row + bulk `router.delete(route bulk, { data: { posts: number[], posts_action: 'delete' } })`; trashed actions `router.post('/multiple', { posts, posts_action: 'restore'|'destroy' })`.

- [ ] **Step 1: Failing Feature test** — `PostInertiaRenderTest`: as admin, `GET /agentic-cms-laravel-admin/posts` asserts `->component('cpanel/posts/List')->has('posts_list.data')->where('is_trash', false)`; `GET /posts/trashed` asserts `where('is_trash', true)`. (setUp seeds DatabaseSeeder + `ensure_pages_exist=false`.)

- [ ] **Step 2: Run — FAIL** (still Blade).

- [ ] **Step 3: Flip `index`/`trashedPosts`**

```php
public function index()
{
    return $this->renderList($this->service->list($this->per_page), false);
}
public function trashedPosts()
{
    return $this->renderList($this->service->trashed($this->per_page), true);
}
private function renderList($paginator, bool $isTrash)
{
    $paginator->getCollection()->transform(fn ($p) => [
        'id' => $p->id, 'title' => $p->title, 'slug' => $p->slug,
        'status' => (int) $p->status, 'author' => optional($p->author)->username,
        'updated_at' => (string) $p->updated_at,
    ]);
    return \Inertia\Inertia::render('cpanel/posts/List', [
        'posts_list' => $paginator, 'is_trash' => $isTrash,
    ]);
}
```
(Presentation mapping only — `$this->service` call, no repo access; keep `LayeringTest` green. Confirm the row exposes `author`/`title`/`slug`/`status`/`updated_at`; adjust field access to the real shape if needed and note it.)

- [ ] **Step 4: Run — PASS.**

- [ ] **Step 5: Create `List.tsx`** — mirror `categories/List.tsx`: `AdminLayout` via `Page.layout`; tab links (Posts / Trashed) using `<Link prefetch>`; table (title, slug mono, status badge, author, updated); per-row Edit link (`/posts/${id}/${locale.current}`); select + bulk bar (`data-testid="bulk-delete-confirm"`); on the main tab, delete via `router.delete('/agentic-cms-laravel-admin/posts/multipleDelete', { data: { posts: ids, posts_action: 'delete' }})`; on the trashed tab, restore/destroy via `router.post('/agentic-cms-laravel-admin/posts/multiple', { data: { posts: ids, posts_action: 'restore'|'destroy' }})`. `New post` button → `/posts/new`. Empty-state row. i18n via `tr('cpanel/posts.*', fallback)`. Monochrome classes only.

- [ ] **Step 6: Write `List.test.tsx`** — rows render; bulk-delete payload shape asserted (`{posts:[id], posts_action:'delete'}`); trashed tab shows restore/destroy and posts to `/multiple`; `bulk-delete-confirm` testid present.

- [ ] **Step 7: Run vitest — PASS.**

- [ ] **Step 8: Convert `Phase5AdminRenderTest`** — remove `cpanel_posts_list` and `cpanel_trashed_posts_list` from the `theme-admin` loop (now covered by `PostInertiaRenderTest`). Run `--filter=Phase5AdminRenderTest` → PASS. `--filter=PostCrudTest` → PASS.

- [ ] **Step 9: Commit** — `feat(posts): list + trashed on Inertia`.

---

## Task 5: Posts Form (new/edit) → Inertia

**Files:** Modify `CPanelPostController.php` (`addPost`, `editPost`); Create `resources/js/pages/cpanel/posts/Form.tsx` (+ test); extend `PostInertiaRenderTest`; Modify `Phase5AdminRenderTest.php` (remove `cpanel_add_new_post` from create-forms loop).

**Interfaces:**
- Produces: component `cpanel/posts/Form` props `{ entity: PostEntity|null, users_list: Array<{id:number,username:string}>, categories_list: Array<{category_id:number,title:string}>, selected_category_ids: number[], tags_value: string, translation_links: Record<string,string> }`. `PostEntity = { id, title, slug, content, preview, author_id, meta_keywords, meta_description, canonical_url, meta_noindex, status, scheduled_at, thumbnail }`.
- Submit: new → `form.post('/agentic-cms-laravel-admin/posts/new')`; edit → `form.put('/agentic-cms-laravel-admin/posts/${entity.id}/update')`.

- [ ] **Step 1: Failing Feature tests (new + edit)** — extend `PostInertiaRenderTest`: `GET /posts/new` → `component('cpanel/posts/Form')`, `where('entity', null)`, `has('users_list')`, `has('categories_list')`; arrange a post, `GET /posts/{id}/en` → `has('entity')`, `where('entity.title', ...)`, `has('selected_category_ids')`, `has('tags_value')`, `has('translation_links')`.

- [ ] **Step 2: Run — FAIL.**

- [ ] **Step 3: Flip `addPost`/`editPost`** to `Inertia::render('cpanel/posts/Form', [...])`. Shape `entity` explicitly from `$this->service->getById($id)` (not the raw model); compute `selected_category_ids` = `$entity->categories->pluck('id')`, `tags_value` = `$entity->tags->pluck('name')->implode(', ')`; `users_list` = `get_authors_list()`; `categories_list` = `get_post_categories_list()`; `translation_links` = `get_entity_translation_links('posts', $id)` (edit) / `[]` (new, unless `lang` route param). Keep `editPost`'s fallback to `addPost` when `getById` is null. Leave `createPost`/`updatePost` mechanics unchanged (flash added in Task 7).

- [ ] **Step 4: Run — PASS.**

- [ ] **Step 5: Create `Form.tsx`** — `AdminLayout` via `Page.layout`; `useForm` seeded from `entity` (or blanks for new); testids `post-title`/`post-slug`/`post-submit`. Fields: `title`, `slug` (`TextField`); `content` + `preview` via `<RichText>` (each unique `id`, `onChange`→`form.setData`); `author_id` (select from `users_list`); `category` (multiselect from `categories_list`, seeded from `selected_category_ids`, posts an array); `tags` (text input seeded from `tags_value`); `thumbnail` via `<MediaField>`; `status` (select 0/1); `scheduled_at` + `created_at`/`updated_at` (native `datetime-local`); SEO (`meta_keywords`, `meta_description`, `canonical_url`, `meta_noindex`); locale switcher from `translation_links`. Inline errors for every field (like Categories' fix). Submit → post/put per the interface. Monochrome only.

- [ ] **Step 6: Write `Form.test.tsx`** — new renders testids + posts to `/new`; edit prefills title + PUTs to `/{id}/update`; category multiselect seeded from `selected_category_ids`; RichText + MediaField mocked at module boundary; `form.setData('category', ...)` posts an array.

- [ ] **Step 7: Run vitest + tsc — PASS/0.**

- [ ] **Step 8:** Remove `cpanel_add_new_post` from `Phase5AdminRenderTest` create-forms loop. `--filter=Phase5AdminRenderTest` PASS; `--filter=PostCrudTest` + `--filter=PostScheduleFormTest` PASS.

- [ ] **Step 9: Commit** — `feat(posts): form (new+edit) on Inertia with RichText, media, categories, tags, scheduling`.

---

## Task 6: Revisions (list / diff / restore) → Inertia

**Files:** Modify `CPanelPostController.php` (`revisions`, `revisionDiff`); Create `resources/js/pages/cpanel/posts/Revisions.tsx`, `RevisionDiff.tsx` (+ tests); extend `PostInertiaRenderTest`.

**Interfaces:**
- Produces: `cpanel/posts/Revisions` props `{ post_id:number, locale:string, revisions: Array<{id:number, created_at:string, author?:string}> }`; `cpanel/posts/RevisionDiff` props `{ post_id:number, locale:string, revision:{id:number,created_at:string}, diff: <shape from ManagesRevisions::revisionDiff> }`.

- [ ] **Step 1: Failing Feature test** — arrange a post with ≥1 revision (an update creates a snapshot via `PostTranslationObserver::updating`); `GET /posts/{id}/revisions/en` → `component('cpanel/posts/Revisions')->has('revisions')`; `GET /posts/{id}/revisions/{rev}/compare/en` → `component('cpanel/posts/RevisionDiff')->has('diff')`.

- [ ] **Step 2: Run — FAIL.**

- [ ] **Step 3: Flip `revisions`/`revisionDiff`** to `Inertia::render`, passing the same data the Blade views received (`$this->service->revisionsFor($id, $lang)` / `revisionDiff($id, $lang, $revision)`). Inspect the actual return shapes of `ManagesRevisions::revisionsFor`/`revisionDiff` and shape props to match; do not change the service. `restoreRevision` (POST) stays as-is (redirects back) — the Revisions page's Restore button uses `router.post(route restore)`.

- [ ] **Step 4: Run — PASS.**

- [ ] **Step 5: Create `Revisions.tsx`** (list of revisions with created_at/author + "Compare" link + "Restore" `router.post`) and `RevisionDiff.tsx` (renders the diff — old vs new per translated field; use the real `diff` shape). `AdminLayout` via `Page.layout`. Monochrome. i18n via `tr('cpanel/posts.*')`.

- [ ] **Step 6: Write tests** — `Revisions.test.tsx` (rows render, Restore posts to the restore route), `RevisionDiff.test.tsx` (renders old/new content for a sample diff).

- [ ] **Step 7: Run vitest + tsc — PASS/0.**

- [ ] **Step 8: Commit** — `feat(posts): revisions list/diff/restore on Inertia`.

---

## Task 7: Flash success + i18n keys + regression

**Files:** Modify `CPanelPostController.php` (flash), `resources/lang/{en,de,ru}/cpanel/posts.php`; Create `tests/Feature/CPanel/PostFlashTest.php`, `tests/Feature/CPanel/PostLocalizationTest.php`.

- [ ] **Step 1: Failing flash test** — as admin, delete a post (bulk endpoint), follow redirect, assert `AssertableInertia` `flash.success` is a non-empty string. Also create + update cases.

- [ ] **Step 2: Run — FAIL** (controllers flash `post_added`/`deleted`/`message`, not `success`).

- [ ] **Step 3: Add flash** — scoped to `CPanelPostController` (do NOT touch `CPanelBaseController`): `createPost` → add `->with('success', __('cpanel/posts.created'))`; `updatePost` → `return parent::update(...)->with('success', __('cpanel/posts.updated'));`; `multipleDelete` → `->with('success', __('cpanel/posts.deleted'))`; `restore` → `->with('success', __('cpanel/posts.restored'))`; `multipleActions` → success per branch. Keep existing legacy flash keys if any Blade still reads them.

- [ ] **Step 4: Run flash test — PASS.**

- [ ] **Step 5: i18n keys** — collect every `cpanel/posts.*` key referenced by `List.tsx`/`Form.tsx`/`Revisions.tsx`/`RevisionDiff.tsx` (grep the `tr('cpanel/posts...`), plus the `created/updated/deleted/restored` flash keys, and add them to `resources/lang/{en,de,ru}/cpanel/posts.php` with real de/ru translations (create the file if absent). Reuse existing `cpanel/posts.php` keys where present.

- [ ] **Step 6: Failing localization test** — `PostLocalizationTest`: visit the posts list with session locale `de` then `ru`, assert `messages` resolves a representative `cpanel/posts.*` key to a non-key, non-English value.

- [ ] **Step 7: Run — PASS.**

- [ ] **Step 8: Commit** — `feat(posts): success flash + de/ru localization`.

---

## Task 8: Regression + verification

- [ ] **Step 1:** `php artisan test --testsuite=Feature` + `--testsuite=Unit` (Arch OOMs locally — known; green in CI). Confirm `PostCrudTest`, `PostScheduleFormTest`, `PostInertiaRenderTest`, `PostFlashTest`, `PostLocalizationTest`, `LayeringTest` green.
- [ ] **Step 2:** `npx vitest run` (all), `npx tsc --noEmit` (0), `npm run build` (OK, no CDN refs), `./vendor/bin/pint --test` (clean — if the controller uses `\Inertia\Inertia::render`, add `use Inertia\Inertia;` to satisfy `fully_qualified_strict_types`).
- [ ] **Step 3:** Manual pass (en/de/ru): create a post with rich content + featured image + categories + tags + schedule; edit; delete/restore; revisions compare/restore; verify instant-nav and monochrome. Fix, commit, then hand to the final whole-branch review.

## Self-Review Notes
- Editor (Task 1) and LFM bridge (Task 2) are isolated and de-risked before the form (Task 5) consumes them; their exact self-hosted-v4 import paths and any peer-range issue are flagged for the implementer to resolve+report, not guessed.
- Row shapes (`posts_list.data`, `PostEntity`, `diff`) are produced in the controller (Tasks 4/5/6) and consumed in the matching page; field names match `ValidatePostData` verbatim.
- Delete: row+bulk → redirecting bulk endpoints (Inertia-friendly); `deleteAjax` untouched. Trashed uses `posts_action` restore/destroy.
- Observers/service/repo untouched; category/tags/content/preview travel as real POST fields.
