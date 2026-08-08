# Newsletter — Phase 1: Subscribers & opt-in — Design

**Status:** approved 2026-08-07

## Goal

Let visitors subscribe to a newsletter with a GDPR-clean double opt-in flow, and
give admins a place to see and manage subscribers. This is Phase 1 of a larger
Newsletter module; it must stand on its own (people can subscribe, confirm and
unsubscribe; admins can view and manage the list) without any campaign-sending
code yet.

## Scope

**In scope (Phase 1):** subscriber data model; public subscribe form (footer
widget); double opt-in confirmation email; confirm + unsubscribe pages;
admin subscriber list with filter/search/manual-add/delete/CSV export; a
`manage_newsletter` permission; i18n en/de/ru; tests.

**Deferred (later phases, NOT this spec):** campaigns and sending infrastructure
(Phase 2), open/click tracking (Phase 3), segments/tags (Phase 4), drip
automations (Phase 5).

## Global constraints

- Laravel 12 / PHP 8.3; Inertia 3 + `@inertiajs/react` (React 19, TS); Vite 8;
  Pest + Vitest; Tailwind.
- Models live in `app/Http/Models/` (admin-only under `app/Http/Models/CPanel/`).
- Repository → Service → Controller layering (arch `LayeringTest`: only
  repositories touch the ORM/DB; controllers/services never do).
- Admin routes under the `agentic-cms-laravel-admin` prefix + `CPanel` namespace,
  behind `auth` + `see_admin_panel` + a per-resource permission middleware.
- i18n: `resources/lang/{en,de,ru}/cpanel/*.php` (admin) and `default/*.php`
  (public); frontend `tr(key, fallback)`.
- Public theme = the new Geist / zinc / violet-gradient system (`.theme-default`
  tokens). No new colors; reuse `--accent`, `.btn-accent`, tokens.
- TDD: failing test first, then minimal code.
- Deploy target Hostinger, no Docker in prod. Queue driver is env-driven
  (`sync` in dev, `database` in prod); the confirmation email is queued.

## Data model

Table `newsletter_subscribers` (model `App\Http\Models\NewsletterSubscriber`):

| column           | type                                   | notes |
|------------------|----------------------------------------|-------|
| `id`             | bigint pk                              | |
| `email`          | string, **unique**                     | stored lowercased/trimmed |
| `status`         | string enum `pending`/`confirmed`/`unsubscribed` | default `pending` |
| `token`          | string(64), unique                     | unguessable (`bin2hex(random_bytes(32))`); stable, used in BOTH confirm and unsubscribe URLs; never expires |
| `locale`         | string(8), nullable                    | captured at subscribe time (`get_current_lang()`) so emails/pages localize |
| `source`         | string(32)                             | `footer` \| `admin` (extensible) |
| `user_id`        | bigint nullable FK → users, nullOnDelete | set when a logged-in user subscribes / email matches |
| `confirmed_at`   | timestamp nullable                     | |
| `unsubscribed_at`| timestamp nullable                     | |
| timestamps       |                                        | `created_at` doubles as the consent timestamp |

Casts: `confirmed_at`/`unsubscribed_at` → datetime. `$hidden`: `token`.
Helper methods: `isConfirmed(): bool`, `isPending(): bool`.

**Why a stable token, not a signed URL:** unsubscribe links live in every future
email forever; Laravel signed URLs can expire and rotate with `APP_KEY` changes.
An unguessable per-subscriber token embedded in the path is simplest and correct
for a link that must work indefinitely. The confirm link reuses the same token.

### Layering

- `app/Repositories/NewsletterSubscriberRepository.php` — all ORM access:
  `findByEmail`, `findByToken`, `create`, `save`, `paginateFiltered($status, $search, $perPage)`, `confirmedEmails()` (for export).
- `app/Services/Newsletter/NewsletterSubscriptionService.php` — domain logic:
  `subscribe(string $email, string $source, ?string $locale, ?int $userId): NewsletterSubscriber` (idempotent — see below), `confirm(string $token): ?NewsletterSubscriber`, `unsubscribe(string $token): ?NewsletterSubscriber`, `resubscribe(string $token)`.
- `app/Services/CPanel/CPanelNewsletterService.php` — admin-side list/add/delete/export, delegating to the repository.

## Public flow

### 1. Subscribe

- Footer widget in `resources/js/layouts/PublicLayout.tsx`: an email input + a
  `.btn-accent` submit, driven by an Inertia `useForm` POSTing to
  `route('newsletter.subscribe')`. Honeypot field (hidden input, e.g. `website`)
  bots fill. Shows the flash message inline.
- Route `POST /newsletter/subscribe` → `NewsletterController@subscribe`.
  Middleware: `throttle:5,1` + captcha via the existing `App\Services\Captcha`
  (a `captcha` validation rule, nullable so it no-ops when reCAPTCHA keys are
  absent). FormRequest `SubscribeNewsletterRequest` validates
  `email` (required|email), honeypot empty.
- Service `subscribe()` is **idempotent and non-enumerating**:
  - no row for email → create `pending`, new token, send confirmation.
  - row `pending` → regenerate nothing, re-send confirmation (rate-limited by the
    route throttle).
  - row `confirmed` → do nothing (already subscribed).
  - row `unsubscribed` → set back to `pending`, send confirmation again.
  - Always returns the same generic flash: `default/newsletter.check_inbox`
    ("If that address is new, check your inbox to confirm."). The controller
    never reveals whether the email already existed.
- Response: `back()->with('newsletter_status', 'submitted')` (Inertia flash the
  footer widget reads to swap the form for a thank-you line).

### 2. Confirm

- Route `GET /newsletter/confirm/{token}` → `NewsletterController@confirm`
  (registered BEFORE the front catch-all `{locale?}/{slug?}`).
- Service `confirm($token)`: token→subscriber; if `pending` → `confirmed` +
  `confirmed_at=now()`; if already `confirmed` → no-op (friendly). Unknown token →
  null.
- Renders Inertia public page `public/NewsletterConfirm` with a status prop
  (`confirmed` | `already` | `invalid`), using `PublicLayout` shell.

### 3. Unsubscribe

- Route `GET /newsletter/unsubscribe/{token}` →
  `NewsletterController@unsubscribe`. One-click (GET, no auth): sets
  `unsubscribed` + `unsubscribed_at`. Unknown token → invalid state.
- Renders `public/NewsletterUnsubscribe` with the status + a "re-subscribe"
  button POSTing `route('newsletter.resubscribe')` with the token (flips back to
  `pending` and re-sends confirmation).

### Routes summary (`routes/web.php`, before the front catch-all)

```
POST /newsletter/subscribe            newsletter.subscribe        throttle:5,1 + captcha
GET  /newsletter/confirm/{token}      newsletter.confirm
GET  /newsletter/unsubscribe/{token}  newsletter.unsubscribe
POST /newsletter/resubscribe          newsletter.resubscribe      throttle:5,1
```

## Email

`App\Mail\NewsletterConfirmationMail` (queued via `ShouldQueue`), Blade/markdown
mailable mirroring the existing `ContactMail` style. Subject + body from
`resources/lang/{locale}/default/newsletter.php`, localized to the subscriber's
`locale`. Body: one confirm button (`route('newsletter.confirm', token)`) + a
plain-text fallback link + a line explaining why they got it. Sender from mail
config. Sent by the service on subscribe/resubscribe.

## Admin

New capability `manage_newsletter`, added exactly like `manage_media` (commit
`55e0e00` is the template):

- `ManageNewsletter` middleware + `app/Http/Kernel.php` alias `manage_newsletter`.
- `UserPolicy::manage_newsletter()` + a row in `UserPermissionsSeeder`.
- A **data migration** backfilling the ability onto the full-access
  (Administrator) role so the live install keeps access.
- Shared ability added to `HandleInertiaRequests::ABILITIES` so the nav item and
  screen gate on `auth.can.manage_newsletter`.
- Nav item "Newsletter" under Settings in `Sidebar.tsx`.

Screens/endpoints under `agentic-cms-laravel-admin/newsletter`:

- `GET /` → `CPanelNewsletterController@index` → Inertia `cpanel/newsletter/List`.
  Props: paginated subscribers (`email`, `status`, `locale`, `source`,
  `subscribed` date), current `status` filter, `search` term.
  UI: `StatusPill` per status (confirmed=success, pending=warning,
  unsubscribed=muted), filter chips, search box, `Pagination`.
- `POST /` → `store` (manual add): `ValidateNewsletterSubscriber` (email
  required|email|unique). Creates a **`confirmed`** subscriber, `source=admin`,
  `confirmed_at=now()` (admin vouches; no opt-in email). Flash success.
- `DELETE /{id}` → `destroy`: hard-delete the row. Flash success.
- `GET /export` → `export`: streamed CSV of **confirmed** subscribers
  (`email,locale,source,confirmed_at`) via the repository. Filename
  `newsletter-subscribers-YYYY-MM-DD.csv`.

i18n: `resources/lang/{en,de,ru}/cpanel/newsletter.php` (+ a `cpanel/menu.newsletter`
key) and `resources/lang/{en,de,ru}/default/newsletter.php` (public strings +
email copy).

## Security / GDPR

- Double opt-in (no email is `confirmed` without clicking the link, except
  admin-added ones which the admin vouches for).
- One-click unsubscribe, working forever via the stable token.
- Token unguessable: `bin2hex(random_bytes(32))` (64 hex chars), `unique`.
- No user enumeration: subscribe always returns the same generic message.
- `throttle:5,1` on subscribe/resubscribe; honeypot; captcha (reCAPTCHA v3,
  no-op without keys).
- Consent proof: `source` + `created_at` retained; `confirmed_at`/`unsubscribed_at`
  timestamps.
- `token` in `$hidden` so it never leaks through Inertia props / JSON.

## Testing (TDD)

**Backend (Pest feature), `Mail::fake()`:**
- subscribe with a new email → creates one `pending` row, queues
  `NewsletterConfirmationMail` to that address.
- subscribe with an existing pending/confirmed email → no duplicate row; still
  returns the generic message (confirmed → no second mail).
- unsubscribed email re-subscribing → back to `pending` + mail sent.
- honeypot filled → silently accepted, no row/mail (bot).
- confirm with a valid pending token → `confirmed` + `confirmed_at` set; page
  status `confirmed`. Already-confirmed token → status `already`. Unknown token →
  status `invalid`.
- unsubscribe with a valid token → `unsubscribed` + timestamp; resubscribe flips
  back to `pending` + mail.
- throttle: the 6th subscribe within a minute is rejected (429).
- admin: list renders the Inertia component with subscriber props; status filter
  + email search narrow the set; manual add creates a `confirmed` row; delete
  removes it; CSV export streams confirmed emails; **every** admin route is 403
  without `manage_newsletter`.
- the `manage_newsletter` data migration backfills the Administrator role.

**Frontend (Vitest + RTL):**
- footer subscribe widget renders, posts on submit, swaps to the thank-you line
  on `newsletter_status=submitted`.
- admin `List` renders a row per subscriber with the right status pill; active
  filter chip highlighted.

## Out of scope / open questions

None outstanding. Campaign sending, tracking, segments and automations are
explicitly later phases. Phase 1 introduces no sending of bulk mail — only the
single transactional confirmation email.
