# agentic-cms-laravel — HANDOFF

> Living handoff for the canon-convergence effort. Read this + `REFACTOR_PLAN.md` +
> `../FEATURE_MATRIX.md` + `../DESIGN_SYSTEM.md` before continuing. Last updated 2026-06-26.
>
> **Latest:** **P10 testing/coverage/CI — implementation DONE (CI-measured parts pending a CI run).**
> Suite **322 green** (791 assertions), PHPStan(level 5)+Pint clean. **Pest 4 is now the canonical
> runner** (`./vendor/bin/pest`; PHPUnit bumped 11→12.5.30, Pest 4.7.4; `tests/Pest.php` hand-written,
> NOT `--init`, so phpunit.xml force-pins intact). Added **`arch()` layering presets**
> (`tests/Arch/LayeringTest.php`: controllers⊄ORM/DB, services⊄DB-facade/query-builder, repo
> allow-list with `App\Http\Controllers` deliberately EXCLUDED) — they caught a **real leak** the
> earlier review missed: `ResetPasswordController` injected `UserRepository` directly → extracted
> `App\Services\Auth\PasswordResetService` (double-hash-safe path preserved), controllers now fully
> repo-free. Added **per-layer test-status table** (`REFACTOR_PLAN.md`) + **direct gap-filling tests**
> (CPanelPost/PageService, PostViewService, front CategoryRepository, ChangePasswordRequest, security
> `WriteThemeFileTool` path-traversal). Authored **Pest 4 browser suite** (`pest-plugin-browser` 4.3.1,
> 9 scenarios w/ `data-testid`+a11y+no-smoke+mobile, `->skip` unless `BROWSER_TESTS=1`; **Dusk kept**
> until CI parity). Added **`.github/workflows/ci.yml`** (lint→analyse→test+coverage→build→browser-e2e
> on MySQL+Playwright). Final Opus whole-branch review: **READY TO MERGE, 0 Critical/0 Important.**
> **CI-GATED LEFTOVERS (cannot run in this sandbox — no PCOV, no MySQL, no browser):** (1) actual
> coverage % — `--coverage --min=80` gate is in CI; first run measures the real baseline and may be
> RED until more gap tests land (Phase 6); (2) the browser e2e must be proven green in the CI e2e job;
> (3) only THEN retire Dusk (`laravel/dusk`, `DuskTestCase`, old `tests/Browser/*Test.php` Dusk classes,
> `scripts/dusk.sh`, `make dusk`). NB: change-password DOES verify the current password
> (`UserRepository::setPlainPassword`/guard at `UserRepository:88`), just not as a form-request rule.
> Resume at **UI redesign** (Task 3) or close P10's CI-gated items by pushing + reading CI.
> Pre-P10 optional leftovers still open: tags in search (§4) + admin tag-list/CRUD; revision storage
> pruning + morph map; **front never filters plain drafts (status=0, no schedule) — publicly reachable
> by slug (pre-existing)**; MCP post tools don't expose scheduled_at; per-category RSS; MCP settings
> tool lacks `email_verification` + plugin toggles (optional parity).

## Where things stand

**Branch:** `refactor/canon-convergence` (off `master`). All work committed there.
**Runner:** **Pest 4** is canonical. NOTE: `./vendor/bin/pest` throws a spurious
`ShouldNotHappen: OutputInterface cannot be resolved` in SOME interactive shells (a Pest-binary
DI quirk, not a code problem) — **use `php artisan test` (same engine) when that happens**; CI and
fresh subagent shells run the binary fine.
**Suite:** **590 passed**, ~55s (in-memory SQLite; browser tests excluded/skipped). 0 risky. (554 → +~36 for the Service content type M1 build.)
(479 after Task 3 → +H1 draft-leak fix +16 MCP tools +tags-in-search = 554.)
**Static analysis:** `composer analyse` (PHPStan/Larastan level 5 + baseline) → **green**.
**Lint:** `composer lint` (Pint, Laravel preset) → clean on all touched files.

### Task 3 — UI redesign to DESIGN_SYSTEM.md (DONE — all 8 phases + final review fixes; SHIP)
Plan: `docs/superpowers/plans/2026-06-26-task3-ui-redesign.md` (public+admin dark toggle in scope).
Ledger: `.git/sdd/progress.md` "Task 3" section. Commits `088419a`..`3b381f6`.
- **P1 tokens DONE** — `resources/css/tokens.css` (`:root` light + `.dark` dark, exact §2 hex + §4
  radius/spacing + §6 motion vars); `tailwind.config.js` bridges utilities to vars + `darkMode:'class'`.
  Utility vocabulary: `bg-bg`/`bg-surface`/`bg-surface-2`, `text-fg`/`text-muted`/`text-subtle`,
  `text-primary`/`bg-primary`/`text-primary-contrast`, `border-border`/`border-strong`, `ring-ring`,
  `bg-success-bg`/`text-success` (+warning/error), `text-accent`. Radii `rounded-sm/md/lg/xl/full`=§4
  (legacy `brand`/`ink`/`paper` ramps kept for un-migrated views).
- **P2 fonts DONE** — self-hosted `@fontsource-variable/{newsreader,inter,geist-mono}` → `font-serif`
  (Newsreader Variable) / `font-sans` (Inter Variable, NOT Inter Tight) / `font-mono` (Geist Mono
  Variable); Google Fonts + admin FA MaxCDN removed from the shells. DEFERRED CDNs: `vendor/laravel-
  filemanager/*` (BS3+FA4) → P6; `cpanel/menus/*` jQuery-UI googleapis → P7. Geist Mono pushes latin
  fonts to ~133KB (>120KB §7) → subset in P8. Font `preload` deferred to P8 (Vite hashing).
- **P3 component library DONE** — 16 anonymous Blade components in `resources/views/components/`
  (icon, eyebrow, button, badge, field, alert, card, card.post, breadcrumb[+item], pagination, avatar,
  empty-state, dropdown[+item], modal, tabs[+tab], toast-region) + ~157 component tests (0 risky).
  **API contracts captured in scratchpad** `t3-phase3a/b/c-report.md` — read those before applying.
  Key: `<x-button variant size as href loading icon>`, `<x-field label name error help required>`
  (caller sets id/aria on the control), `<x-card.post :title :url :excerpt :category :date :author
  :image>`, `<x-tabs :tabs="['en'=>'English'] " default>` + `<x-slot:panel_en>`, `<x-modal name title
  size>` (open via `$dispatch('open-modal','name')`).
- **P4 public shell DONE** — header/footer/breadcrumb rebuilt to §5 (sticky scroll-shadow, skip-link,
  `<nav aria-label>`, locale switcher as `<x-dropdown>`, **public dark/light toggle** [localStorage key
  `cmstack-theme` SHARED with admin, no-FOUC inline `<head>` script], focus-trapped mobile drawer in
  `front.js`); all `<head>`/seo-meta/`@vite`/`@yield`/`@stack`/locale wiring preserved; `@hook('footer')`.
- **P5 public pages DONE** — post detail + category/tag archives + home/page/contact/search + user
  pages (profile/edit/change-password) + auth (login/social parity + the 4 Bootstrap-4 ports:
  register, passwords/email, passwords/reset, verify). All forms/Alpine islands/get_field/captcha/
  routes preserved; full Auth suite (43) green; no Bootstrap-4 left in `default/`+`auth/`.
- **P6 admin shell DONE** — sidebar/topbar §5, admin dark toggle (`cmstack-theme`), token-driven
  `.admin-*` classes in admin.css, flash→`x-alert`; admin.js runtime (collapse/modal/toast/jQuery-shims)
  + permission-gating + language switcher + logout preserved.
- **P7 admin section views DONE** — 30 views: lists (token tables + bulk bar + status tabs + badges +
  row-action dropdowns), content forms (`x-field` + custom-field builder + editor + parent picker
  hooks preserved), settings/media/dashboard/menus/revisions. **CORRECTION:** content forms still edit
  ONE locale at a time via the topbar language switcher (the existing pattern) — they do NOT yet use a
  per-locale `x-tabs` strip. `x-tabs`/`x-modal`/`x-toast-region` are built+tested library components
  but currently UNUSED in views (available for future wiring; e.g. per-locale tabs on content forms).
- **P8 perf+a11y DONE** — font `wght`-subset + Geist latin-only, responsive images (width/height/lazy/
  fetchpriority), a11y sweep (one-h1 incl archives-via-banner, skip-link, landmarks), token cleanup;
  `.lighthouserc.json` + Lighthouse CI job (`ci.yml`, §7 budget — **CI-measured, never asserted unrun**).
- **Final Opus design review: FIX-THEN-SHIP → fixes applied** (commit `3b381f6`): dark-mode token fixes
  in 6 admin views (ink-*/brand-* → semantic), `aria-controls`, `env('APP_URL')`→`config('app.url')`,
  button secondary hover delta. **Task 3 = SHIP.** Suite 479 green, Pint+PHPStan clean.
- **DEFERRED (noted, non-blocking):** (1) §5 keyboard-accessible sortable for the menu builder — still
  jQuery-UI from googleapis CDN; (2) `vendor/laravel-filemanager/*` BS3+FA4 CDNs (third-party published
  views — republish to remove); (3) admin `x-field` doesn't pass `:error`/`aria-describedby` (admin
  relies on flash banners — Medium a11y improvement, per-form wiring); (4) ~~JS-hook list buttons raw~~
  **RESOLVED/non-issue**: the `delete_*`/`destroy_*`/`restore_*` row actions are intentionally styled as
  inline text-actions via the scoped `.theme-admin .user_actions` CSS (admin.css ~L432, danger hover on
  delete variants) — NOT raw/unstyled; converting them to `<x-button>` would regress that pattern and the
  jQuery hooks in `public/admin/js/*.js` depend on the classes; (5) tracked
  `public/build/*` assets are stale (rebuilt on deploy / in CI). **Visual fidelity + Lighthouse ≥95 +
  the Pest browser computed-style suite are MEASURED IN CI** (served app + Chrome + MySQL), not here.

### Completeness audit (Opus critic, 2026-06-28) + fixes
Suite now **486 passed** (Pest 4, `php artisan test`). README rewritten to current state (`ea6466c`).
FIXED this pass:
- **H1 (security) FIXED** (`a8a90c6`): plain DRAFTS (status=0) were publicly reachable by direct slug —
  posts AND pages. Added `status=PUBLISHED` to the FRONT `applyFrontReadScope()` on `PostRepository`
  (`post_translations.status`) + a NEW override on `PageRepository` (`page_translations.status`);
  published+future-scheduled stays visible (status-aware scope). +7 regression tests; flipped the
  characterization test that documented the leak. (This closes the long-standing HANDOFF "front never
  filters plain drafts" item.)
- **M3 FIXED** (`4685aa6`): `env('APP_URL')`→`config('app.url')` in 6 admin Blade views (config:cache-safe).
- **L2 FIXED** (`ff18cec`): `public/build` untracked + added to `.gitignore` (Vite output, rebuilt on CI/deploy).
- HANDOFF P7 `x-tabs` claim corrected (above).

REMAINING audit findings (NEW roadmap — matrix-vs-reality, from `../FEATURE_MATRIX.md` canon):
- **H3 — MCP tool parity — DONE** (commits `caf86fc` Tags, `f90b791` Comments, `7a2871b` Media,
  `512a641` GEO+publish+revisions). Surface 28→**44 tools**: Tags (5, `manage_posts`), Comments
  moderation (4, `manage_comments`; fixed an approve/unApprove fatal-on-missing bug), Media list+metadata
  (2, read-only, path-allowlisted w/ traversal-rejection tests, `manage_general_settings`), GEO settings
  get/update (2), PublishPost (1), Post revisions list/restore (2) — all `manage_posts`/delegating to
  existing repos/services. Suite **551 green**, Pint+PHPStan clean. Still out of scope (noted): media
  upload/delete via MCP, `email_verification`/plugin-toggle MCP parity.
- **M1 — Service content type — DONE** (plan `docs/superpowers/plans/2026-06-28-service-content-type.md`,
  14 tasks, commits 6535f75..c468d02). User chose "build the content type". Delivered: `Service`/`ServiceTranslation`
  models+migrations, `manage_services` permission/policy/middleware, front+admin repositories & services,
  `ServiceTranslationObserver` (sanitise), form requests, `CPanelServiceController` admin CRUD +
  routes + nav + list/new/edit views + en/ru lang + `service.js`, public `ServiceController`
  `/services` grid + `/{locale?}/services/{slug}` detail + views, sitemap + `## Service pages` in
  llms.txt + `ItemList`/`Service` JSON-LD on index, 5 MCP tools (surface 44→49), `CPanelServicesSeeder`
  (3 samples). ~30 new tests, full suite **590 green**, lint+analyse clean. The `geo_settings`
  free-text business-services summary is intentionally LEFT as-is (distinct concern: GEO/business
  identity → homepage Organization JSON-LD), and is now documented as such in CLAUDE.md. Note:
  FEATURE_MATRIX is read-only canon — §1/§9 is now genuinely backed; no matrix edit made.
- **M2 — tags in public search — DONE** (`6f748c8`): `PageRepository::filterByTag()` + `case 'tag'`,
  `tag` added to `SearchRequest` filter, `<option value="tag">` + `/tag/{slug}` result links in
  search.blade.php, en/ru `filter_tag` keys, 3 tests (554 green). Services-in-search still blocked on M1.
- **L1 — CLAUDE.md — DONE** (819b3f0 + doc batch): lint line now Pint/`composer lint` (not StyleCI),
  Pest-browser CI e2e noted alongside Dusk, asset-pipeline note corrected (legacy `public/admin/js/*.js`
  still loaded in admin list views for delete/destroy AJAX). Dusk left in until the CI-gated retirement.
- Still-deferred (unchanged): jQuery-UI keyboard-sortable (menu builder); filemanager vendor BS3/FA4 CDN;
  admin `x-field` `:error`/`aria-describedby` wiring. (Dropped: "JS-hook list buttons as raw `<button>`"
  — they're intentionally inline-styled via `.theme-admin .user_actions`, not raw.)

### Architecture map (current)

Strict layering is now enforced and verified across the whole app:

```
Controller (HTTP boundary only)
   -> Service  (business logic; NO ORM — repositories only)
       -> Repository (all Eloquent / query builder / DB lives here)
           -> Model
Service -> Event -> Listener/Observer   (for side effects of writes)
```

- **Controllers** (`app/Http/Controllers/**`): every one is a pure boundary — validate (Form
  Request) → call a service → map result to a response. **No controller calls a repository**
  (`grep -r '$this->repository' app/Http/Controllers` = none). Base controllers
  (`BaseController`, `CPanel/CPanelBaseController`) hold an untyped `protected $service`
  assigned by each subclass.
- **Services** (`app/Services/**`): `BaseCrudService` (generic CRUD over a `BaseRepository`:
  `list/getById/resolveBySlug/create/update/delete/destroy/restore`), domain services under
  `App\Services\CPanel\*` and `App\Services\Front\*`, plus `App\Services\Auth\*`. **No service
  touches the ORM** — verified by grep + 3 adversarial skeptics.
- **Repositories** (`app/Repositories/**`): unchanged two-family design (front + `CPanel*`)
  over `BaseRepository`. New methods added during the refactor: `UserRepository`
  (`findOrLinkSocialIdentity`, `createFromSocial`, `createFromRegistration`,
  `setPlainPassword`), `CPanel{Seo,Geo}SettingsRepository::saveSingleton`,
  `CPanel{Post,User,Comment}Repository` dashboard reads, `{Page,Post,Category}Repository::
  sitemapEntries`, `CategoryRepository::llmsEntries`.
- **Tooling:** `pint.json`, `phpstan.neon` (+ `phpstan-baseline.neon`, 86 frozen legacy
  findings), composer scripts `lint`/`lint:fix`/`analyse`/`test:coverage`/`check`.

## DONE (this effort)

1. **Auth service extraction** (`App\Services\Auth\SocialAuthService`,
   `UserRegistrationService`) + characterization/regression tests. Fixed a latent
   **double-hash bug** (register *and* password-reset double-hashed → login broke); now a
   single hashing path via the model mutator, with `tests/Feature/Auth/*` regression tests.
2. **Quality tooling**: Pint + Larastan(level 5 + baseline) + composer scripts.
3. **Architecture refactor (Task 2) — COMPLETE**: introduced the service layer; refactored
   **all ~22 controllers** (admin + front) to thin boundaries; moved **all** data access into
   repositories so no service touches the ORM. Event sync/async policy recorded in
   `REFACTOR_PLAN.md §1c`. Adversarially verified (layering / behavior / security+perf) —
   clean; the one finding (reset-password model mutation) was fixed.

## PENDING (ordered — resume here)

> Each item: TDD (characterization first) → suite green (show output) → 2–3 adversarial
> skeptics → fix → commit → refresh this file. Keep services repo-only and side effects in
> events/observers (with sync/async classification in `REFACTOR_PLAN.md`).

> DONE since last handoff: **Plugin/hook registry** (P9) — item 9 below; +27 tests
> (suite 263 → 290), brainstorm→spec→plan→TDD, adversarially verified (HIGH null-content 500 +
> MEDIUM/LOW region issues + LOW arbitrary-slug found and fixed). Earlier: Membership +
> email-verification (P8), RSS/Atom feeds (P7), Scheduled publishing (P6), Category tree admin UI
> (§2), Soft-delete for pages (§1), Revisions + restore UI (§1), comment-notification (§18/§3), Tags.

1. **Tags taxonomy** (P1) — **DONE end-to-end** (schema `tags`/`tag_translations`/`post_tag`;
   `Tag`/`TagTranslation`; `Post::tags()`; `TagRepository` find-or-create+sync + `postsForTag`;
   `PostObserver::syncTags` reads the `tags` form field; admin post-form `tags` input
   (new+edit, edit pre-fills); `Front\TagViewService` + thin `TagController` + `/tag/{slug}`
   archive + view; tags-as-pills on public post detail; language switcher wired; en/ru lang
   keys). **Optional leftovers:** include tags in search (§4); a dedicated admin tag-list/CRUD.
   NB: 2 of the frozen PHPStan baseline entries are tag `relationExistence` larastan
   false-positives (identical to the category ones) — leave.
3. **Revisions + restore UI** (P2) — **DONE end-to-end** (polymorphic `revisions` table;
   `Revision` model; `RevisionRepository` snapshot/listFor/findFor/diff/restoreFrom +
   allow-list restore; `PostTranslationObserver`/`PageTranslationObserver` `updating` hook
   delegating the snapshot; `ManagesRevisions` trait on the post/page services; controller
   `revisions`/`revisionDiff`/`restoreRevision` + routes; shared `cpanel/revisions/{list,diff}`
   views + en/ru lang; transactional writes, trash/scope/authz guards; 15 tests). Adversarially
   verified. **Optional leftovers:** prune/cap `revisions.data` growth; register a morph map.
4. **Soft-delete for pages** (P3) — **DONE end-to-end** (`pages.deleted_at` migration + SoftDeletes
   on `Page`; `CPanelPageRepository` trashedPages/delete/restore/destroy; `CPanelPageService`
   trashed/runBulkAction; controller trashedPages/restore/multipleActions + routes — note the
   GET `/{id}/restore` route is registered BEFORE `/{id}/{lang}` to avoid shadowing; pages_list
   trash-tab + bulk UI + page.js destroy; en/ru lang; 11 tests). Permanent-destroy is restricted
   to already-trashed rows (`onlyTrashed`) in BOTH posts and pages. Adversarially verified.
5. **Category tree admin UI** (P4) — **DONE** (made the inert parent picker work:
   `CPanelCategoryRepository::parentOptions`/`descendantIds` build the current-locale tree and
   exclude self+descendants; form field renamed `parent_category` → `parent_category_id` so
   Astrotomic persists it to the translated column; indented dropdown + selected state;
   `CategoryRequest` cycle guard via `Rule::notIn(self+descendants)` with int normalisation;
   MCP `UpdateCategoryTool` cycle guard; 6 tests). Adversarially verified. Optional leftover:
   show the tree/parent in the category LIST view (parent picker itself satisfies the matrix).
6. **Scheduled publishing** (P6) — **DONE** (`post_translations.scheduled_at` nullable+indexed;
   `CPanelPostRepository::publishDue` + `CPanelPostService::publishDue` + `posts:publish-due`
   command scheduled `everyMinute()->withoutOverlapping()` in `App\Console\Kernel`. Front hides
   future-scheduled *drafts* via `Post::scopeNotScheduledForFuture` (status-aware) applied to ALL
   public read paths: detail (via `BaseRepository::applyFrontReadScope` hook, overridden in front
   `PostRepository`), sitemap, category/tag archives, search, home helper. Admin datetime-local
   schedule field + `ValidatePostData` `scheduled_at`; en/ru lang. 9 tests). Adversarially verified.
7. **RSS/Atom feeds** (P7) — **DONE end-to-end** (`/rss.xml` RSS 2.0 + `/atom.xml` Atom 1.0 of the
   20 most recent PUBLISHED posts in the default locale, newest first; `App\Services\Front\FeedService`
   builds the XML, `PostRepository::feedEntries` is the single bounded query [`status=1` +
   `notScheduledForFuture()` + locale + `limit`], `SeoController::rss/atom` wrap it in `Cache::remember`
   1h + correct `Content-Type` [`application/rss+xml` / `application/atom+xml`]; routes registered
   before the front catch-all; feed autodiscovery `<link rel="alternate">`s added to
   `seo-meta.blade.php`; 7 tests in `tests/Feature/Front/FeedTest.php`). Drafts + future-scheduled
   posts never leak (proven by tests). Adversarially verified — skeptics found `esc()` used
   `ENT_XML1` (leaves `"` raw → Atom `href="..."` attribute breakout) and let C0 control chars
   through (broke XML well-formedness); FIXED in `FeedService::esc()` (strip
   `/[\x00-\x08\x0B\x0C\x0E-\x1F]/` then `ENT_QUOTES|ENT_XML1`) + a hostile-content regression test.
   **Optional leftover:** per-category RSS feed.
8. **Membership toggle + email-verification enforcement** (P8) — **DONE end-to-end**. Membership:
   `EnsureRegistrationEnabled` middleware (alias `registration_enabled`) on `RegisterController`
   blocks the register form+POST when `membership` is off (redirect to login + flash); the social
   callback gates NEW-account creation by the same toggle (existing-account login still works);
   header hides the register link. Email verification (optional + enforced): new
   `general_settings.email_verification` column (migration + fillable + boolean casts + admin form
   + `ValidateGeneralSettings` normalize/rule + seeder default 0); `User implements MustVerifyEmail`;
   `Auth::routes(['verify'=>true])`; `SendVerificationNotificationIfEnabled` listener sends the
   verification email only when on, and `EventServiceProvider::configureEmailVerification()` is
   overridden empty so Laravel's unconditional listener never fires; `EnsureEmailIsVerifiedWhenRequired`
   middleware (alias `verified_if_required`) on member routes (likes/comments/profile) enforces only
   when on (409 for JSON, redirect to `verification.notice` otherwise); social accounts created
   pre-verified; seeded admin/founder pre-verified. en/ru `default/auth` + `cpanel/settings` lang.
   Adversarially verified (3 skeptics): correctness + architecture cannot refute (phpstan-baseline
   SHRANK — `LoginController` return types fixed, not baselined). **Security found a PRE-EXISTING
   social-login account-takeover** (`findOrLinkSocialIdentity` linked by email without provider
   verification → takeover incl. admin, bypassing both toggles). **FIXED** (user chose "harden"):
   repo split into `findBySocialIdentity`/`findByEmail`/`linkSocialIdentity`; `SocialAuthService::findOrLink`
   links onto an email-matched account ONLY when the provider asserts the email is verified
   (`email_verified`/`verified_email` in raw payload; absent ⇒ unverified = secure default), else
   throws `SocialEmailNotVerifiedException` → controller redirects to login with a flash. 23 tests
   (Membership/EmailVerification/SocialMembershipAndVerification + updated SocialAuthService/SocialLoginLinking).
   **Optional leftover:** expose `email_verification` in MCP `UpdateGeneralSettingsTool` for parity.
9. **Plugin/hook registry** (P9) — **DONE end-to-end** (Laravel-events engine `App\Support\Hooks`:
   `action`/`onAction`, `filter`/`onFilter` [mutable `HookValue` container so filters return values on
   the event bus], `region`/`onRegion` [concatenated HTML, array-fragment-safe], all over the event
   dispatcher with `hook.{action,filter,region}.<name>` keys; `@hook('region')` Blade directive +
   `hooks` singleton. In-repo plugins implement `App\Plugins\Contracts\PluginInterface`
   [slug/name/description/boot(Hooks)]; `App\Support\PluginManager` discovers `app/Plugins/*/*Plugin.php`,
   `sync()`s slugs into the `plugins` table [new ⇒ disabled], `loadEnabled()` boots enabled-only;
   `PluginServiceProvider` primes the shared Hooks LAZILY on first resolution [per request/app
   instance ⇒ toggle without restart; `Schema::hasTable` guard]. Injection: `the_content` filter at
   `posts/post.blade.php` + `@hook('head'/'header'/'footer')`. Sample `ReadingTimePlugin` filters
   `the_content`. Admin manager: `CPanelPluginController`→`CPanelPluginService`→`CPanelPluginRepository`,
   list+toggle under `manage_general_settings`, sidebar link, en/ru lang. 27 tests
   [`tests/Unit/HooksTest`, `tests/Feature/HookDirectiveTest`, `tests/Feature/Plugins/*`]).
   Adversarially verified — fixed HIGH null-content 500 (null-safe filter), MEDIUM region array-fatal +
   LOW falsy-drop (robust `region()`), LOW arbitrary-slug junk rows (toggle validates against
   `discover()` → 404). **Optional leftovers:** filter priorities, plugin zip-upload, Octane re-prime
   reset, expose plugin toggles via MCP (all YAGNI/out-of-scope).
10. **Testing mandate + coverage + CI** (P10) — **IMPLEMENTATION DONE; CI-measured items pending.**
    Plan: `docs/superpowers/plans/2026-06-26-p10-testing-coverage-ci.md`. Commits `aff5a13`..`2208bde`.
    - **Pest 4 = canonical runner** — DONE (`./vendor/bin/pest`; PHPUnit 11→12.5.30, Pest 4.7.4;
      `tests/Pest.php` hand-written; phpunit.xml force-pins intact; composer `test`/`test:coverage`/
      `check` repointed to pest). 290→322 tests green.
    - **Pest `arch()` presets** — DONE (`tests/Arch/LayeringTest.php`; controllers⊄ORM/DB, services⊄
      DB-facade/query-builder, repo `toOnlyBeUsedIn` allow-list with `App\Http\Controllers` EXCLUDED).
      Caught + fixed a real controller→repository leak (`ResetPasswordController` → new
      `App\Services\Auth\PasswordResetService`; double-hash-safe reset path preserved).
    - **Dusk → Pest 4 browser** — AUTHORED (`pest-plugin-browser` 4.3.1; `tests/Browser/{Homepage,
      AuthAdmin,GeoSettings}Test.php`; `data-testid` + a11y + no-smoke + mobile; `->skip` unless
      `BROWSER_TESTS=1`; excluded from default testsuites). **Dusk NOT yet removed** — retire only
      after the CI e2e job proves parity.
    - **Per-layer test-status table** — DONE in `REFACTOR_PLAN.md`; no layer at zero (direct tests
      added for previously transitive-only services/repos + security `WriteThemeFileTool`).
    - **Coverage ≥80% services/repos + 100% critical paths** — **CI-GATED.** No PCOV/Xdebug here so
      `--coverage` can't run locally; the `--min=80` gate lives in `ci.yml`. First CI run measures the
      real baseline and may be RED until more gap tests land — close it then (never assert an unrun %).
    - **CI pipeline** — DONE (`.github/workflows/ci.yml`: quality[lint→analyse→pest --coverage --min=80]
      + build[vite] + e2e[MySQL 8 + Playwright Chromium, `pest tests/Browser` w/ `BROWSER_TESTS=1`]).
      Final Opus whole-branch review: READY TO MERGE, 0 Critical/0 Important.
    - **REMAINING (needs a real env, not this sandbox):** push → read CI coverage → add gap tests to
      reach ≥80%/100%-critical (Phase 6); prove the browser e2e green; then retire Dusk in one commit.
11. **UI redesign to `../DESIGN_SYSTEM.md`** (Task 3, biggest): tokens → self-hosted fonts
    (Newsreader/Inter/Geist Mono) → Blade components → perf budget → a11y. **Lighthouse ≥95
    mobile must be MEASURED** with a real run (needs served app + headless Chrome against
    MySQL — not available in this sandbox; flag the env need, never assert the score).
12. **README rewrite** (Task 5) + **completeness-critic** pass.

## Key decisions / rejected options

- Service-per-domain extending `BaseCrudService`, injecting the **concrete repository** as a
  private prop (`private CPanelPostRepository $repo`) and passing it to `parent::__construct`
  so generic CRUD is type-safe while domain methods call repo-specific methods. Rejected:
  adding repo methods to the interface (forces all repos to implement); a second source of
  truth.
- `protected $service` left **untyped** on base controllers (matches the legacy untyped
  `$repository`) because PHP forbids covariant narrowing of an inherited typed property and
  subclasses call domain-specific methods on it. PHPStan stays green (mixed allows the calls);
  the architecture rule is about layering, not type hints.
- No Strategy/Factory/Adapter introduced — would be over-engineering here (scope guardrail).
- `ContactService::send()` sends mail directly (exempt from the events rule): the contact
  form's mail IS the primary action, not a side effect of a DB write.

## How to run

```bash
# in agentic-cms-laravel/
php artisan test                       # full suite TODAY (PHPUnit; in-memory SQLite; never MySQL)
# P10 TARGET runner (after migration): ./vendor/bin/pest --coverage  + Pest browser suite
composer lint                          # pint --test
composer analyse                       # phpstan level 5 (+ baseline)
composer check                         # lint + analyse + test
# App (needs MySQL 8 + ext-imagick): make setup ; admin at /cmstack-laravel-admin
```
> NOTE: the updated operating prompt (`../prompts/cmstack-laravel.md`) makes **Pest 4** the
> canonical runner and **Pest browser** the E2E layer (replacing Dusk). The suite still runs under
> PHPUnit via `php artisan test` until the P10 migration lands.

## Gotchas

- **Models live in `app/Http/Models/`** (not `app/Models/`); admin models under `CPanel/`.
- **Test isolation** is pinned in `tests/CreatesApplication.php` (forces SQLite `:memory:`);
  don't weaken it — Docker injects `DB_CONNECTION=mysql` and would wipe the dev DB.
- **Password hashing**: `User::setPasswordAttribute` hashes on assign. NEVER `Hash::make()`
  before assigning — that double-hashes (the bug fixed this effort). Pass plaintext.
- **PHPStan baseline**: if a refactor removes a previously-frozen error you'll get
  `ignore.unmatched`; regenerate with `phpstan analyse --generate-baseline=phpstan-baseline.neon`.
  Never baseline a NEW error in your own code — fix it.
- **Pint** auto-removes unused imports and FQCN-collapses docblock types; re-run it after
  edits and let it re-add a `use` for a `@param`/`@return` class.
- No Xdebug/PCOV here → coverage numbers require CI or a local PCOV install.
- **Admin route ordering**: the content groups have a greedy `GET /{id}/{lang}` editor route
  whose `{lang}` is unconstrained. Any other 2-segment `GET /{id}/<literal>` route (e.g.
  `/{id}/restore`) MUST be registered BEFORE it, or it is shadowed (matched as edit with
  `lang="<literal>"`). Posts/pages restore routes are placed accordingly.

---

## READY-TO-PASTE CONTINUATION PROMPT (new window)

```
You are a senior Laravel/PHP engineer continuing the cmstack-laravel canon-convergence
work AUTONOMOUSLY in /Users/huseyn0w/Desktop/SWE/cmstack/cmstack-laravel (git branch
refactor/canon-convergence).

AUTHORITATIVE OPERATING CONTRACT: ../prompts/cmstack-laravel.md (updated 2026-06-26) — read it
FIRST; if it conflicts with anything below, IT wins. Then read, in order:
cmstack-laravel/HANDOFF.md, cmstack-laravel/REFACTOR_PLAN.md, ../FEATURE_MATRIX.md,
../DESIGN_SYSTEM.md (the last two are read-only canon — do NOT edit; if either is missing, stop
and tell me in Russian "Нет общих спеков — сначала запусти prompts/00-bootstrap.md"). Then resume
from the FIRST item in HANDOFF.md "PENDING".

Operating rules (summary — the prompt above is authoritative):
- ORCHESTRATOR MODEL: you (lead, Opus) do only thinking — decomposition, architecture, the plan,
  integration, review. DELEGATE the doing (reading big files, writing code/tests, running suites)
  to subagents; protect your context. Model routing: Haiku = cheap lookups; Sonnet = low-risk
  implementation/tests; Opus = architecture-sensitive/critical work + all review.
- TESTING: Pest 4 is the canonical runner (`./vendor/bin/pest`), Pest browser replaces Dusk, add
  `arch()` layering presets, per-layer test status in REFACTOR_PLAN.md (see PENDING P10).
- Work autonomously inside cmstack-laravel/; don't ask permission for reads/edits/artisan/
  composer/npm/tests/local git. Only stop for genuinely irreversible actions or a product
  decision the spec files don't answer (batch such questions).
- HARD RULES (top priority, non-negotiable): controllers in app/Http/Controllers contain
  ZERO business logic and ZERO data access — pure HTTP boundary (validate via Form Request →
  call a SERVICE → map result to response). Services access data ONLY through repositories
  (no Eloquent/query-builder/DB/raw SQL in services). Side effects of writes go through
  domain events → listeners/observers, each classified synchronous (in-transaction) or
  asynchronous (queued) and recorded in REFACTOR_PLAN.md. Chain: controller → service →
  repository → model.
- Use Superpowers skills in order: brainstorming (only if scope unclear), writing-plans,
  test-driven-development, subagent-driven-development, requesting-code-review,
  verification-before-completion. Follow rigid skills exactly.
- Max-quality subagent routing; for every refactor/feature dispatch 2–3 INDEPENDENT
  adversarial Opus skeptics (behavior / correctness / security / performance) that try to
  REFUTE; treat a finding resolved only when a majority cannot break it.
- Keep the suite green and SHOW real output; never claim passing without the run. Targets:
  ≥80% coverage on services/repos + 100% of critical paths (needs PCOV/CI — no coverage
  driver in the sandbox, flag it). Lint (Pint) + static analysis (Larastan level 5) stay
  clean; new code adds no baseline entries.
- Respond to me in RUSSIAN; all code/comments/identifiers/commit messages/.md docs in
  English. Commit each verified slice with a plain message — **NO `Co-Authored-By` / Claude
  attribution trailer**. When context drops below ~50%, refresh HANDOFF.md (incl. this
  continuation prompt) and tell me in Russian to open a new window.

**STATE (resume here):** branch pushed to origin (`06a778f`), suite **554 green** (`php artisan test`
— NOT `./vendor/bin/pest`, which throws a spurious OutputInterface error in some shells). DONE this
engagement: architecture refactor, P1–P9 features, **P10** (Pest 4 / arch / CI — READY TO MERGE),
**Task 3** (full UI redesign to ../DESIGN_SYSTEM.md, 8 phases + review fixes, SHIP), README rewrite,
and a completeness-audit pass that FIXED: H1 (draft-content slug leak, security), M2 (tags in search),
M3 (env→config in admin views), L2 (untrack public/build), and **H3 — MCP tool parity (28→44 tools:
Tags/Comments/Media/GEO/Publish/Revisions)**. See the "Completeness audit" subsection above for commits.

**REMAINING — pick up here:**
1. **CI-gated (needs the pushed branch's CI run):** read the first CI coverage report → add gap tests
   to ≥80% services/repos + 100% critical paths; prove the Pest browser e2e + Lighthouse jobs green;
   THEN retire `laravel/dusk` in one commit. (No PCOV/MySQL/Chrome locally — measured in CI only.)
2. **M1 — Service content type — DONE** (commits 6535f75..c468d02, plan
   `docs/superpowers/plans/2026-06-28-service-content-type.md`). Full first-class translatable Service
   content type built end-to-end (models→repo→service→admin CRUD→front pages→sitemap/llms/JSON-LD→5 MCP
   tools→seeder), suite 590 green. See the "Completeness audit" M1 entry above for the full breakdown.
3. **Deferred polish (bounded, no decision needed):** self-host the menu-builder jQuery-UI + add a
   keyboard-accessible sortable; wire admin `x-field` `:error`/`aria-describedby` (~17 forms — needs a
   slot aria-describedby contract, not just the component); re-style/replace the
   `vendor/laravel-filemanager` BS3/FA4 views. (The `delete_*` list-button item is dropped — see DEFERRED
   note (4): they're intentionally inline-styled via `.theme-admin .user_actions`, not raw.)
4. **L1 — CLAUDE.md — DONE (819b3f0 + this batch):** corrected the lint line (Pint/`composer lint`
   canonical, not StyleCI), added the Pest-browser CI e2e note alongside Dusk, and fixed the asset
   note (legacy `public/admin/js/*.js` are still loaded in admin list views for delete/destroy AJAX).
```
