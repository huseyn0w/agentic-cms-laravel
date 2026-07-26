# Phase 3 (cont.) — Posts cpanel resource on Inertia — Design

**Date:** 2026-07-27
**Branch:** `feat/inertia-migration` (continues after the Categories slice, HEAD `771bfb8`)
**Depends on:** Phase 3 Categories slice (AdminLayout shell, monochrome tokens, i18n, the vertical-slice recipe)

## Goal

Migrate the admin **Posts** resource from Blade to Inertia + React, following the Categories slice pattern, and stand up the two capabilities Categories didn't need: a React rich-text editor (TinyMCE) and a media/featured-image picker bridged to laravel-filemanager (LFM). Also migrate the shared **revisions** screens (list / diff / restore) and fix a validation bug (`title`/`slug` `max:20`).

## Decisions (from brainstorm 2026-07-27)
- **Rich editor:** `@tinymce/tinymce-react` (official React wrapper), pinned to a version compatible with the self-hosted **TinyMCE v4** already in `package.json` (`tinymce ^4.9.11`). No CDN — the wrapper must use the locally bundled `tinymce` v4 (import `tinymce/tinymce` + theme/plugins/skins, pass `tinymceScriptSrc`-less / bundled mode). This is the main integration risk → de-risked by a standalone editor task first.
- **Revisions:** ported in this slice (list / diff / restore on Inertia), not left as Blade.
- **`max:20` fix:** bump `ValidatePostData` `title`/`slug` to `max:255` with a covering test.

## Scope

In scope:
1. Posts **List** (+ Trashed tab) → Inertia. Bulk delete / bulk restore+destroy, per-row soft-delete / restore / destroy via `router.*`.
2. Posts **Form** (shared new/edit) → Inertia, with every current field: `title`, `slug`, `content` + `preview` (rich text), `author_id`, `category[]` (multiselect), `tags` (comma-separated input), `thumbnail` (LFM picker), `status`, `scheduled_at`, `created_at`/`updated_at`, SEO (`meta_keywords`, `meta_description`, `canonical_url`, `meta_noindex`), locale switcher.
3. **RichText** React component wrapping TinyMCE v4 (self-hosted) with the existing plugin/toolbar config, bound to `content` and `preview`; image insert routed through LFM.
4. **Media bridge** (`useLfmPicker` / `<MediaField>`): opens `/filemanager?type=Images` in a popup and receives the selected path via the LFM `SetUrl` callback; fills `thumbnail`.
5. **Revisions** list / diff / restore → Inertia.
6. Backend: controllers `view()` → `Inertia::render()` (routes, names, method names, and the `restore`-before-`{id}/{lang}` ordering preserved); add a `flash.success` on create/update/delete (like Categories); fix `ValidatePostData` `max`.
7. Tests: AssertableInertia for list/trashed/new/edit/revisions; convert `Phase5AdminRenderTest` posts assertions; keep `PostCrudTest`/`PostScheduleFormTest` green; Vitest for RichText/MediaField/List/Form/Revisions.

Out of scope:
- Pages/Services rich-editor migration (they reuse RichText later; separate slices). Pages' custom-field builder (`custom_text_modal` etc.) is NOT a Posts concern.
- Replacing LFM itself with a React media library (later phase); this slice keeps the LFM iframe/popup and bridges to it.
- Deleting legacy Blade post views / `public/admin/js/{post,thumbnail}.js` (Phase 5 cleanup); they stay until the slice is verified.

## Architecture

### Reused from the Categories slice
`AdminLayout` (persistent shell via `Page.layout`), monochrome tokens + `.admin-*` classes, `useForm`/`router` idioms, `tr(key, fallback)` i18n with keys added to `resources/lang/**`, `flash.success` surfaced by `HandleInertiaRequests`, `AssertableInertia` test pattern, `data-testid` conventions.

### New component: `resources/js/components/RichText.tsx`
A controlled wrapper over `@tinymce/tinymce-react`'s `<Editor>` using the **locally bundled** TinyMCE v4 (no CDN). Props: `{ name, value, onChange(html), height? }`. Config ports the legacy `agentic-cms-laravel.js` init: the same `plugins` and `toolbar`, `relative_urls:false`, and image handling via TinyMCE v4's `file_browser_callback` pointing at `/filemanager?field_name=...&type=Images|Files` opened in TinyMCE's `windowManager` (unchanged mechanism — LFM already round-trips through it). `onChange` (editor `change`/`keyup`/`undo`/`redo` → `getContent()`) feeds Inertia `useForm.setData`. The `content` and `preview` fields each mount one instance (fixing the legacy duplicate `id="editor"` — each gets a unique id).

**Risk / de-risk:** getting `@tinymce/tinymce-react` to drive self-hosted TinyMCE v4 is the crux. Task order isolates this: a standalone `RichText` task that renders, edits, and reports value changes (Vitest with TinyMCE mocked at the module boundary), plus a manual smoke that the editor renders with local assets and no CDN request. Only after that does the form consume it.

### New bridge: `resources/js/lib/lfm.ts` (`useLfmPicker`) + `<MediaField>`
LFM's picker page calls `window.SetUrl(url, ...)` on its opener when a file is chosen. The bridge:
- opens `window.open('/filemanager?type=Images&field_name=<name>', 'lfm', 'width=..,height=..')`,
- sets `window.SetUrl = (url) => { setValue(url); popup?.close(); }` for the duration,
- cleans up on unmount.
`<MediaField>` renders the current thumbnail preview + "Choose image" / "Remove" controls, writing the URL into `useForm`'s `thumbnail`. No jQuery, no `lfm.js`.

### Form fields not in Categories
- **Category multiselect** (`category[]`): a controlled multi-checkbox or multi-select list from `categories_list` (depth-aware). Must post `category` as an array of ids (observer reads `app('request')->category`).
- **Tags** (`tags`): a comma-separated text input (value = `tag1, tag2`); posted verbatim; `PostObserver::syncTags()` parses it. No tag-widget needed to match current behavior.
- **Author** (`author_id`): select from `users_list` (`id`,`username`).
- **Status** (`status`): select `0` (private) / `1` (published).
- **Scheduling** (`scheduled_at`): native `<input type="datetime-local">` (drops the jQuery datepicker). `created_at`/`updated_at`: native `datetime-local` too.

### Backend changes (controllers only; observers/service/repo untouched)
- `index`, `trashedPosts` → `Inertia::render('cpanel/posts/List', ['posts_list' => ..., 'is_trash' => bool])` (presentation-map rows: `id, title, slug, status, author, created_at, updated_at` — resolve author name in the controller via the eager-loaded relation, like Categories' parent-name map, staying Controller→Service).
- `addPost`, `editPost` → `Inertia::render('cpanel/posts/Form', ['entity' => null|shaped, 'users_list', 'categories_list', 'selected_category_ids', 'tags_value', 'translation_links'])`. `editPost` keeps the `addPost` fallback when `getById` is null.
- `createPost` / `updatePost` / `multipleDelete` / `multipleActions` / `restore` / `deleteAjax` / `destroyAjax`: unchanged mechanics; add `->with('success', __(...))` where a user action completes (create/update/delete/restore), scoped to this controller (do NOT change `CPanelBaseController`).
- `revisions`, `revisionDiff`, `restoreRevision` → `Inertia::render('cpanel/posts/Revisions'|'RevisionDiff', [...])`.
- **Route ordering preserved:** `/{id}/restore` stays before `/{id}/{lang}`. Delete flow: row + bulk go through the existing endpoints (`deleteAjax` echoes plain text today — for Inertia, row-delete uses `cpanel_posts_bulk_delete` with a single id like Categories, OR a redirecting endpoint; the bulk endpoint already `back()`s → Inertia-friendly; `deleteAjax` stays untouched, dead for posts → Phase 5).
- **Validation fix:** `ValidatePostData` `title`/`slug` `max:20` → `max:255`.

## Data flow
Same as Categories: request → `auth`+`see_admin_panel`+`manage_posts` → thin controller → Service → Repository/observers → `Inertia::render`; observers read `content`/`preview`/`category`/`tags` off `app('request')`, so the React form posts them as real fields via `useForm` (multipart not required — thumbnail is a URL string from LFM, not a file upload). Mutations via `router`/`form.post|put`; `back()` + `flash.success`.

## Testing strategy
- **Backend (AssertableInertia):** `PostInertiaRenderTest` — list (`cpanel/posts/List` + `posts_list.data`), trashed (`is_trash` true), new (`entity` null + `users_list`/`categories_list`), edit (`entity` shaped, selected categories, tags value, translation_links), revisions list/diff. Locale coverage en/de/ru for labels. Convert `Phase5AdminRenderTest` posts routes to component assertions. Keep `PostCrudTest` + `PostScheduleFormTest` green (transport-agnostic).
- **Backend (unit):** validation-fix test (`title` 21–255 chars now passes; empty still fails).
- **Frontend (Vitest):** `RichText` (renders, emits onChange — TinyMCE mocked at module boundary), `MediaField`/`useLfmPicker` (opens popup, `SetUrl` sets value, cleanup), `List` (rows, trashed tab, bulk/row delete payloads, testids), `Form` (all fields, submit targets, category multiselect, tags), `Revisions` (list + diff render).
- **Flash + i18n regression:** like Categories — a delete produces `flash.success`; de/ru keys resolve.

## Follow-on (not this slice)
Pages + Services (reuse `RichText` + `MediaField`; Pages adds the custom-field builder). Comments, Users, Roles, Menus, Media (React LFM replacement), Settings. Phase 4 (public + SSR), Phase 5 (delete legacy Blade + `post.js`/`thumbnail.js`/jQuery once no admin screen needs them).
