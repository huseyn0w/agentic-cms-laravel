# agentic-cms-laravel — Refactor & Parity Plan

> Master plan coordinating the architecture refactor, feature-parity work, unified UI,
> tooling, and tests required to bring `agentic-cms-laravel` to the agentic-cms canon
> (`../FEATURE_MATRIX.md`, `../DESIGN_SYSTEM.md`). Detailed, executable per-slice plans
> live under `docs/superpowers/plans/`.

**Baseline (verified 2026-06-24):** PHP 8.3.10, Laravel 11, `vendor/` + `node_modules/`
present. Full suite **170 tests / 449 assertions PASS** in ~16s (in-memory SQLite). This
plan must keep that green and only add to it.

---

## 0. Audit findings (ground truth, not the prompt's premise)

The prompt's premise is "business logic and **direct Eloquent queries** live in
`app/Http/Controllers`." A code-level audit shows this is **largely already solved** in
this implementation, and the plan is recalibrated accordingly. Honest findings:

| Claim in prompt | Reality in code | Action |
|---|---|---|
| Eloquent calls inside controllers | grep finds **only** `LoginController` (social-login lookup/link) and `RegisterController::create()` using `User::` directly. CPanel controllers call `parent::create()` → `BaseController` → **repository**. `PageController` line is `back()->with(...)`, not Eloquent. | Extract the genuine 2 spots (auth) into a service. Everything else already conforms. |
| Business logic in controllers | CPanel controllers are already thin (validate via Form Request → repository). Auth controllers carry the only real branching logic (social linking, user creation). | Service-layer extraction limited to Auth. |
| No service layer | `app/Services/` exists but holds only `Captcha`. No `Events`/`Listeners`/`Actions`. | Add `App\Services\Auth\SocialAuthService`; introduce Events for side effects (comment notification — a parity gap). |
| Repositories | Mature two-family repo layer (front + `CPanel*`) over `BaseRepository`. | Keep, extend; do **not** reinvent. |
| Policies / Observers / Form Requests / Middleware | All present and used (`UserPolicy`, `PostObserver`/`PageObserver`, `app/Http/Requests/*`, per-capability middleware). | Keep, extend. |

**Conclusion:** Task 2 (architecture refactor) is **small and surgical**, not a rescue.
The bulk of remaining effort is **feature parity**, **tooling/CI**, **test coverage**, and
the **UI redesign** to `DESIGN_SYSTEM.md`.

---

## 1. Architecture refactor — pattern mapping

Each row: target method → chosen pattern → justification → rejected alternative.

| Fat method / concern | Pattern applied | Justification | Rejected alternative |
|---|---|---|---|
| `LoginController::handleProviderCallback` + `findOrLinkUser` + `createUser` + `validateSocialUser` | **Service** (`App\Services\Auth\SocialAuthService`) | Multi-step branching (lookup by provider id → link by email → create) + a DB transaction is business logic, not HTTP concern. Controller becomes: resolve socialite user → call service → `Auth::login` → redirect. | *Action classes* — overkill for one cohesive flow; a single service with named methods is clearer and testable in isolation. |
| `RegisterController::create()` building a `User` | **Service** (reuse `SocialAuthService`-adjacent `UserRegistrar` OR existing `UserRepository`) | Centralizes the "create a user with hashed password + default role" rule so social + form registration share one code path. | *Leave in trait* — keeps a second source of truth for user creation; rejected. |
| New-comment side effect (notify author/moderators) — **parity gap** §18 | **Event + Listener** (`CommentSubmitted` → `SendCommentNotification`) | Side effects belong off the request path (Observer pattern family); email must not block the submit response and must be testable via `Event::fake()`. | *Inline mail in controller/repo* — couples submit to mail, untestable, blocks request; rejected. |
| Scheduled publishing — **net-new** §1 | **Console command + scheduler** (`posts:publish-due`) | A cron-driven sweep is the idiomatic Laravel approach; pure data transition. | *Observer/queue per item* — no trigger exists until the due time; rejected. |

No Strategy/Factory/Adapter/Bridge is introduced: there is no real branching or
external-integration concern here that justifies them. Adding them would be over-engineering
(scope guardrail) — recorded as deliberately rejected.

---

## 2. Feature-parity gaps for agentic-cms-laravel (from FEATURE_MATRIX §"agentic-cms-laravel needs")

Ordered by value/independence. Each becomes its own TDD slice with characterization +
feature tests. **None may silently drop existing behavior.**

| # | Gap | Matrix ref | Notes |
|---|---|---|---|
| P1 | **Tags** taxonomy (M2M with posts, slug, browseable archive) | §2 | Mirror existing `Category` model/repo/observer; add `tags` + `taggables` (or `post_tag`) + translations. |
| P2 | **Revisions** for posts+pages (immutable snapshot before update) **+ restore UI** | §1 | Net-new restore UI is shared across all three stacks. Snapshot in observer/service on update. |
| P3 | **Soft-delete for pages** (trash/restore/permanent-delete) | §1 | Posts already soft-delete; extend to `Page` + admin bulk actions. |
| P4 | **Category tree admin UI** (parent picker at minimum) | §2 | Column exists; add hierarchy editing. |
| P5 | **Comment-notification email** (author/moderators) | §18 | Implemented via Event/Listener from §1 mapping. |
| P6 | **Scheduled publishing** (`scheduled_at` + command) | §1 | Console command + `routes/console.php` schedule. |
| P7 | **RSS/Atom feeds** (`/rss.xml`, per-category) | §16 | Net-new for all three; self-contained, fully headless-testable. |
| P8 | **Membership/registration toggle** wired + **email-verification enforcement** | §5 | Settings already dangle; wire to signup + `verified` middleware. |
| P9 | **Plugin/extensibility hook registry** (actions/filters + render-region) | §12 | Adopt django's canonical model. Largest; lands last. |

**Tooling parity (FEATURE_MATRIX §22):** Pint (local lint), Larastan/PHPStan (static
analysis), coverage reporting, CI pipeline (lint → static → test → build → e2e).

**Flagged matrix note (do NOT edit shared file):** none found so far that contradict the
matrix; this section is the place to record any discovered discrepancy for the human.

---

## 3. UI redesign to DESIGN_SYSTEM.md (Blade + Tailwind, native)

Scope: every public surface (`resources/views/default/`) and admin
(`resources/views/.../CPanel` / admin theme). Approach:

1. **Tokens first** — define §2 color tokens + §4 radius/spacing as CSS custom properties
   on `:root`/`.dark`, scoped under `.theme-default` (public) and `.theme-admin` (admin)
   per the existing preflight-disabled convention. Bridge into `tailwind.config.js`
   `theme.extend` (`bg-surface`, `text-muted`, `border-border`, …).
2. **Fonts** — self-host Newsreader / Inter / Geist Mono variable woff2, subset, `preload`
   the two critical weights, `font-display: swap`. No CDN.
3. **Components** — build the §5 component set as Blade components/partials (buttons,
   inputs, cards, nav header, admin sidebar/topbar, tables w/ bulk bar, tabs, breadcrumbs,
   dropdown, avatar, dropzone, sortable list, rich-text chrome, badges, modals, toasts,
   alerts, pagination, empty states, prose).
4. **Performance budget (§7)** — near-zero public JS (Alpine islands only), purged
   Tailwind, lazy editor, responsive images. **Lighthouse ≥95 mobile (Perf/SEO/A11y/BP)
   must be measured with a real run** — requires a served app + Lighthouse; flagged as
   needing an environment that can run headless Chrome against MySQL-backed app. Do not
   claim the score without the run.
5. **A11y (§8)** — WCAG 2.1 AA: landmarks, focus-visible, contrast (token set already
   compliant), keyboard nav, reduced-motion. Verify per screen.

> **Honesty note:** the full UI redesign + measured Lighthouse is the single largest piece
> and spans multiple work sessions. It is sequenced after refactor + tooling + parity
> primitives so the design system lands on a stable base.

---

## 4. Tests (FEATURE_MATRIX §22 / prompt Task 4)

- Keep the 170-test baseline green at every step.
- **Characterization tests before each refactor** (pin current behavior).
- Targets: **≥80% line coverage on services/repositories**, **100% of critical request
  paths** (auth, content CRUD, publishing, media). Add coverage reporting (Xdebug/PCOV).
- Feature/integration test per refactored controller; regression test for every
  adversarial finding.
- Run full suite **with coverage** and show real output — never assert passing without it.

---

## 5. Execution sequence (this is the order of slices)

1. ✅ `REFACTOR_PLAN.md` (this doc).
2. **Slice A — Auth service extraction** (Task 2 core). TDD: characterization tests for
   social-login linking + register, then extract to `SocialAuthService`, controllers thin.
   Plan: `docs/superpowers/plans/2026-06-24-auth-service-extraction.md`.
3. **Slice B — Quality tooling**: Larastan + Pint + coverage config; make suite + static
   analysis green.
4. **Slice C — Parity primitives** P5 (comment-notification via Event/Listener) and P7
   (RSS feeds) — both self-contained and fully headless-testable.
5. **Slice D+** — remaining parity (P1–P4, P6, P8, P9) per priority.
6. **Slice UI** — DESIGN_SYSTEM rollout (tokens → fonts → components → budget/a11y),
   measured Lighthouse.
7. **README** rewrite + **completeness-critic** pass.

Each slice: TDD → run suite (show output) → 2–3 independent adversarial skeptics
(behavior-preservation / correctness / security / performance) → fix → re-verify →
refresh `HANDOFF.md`.

---

## 1b. HARD RULE (prompt update) — ZERO business logic / data access in controllers

The prompt now mandates (top priority): every controller in `app/Http/Controllers`
is a **pure HTTP boundary** — read/validate (Form Request) → call a **service** → map
result to response. **No** repository calls, Eloquent/query-builder/raw SQL, domain
conditionals, multi-step orchestration, or result→meaning mapping in controllers. Chain is
strictly **Controller → Service → Repository**. Adversarial verification must reject any
controller still holding logic/data access.

### Full controller audit (drives the refactor)

The two base controllers do the data access for nearly the whole app:
- `CPanelBaseController` — `create/update/edit/delete/restore/destroy/deleteAjax/destroyAjax`
  all call `$this->repository->…` and map results to `green/red`/echo-trans. → retarget at a
  **`BaseCrudService`** (returns domain results, not redirects). Cleans inherited violations
  in every admin controller's `parent::*` calls at once.
- `BaseController` (front) — `index/modifyTranslatedSlug/setLang` do repo `getBy` + slug/locale
  orchestration. → **`ContentResolverService`** + **`LocaleService`**. Cleans front
  `PostController/PageController/CategoryController` `index()` at once.

| Controller | Violating methods | Owning service |
|---|---|---|
| CPanelBaseController | 8 (all CRUD + ajax + setLang) | **BaseCrudService** + LocaleService |
| BaseController (front) | 3 (index, modifyTranslatedSlug, setLang) | **ContentResolverService** + LocaleService |
| CPanelPostController | 8 | CPanelPostService |
| CPanelCommentController | 5 | CPanelCommentService |
| CPanelCategoryController | 5 | CPanelCategoryService |
| CPanelMenuController | 5 (incl. slug-mutation rule) | CPanelMenuService |
| CPanelPageController | 4 | CPanelPageService |
| CPanelUserController | 4 | CPanelUserService |
| CPanelRoleController | 4 (3 inherited + index) | CPanelRoleService |
| CPanelHomeController | 4 (raw Eloquent: Post/User/Comments L34/40/46) | CPanelDashboardService |
| CPanelSeoSettingsController | 2 (raw firstOrNew→fill→save) | SeoSettingsService |
| CPanelGeoSettingsController | 2 (raw firstOrNew→fill→save) | GeoSettingsService |
| CPanelGeneralSettingController | 2 | GeneralSettingsService |
| CPanelSiteOptionsController | 2 | SiteOptionsService |
| CPanelLanguageController | 1 | LocaleService |
| CPanelMediaController | 0 (drop unused import) | — |
| SeoController | 5 (raw Eloquent: Page/Post/Category joins L48-62, L234) | SeoFeedService |
| PostController | 2 (index inherited, handleLike) | PostViewService + PostLikeService |
| PageController | 4 (index, sendMail, searchResult, paginatedResult) | PageViewService + ContactService + SearchService |
| PostCommentController | 3 (store/delete/update) | CommentService |
| UserController | 4 (yourProfile/update/changePassword/show) | ProfileService |
| CategoryController | 1 (index) | CategoryViewService |
| Auth/* | 0 — COMPLIANT (SocialAuthService, UserRegistrationService) | done |

### Service-layer design
`App\Services\BaseCrudService` wraps a `BaseRepositoryInterface` and exposes
`list($perPage) / getById($id) / create($request) / update($id,$data) / delete($ids) /
destroy($ids) / restore($ids)` returning **domain results** (entity/bool/paginator), never
redirects/views. Each domain service `extends BaseCrudService` and passes its repository to
`parent::__construct()`, adding domain methods (bulk actions, approve/unapprove, settings
singleton save, slug rules). Controllers inject the **domain service** (not the repository),
`CPanelBaseController` holds `protected $service` and delegates; response mapping
(redirect/`green|red`/echo-trans/json/view) stays in the controller.

### Execution order (Slice C)
1. **C1 (foundation):** `BaseCrudService`; retarget `CPanelBaseController` → `$this->service`.
2. **C2 (front foundation):** `ContentResolverService` + `LocaleService`; retarget `BaseController`.
3. **C3:** posts + comments controllers (admin + front).
4. **C4:** pages, categories, menu, users, roles.
5. **C5:** settings (Seo/Geo/General/SiteOptions/Language), CPanelHomeController, SeoController.
Each slice: characterization test → refactor → suite green (shown) → adversarial skeptic →
fix → commit.

---

## 1c. HARD RULE — services never touch the ORM; side effects via observers

The prompt mandates: the **service layer accesses data only through repositories**
(no `Model::`, `new Model()`, `->fill/->save/->update/->delete` on Eloquent, no query
builder, no `DB::`, no raw SQL in services). Side effects of writes (notifications, cache
invalidation, audit, reindex) are **not** invoked inline — the service emits a **domain
event** and **listeners/observers** handle the effect. Strict chain:
**controller → service → repository → model**, plus **service → event → listener/observer**.

This was applied to every service: auth (`SocialAuthService`/`UserRegistrationService` →
`UserRepository::findOrLinkSocialIdentity/createFromSocial/createFromRegistration/
setPlainPassword`), settings (`Seo/GeoSettingsService::save` → repo `saveSingleton`),
dashboard (`CPanelDashboardService` → `CPanel{Post,User,Comment}Repository` read methods),
and the front `SeoFeedService` (raw sitemap/llms joins → `*Repository::sitemapEntries/
llmsEntries`). Verified: `grep` shows zero ORM access in any service body; adversarial
skeptics confirmed the layering is clean.

### Event classification policy (required by the rule)

Every domain event is classified **synchronous** (must run in the same DB transaction —
atomic with the write) or **asynchronous** (fire-and-forget via the queue). Atomic effects
run as in-transaction synchronous listeners, never detached queued jobs. Current/planned
events:

| Event | Effect (listener/observer) | Classification | Rationale |
|---|---|---|---|
| `CommentSubmitted` (planned, parity §18) | email post author + moderators | **async** (queued) | email is fire-and-forget; must not block or roll back the comment write |
| Content updated (Post/PageTranslation `updating`) | snapshot the prior translation into `revisions` | **sync** (in-transaction) | the snapshot must be atomic with the update — `BaseRepository::update` / `RevisionRepository::restoreFrom` wrap the save in `DB::transaction`, so a failed write leaves no orphaned revision |
| Content published/updated | cache invalidation | **sync** (in-transaction) | cached reads must be consistent with the committed write. Today handled by `genealabs/laravel-model-caching` auto-flush on save (Observer-equivalent); when moved to explicit events, keep synchronous |
| `PostLiked` (existing like toggle) | like-count consistency | **sync** | the count must be atomic with the like row; currently inside the repo write |
| Audit log (future) | append audit record | **async** | non-critical trail; queue acceptable |

No side effect is currently fired inline from a service. When the comment-notification
feature lands (parity P5), it must be an async queued listener on `CommentSubmitted`, not a
`Mail::send` inside `CommentService`. (`ContactService::send` is exempt: the contact form's
mail *is* the primary user action, not a side effect of a DB write.)

---

## 6. Status log

- 2026-06-24: Audit complete; baseline 170 green; plan written.
- 2026-06-24: Slice A (auth service extraction) done + adversarially verified; fixed a
  latent double-hash bug (register + password reset). Suite 182 green.
- 2026-06-24: Slice B (Pint + Larastan level5/baseline + composer scripts) done; analyse green.
- 2026-06-24: Prompt updated with the ZERO-logic-in-controllers hard rule; full controller
  audit added above.
- 2026-06-24: Slice C DONE. Introduced `BaseCrudService` + `App\Services\CPanel\*` +
  `App\Services\Front\*`; refactored **every** controller (admin + front) to a pure HTTP
  boundary. No controller touches a repository (`grep $this->repository` in controllers =
  none). Suite 182 green; PHPStan level-5 green (baseline 91→86).
- 2026-06-24: Prompt updated with the services-only-through-repositories + events/observers
  hard rule. Moved all remaining ORM access out of services into repository methods
  (auth/settings/dashboard/seo-feed). `grep` shows zero ORM in service bodies. Added the
  event sync/async classification policy (§1c).
- 2026-06-24: Adversarial verification of the whole service layer (3 skeptics: layering /
  behavior / security+perf). Verdict: layering clean, behavior preserved, no security/perf
  regression. One finding fixed — `ResetPasswordController` model mutation moved to
  `UserRepository::setPlainPassword`. Suite 182 green; PHPStan green.
- **Architecture refactor (Task 2) COMPLETE and verified.**
- 2026-06-24: Parity P5 + §3 DONE. Comment-notification via `CommentSubmitted` event →
  queued `SendCommentNotification` listener → `CommentSubmittedMail` (the first real
  event/observer, async per §1c). Adversarial review surfaced a missing submit rate limit;
  added `throttle:8,1` + `max:5000` (closes parity §3 "Submit rate limiting"). Suite 186 green.
  Remaining (see HANDOFF.md): parity P1–P4/P6–P9, UI redesign, coverage→80%/CI, README.
- 2026-06-24: Parity P1 (Tags taxonomy) DONE end-to-end (see HANDOFF.md). Suite 196 green.
- 2026-06-25: Parity **Revisions + restore UI** DONE (FEATURE_MATRIX §1, net-new for all
  stacks). Immutable pre-update snapshot of the Post/PageTranslation row via the translation
  `updating` observer → `RevisionRepository` (polymorphic `revisions` table). Admin history
  list (paginated) + per-field diff + scoped restore for posts and pages; restore is itself
  revisioned (undoable). Snapshot/restore are sync/in-transaction (§1c row added). 3 adversarial
  skeptics (correctness / security / architecture-perf) run; fixes applied: (1) restore uses an
  ALLOW-list of editorial fields — never reassigns `author_id`, drops stray keys, and guards the
  unique(locale,title,slug) collision (no 500); (2) update + restore wrapped in `DB::transaction`
  (no orphaned revisions); (3) revision routes 404 for trashed posts (consistent with editPost);
  (4) `listFor` paginated (no unbounded full-row JSON load); plus added authz + compare-IDOR +
  authorship + collision + trashed regression tests. Suite **211 green**, Pint + PHPStan clean.
  Known v1 limitations (tracked, acceptable): `revisions.data` stores the full translation row as
  JSON per edit (no pruning/dedup — revisit if storage grows); `revisionable_type` stores the FQCN
  (no morph map). Remaining: parity P3/P4/P6–P9, UI redesign, coverage→80%/CI, README.
- 2026-06-25: Parity **Soft-delete for pages** (P3) DONE (FEATURE_MATRIX §1). `Page` now uses
  `SoftDeletes` (`pages.deleted_at` migration); CPanelPage{Repository,Service,Controller} mirror
  the posts trash/restore/permanent-destroy + bulk-action design; pages_list gains the
  published/trashed tab + bulk bar + row actions (+ page.js destroy); en/ru lang. Delete is now a
  soft-delete (was hard); force-delete cascades page_translations via the existing FK. 3
  adversarial skeptics (front-regression / security / architecture): new code clean; fixes —
  permanent-destroy restricted to `onlyTrashed()` rows in BOTH posts & pages (can no longer nuke a
  live row in one step), and a pre-existing **shadowed** single-post-restore route fixed (GET
  `/{id}/restore` moved before the greedy `/{id}/{lang}` editor route). +11 tests incl.
  front-404/sitemap-exclusion + authz on the new routes. Suite **222 green**, Pint + PHPStan clean.
  Note: sitemap.xml is `Cache::remember`-cached for 1h (eventually-consistent for ALL content
  changes, pre-existing — not specific to soft-delete). Remaining: parity P4/P6–P9, UI redesign,
  coverage→80%/CI, README.
- 2026-06-25: Parity **Category tree admin UI** (P4) DONE (FEATURE_MATRIX §2). The category form's
  parent picker was inert (field `parent_category` vs column `parent_category_id`; options used a
  missing `->id`; no cycle guard). Now functional: `CPanelCategoryRepository::parentOptions`/
  `descendantIds` build the current-locale tree (orphan-safe, cycle-safe) and exclude self +
  descendants; field renamed to `parent_category_id` (Astrotomic routes it to the translated
  column); indented dropdown + selected state. `CategoryRequest` enforces the cycle server-side via
  `Rule::notIn(self+descendants)` reading the route id (not the unbound constructor `$term_id`).
  3 adversarial skeptics: layering clean; fixes — (1) a fractional id ("5.5") bypassed
  `numeric`+loose-`notIn` then truncated to an int cycle → now normalised to int in
  `prepareForValidation` + rule `integer`; (2) MCP `UpdateCategoryTool` skipped the cycle check →
  added the same guard. +6 tests (web + MCP). Suite **229 green**, Pint + PHPStan clean.
  Remaining: parity P6–P9, UI redesign, coverage→80%/CI, README.
- 2026-06-25: Parity **Scheduled publishing** (P6) DONE (FEATURE_MATRIX §1, net-new for all
  stacks). `post_translations.scheduled_at` (nullable, indexed); `CPanelPostRepository::publishDue`
  → `CPanelPostService::publishDue` → `posts:publish-due` command (pure boundary), scheduled
  `everyMinute()->withoutOverlapping()`. Future-scheduled *drafts* are hidden from EVERY public
  read path via `Post::scopeNotScheduledForFuture` (status-aware: published posts always visible),
  applied at post detail (new `BaseRepository::applyFrontReadScope` hook — no-op default so other
  entities/admin are unaffected — overridden in the front `PostRepository`), sitemap, category/tag
  archives, search, and the home helper. Admin datetime-local schedule field + `ValidatePostData`.
  3 adversarial skeptics: no leaked public path, no regressions, layering clean; fix — made the
  scope status-aware so publishing overrides a lingering schedule. +9 tests. Suite **238 green**,
  Pint + PHPStan clean. Pre-existing gaps noted (NOT this feature): front never filters plain
  drafts (status=0, no schedule); MCP post tools don't expose scheduled_at. Remaining: parity
  P7–P9, UI redesign, coverage→80%/CI, README.
- 2026-06-26: Phase 4 test gap-filling DONE. Per-layer status table added (see below). Direct tests
  added for 6 previously-transitive-only layers: `WriteThemeFileTool` (security/path-traversal),
  `ChangePasswordRequest` (validation rules via route), `PostViewService` (slug resolution +
  scheduling visibility), `CategoryRepository` (sitemapEntries/llmsEntries/displayList),
  `CPanelPostService` (create + category pivot + trashed), `CPanelPageService` (create + update +
  delete/restore/trashed). Suite **321 green**, Pint + PHPStan clean.

---

## Per-layer test status (P10)

> Updated by P10 Phase 4. "Direct" = a test file that imports/instantiates the class or
> invokes its command/route as the explicit subject. "Transitive" = exercised only as a
> side effect of another test. No layer is at absolute zero; the gap-fillers below add
> direct tests to the highest-value previously-transitive-only layers (services/MCP).

| Layer | Files | Representative tests | Status |
|---|---|---|---|
| Models | 22 (`app/Http/Models/**`, incl. `*Translation`, `CPanel/`) | ObserverTest, MassAssignmentTest, TagModelTest, Revisions/*, Admin/* | ✅ covered (gaps: `CPanelMedia`, `Likes` class-level) |
| Controllers | 22 (front + Auth + CPanel) | AdminPanelAccessTest, Admin/*, Auth/*, Front/*, MediaRouteGuardTest | ✅ covered (gaps: `CPanelLanguageController`, `UserPermissionsController`) |
| Middleware | 14 (`app/Http/Middleware/**`) | AdminPanelAccessTest, Admin/* (manage_*), Auth/EmailVerificationTest, Auth/MembershipToggleTest, SearchContactLanguageTest | ✅ covered (untested are thin framework wrappers: CheckForMaintenanceMode, EncryptCookies, TrimStrings) |
| Form Requests | 21 (`app/Http/Requests/**`) | RepositoryBehaviorTest, Admin/*, Front/* (via routes) | ✅ covered (gap: `ChangePasswordRequest` → filled) |
| Policies | 1 (`UserPolicy`) | tests/Unit/UserPolicyTest.php | ✅ covered |
| Repositories | 21 (`app/Repositories/**`, front + CPanel*) | RepositoryBehaviorTest, RepositoryWhitelistTest, Plugins/*, TagRepositoryTest, Revisions/*, Admin/* | ✅ covered (direct gaps: front `CategoryRepository`/`PageRepository`, `CPanelRolePermissionsRepository` → CategoryRepository filled) |
| Services | 27 (`app/Services/**`: Auth, CPanel, Front, Captcha, Concerns) | CaptchaServiceTest, Auth/UserRegistrationServiceTest, Auth/SocialAuthServiceTest, CommentNotificationTest | ⚠️ → ✅ (CPanel + Front services were transitive-only; direct tests added for `CPanelPostService`, `CPanelPageService`, `PostViewService`) |
| Observers / Events / Listeners | 5 observers + `CommentSubmitted` + 2 listeners | ObserverTest, Revisions/PageRevisionTest, CommentNotificationTest, Auth/EmailVerificationTest | ✅ covered |
| Jobs | 0 (layer absent) | — | ✅ n/a |
| Console Commands | 1 (`PublishDuePosts`) | Scheduling/PublishDuePostsTest (+2) | ✅ covered |
| Providers / bindings | 8 (`app/Providers/**`) | PluginBootIntegrationTest, UserPolicyTest, CommentNotificationTest, CaptchaServiceTest | ✅ covered (AppServiceProvider/Broadcast/Observer/RouteServiceProvider verified transitively) |
| Blade components | 0 (`app/View/Components` absent) | — | ✅ n/a |
| Factories | 1 (`UserFactory`) | exercised by 20+ test files | ✅ covered |
| Helpers | `bootstrap/agentic-cms-laravel-helpers.php` | tests/Unit/HelpersTest.php | ✅ covered |
| MCP tools | 33 (`app/Mcp/**`: 28 tools + 1 server + 4 concerns) | Mcp/AgenticCmsLaravelServerTest (5 tools + Concerns), Mcp/WriteThemeFileToolTest | ⚠️ → ✅ (security-critical `WriteThemeFileTool` path-traversal test added) |

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
