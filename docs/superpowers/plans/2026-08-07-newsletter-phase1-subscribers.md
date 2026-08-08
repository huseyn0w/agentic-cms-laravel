# Newsletter Phase 1 — Subscribers & Opt-in Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. User preference for THIS plan: **inline TDD execution** (executing-plans), not subagent ceremony.

**Goal:** Let visitors subscribe to a newsletter via a GDPR-clean double opt-in flow (subscribe → confirmation email → confirm/unsubscribe pages) and give admins a gated screen to view, filter, add, delete and export subscribers.

**Architecture:** A standalone `newsletter_subscribers` table (not tied to `users`). Public flow: footer widget → `POST /newsletter/subscribe` → queued `NewsletterConfirmationMail` → stable-token `GET` confirm/unsubscribe Inertia pages. Layering is Repository → Service → thin Controller (only repositories touch the ORM). Admin CRUD sits under the `agentic-cms-laravel-admin/newsletter` prefix behind a new `manage_newsletter` permission built exactly like `manage_media` (commit `55e0e00`), including a data migration that backfills the Administrator role.

**Tech Stack:** Laravel 12 / PHP 8.3, Inertia 3 + `@inertiajs/react` (React 19, TS), Vite 8, Pest (feature) + Vitest 4 + RTL, Tailwind, `react-i18next`.

## Global Constraints

- Models live in `app/Http/Models/` (this model is public-facing, so NOT under `CPanel/`). Copy verbatim: `App\Http\Models\NewsletterSubscriber`.
- Repository → Service → Controller layering. Only repositories touch the ORM/DB. `LayeringTest` (arch) enforces this — controllers and services never call Eloquent directly.
- Admin routes: prefix `agentic-cms-laravel-admin`, namespace `CPanel`, behind `restrict_admin_ip` + `auth` + `see_admin_panel` + `require_2fa` (group middleware already applied) and the per-resource `manage_newsletter` middleware.
- Permission name is exactly `manage_newsletter`. Build it via the `manage_media` template: middleware + `app/Http/Kernel.php` alias + `UserPolicy::manage_newsletter()` + `UserPermissionsSeeder` row + `HandleInertiaRequests::ABILITIES` entry + a data migration that backfills any full-access (Administrator) role.
- Subscriber token: `bin2hex(random_bytes(32))` → 64 hex chars, `unique`, stable, never expires. Used in BOTH confirm and unsubscribe URLs. In model `$hidden`.
- `status` is one of exactly `pending` | `confirmed` | `unsubscribed`.
- Subscribe is idempotent and non-enumerating: always the same generic flash (`default/newsletter.check_inbox`); never reveal whether an email already existed.
- Public newsletter routes MUST be registered BEFORE the front catch-all `/{locale?}/{slug?}` (routes/web.php:298) or they are swallowed.
- i18n keys exist in all three locales: `resources/lang/{en,de,ru}/cpanel/newsletter.php`, `resources/lang/{en,de,ru}/default/newsletter.php`, and a `newsletter` entry in `resources/lang/{en,de,ru}/cpanel/menu.php`.
- Design system: public theme = `.theme-default` (Geist / zinc / violet-gradient); admin = Mono/Vercel `admin-card` / `admin-sep` / `StatusPill` tokens. No new colors — reuse `--accent`, `.btn-accent`, existing tokens.
- TDD: failing test first, then minimal code. Frequent commits (one per task minimum). NO `Co-Authored-By` trailer in commits.
- Branch: `feat/newsletter` (already checked out, off main). Queue driver env-driven (`sync` in dev/tests, `database` in prod); the confirmation email is queued.
- Verify commands: backend `php artisan test --filter=<Class>` (isolated in-memory SQLite); arch isolated `php -d memory_limit=1G artisan test tests/Arch/LayeringTest.php`; frontend `npx vitest run <file>`; style `vendor/bin/pint <files>`; static `composer analyse`; build `npm run build`.

---

## File Structure

**Backend (new):**
- `database/migrations/2026_08_07_000900_create_newsletter_subscribers_table.php` — the table.
- `database/migrations/2026_08_07_001000_add_manage_newsletter_permission.php` — permission + Administrator backfill.
- `app/Http/Models/NewsletterSubscriber.php` — model.
- `database/factories/NewsletterSubscriberFactory.php` — test factory.
- `app/Repositories/NewsletterSubscriberRepository.php` — all ORM access.
- `app/Mail/NewsletterConfirmationMail.php` — queued mailable.
- `resources/views/emails/newsletter-confirmation.blade.php` — markdown email body.
- `app/Services/Newsletter/NewsletterSubscriptionService.php` — public domain logic.
- `app/Services/CPanel/CPanelNewsletterService.php` — admin list/add/delete/export logic.
- `app/Http/Requests/SubscribeNewsletterRequest.php` — public subscribe validation.
- `app/Http/Requests/StoreNewsletterSubscriberRequest.php` — admin manual-add validation.
- `app/Http/Controllers/NewsletterController.php` — public subscribe/confirm/unsubscribe/resubscribe.
- `app/Http/Controllers/CPanel/CPanelNewsletterController.php` — admin index/store/destroy/export.
- `app/Http/Middleware/ManageNewsletter.php` — permission gate.

**Backend (modified):**
- `app/Http/Kernel.php` — alias `manage_newsletter`.
- `app/Policies/UserPolicy.php` — `manage_newsletter()` method.
- `database/seeds/UserPermissionsSeeder.php` — `manage_newsletter` row.
- `app/Http/Middleware/HandleInertiaRequests.php` — `manage_newsletter` in `ABILITIES` + `newsletter_status` flash.
- `routes/web.php` — public newsletter group (before catch-all) + admin newsletter group.

**Frontend (new):**
- `resources/js/components/public/NewsletterSubscribe.tsx` (+ `.test.tsx`) — footer widget.
- `resources/js/pages/public/NewsletterConfirm.tsx` — confirm result page.
- `resources/js/pages/public/NewsletterUnsubscribe.tsx` — unsubscribe result page.
- `resources/js/pages/cpanel/newsletter/List.tsx` (+ `.test.tsx`) — admin list.

**Frontend (modified):**
- `resources/js/layouts/PublicLayout.tsx` — mount `<NewsletterSubscribe />` in the footer.
- `resources/js/lib/types.ts` — add `manage_newsletter` to `Ability`, add `newsletter_status` to flash.
- `resources/js/lib/admin-nav.ts` — Newsletter nav item under Settings.

**i18n (new):**
- `resources/lang/{en,de,ru}/default/newsletter.php`
- `resources/lang/{en,de,ru}/cpanel/newsletter.php`

**i18n (modified):**
- `resources/lang/{en,de,ru}/cpanel/menu.php` — `'newsletter'` label.

**Tests (new):**
- `tests/Feature/Newsletter/NewsletterSubscriptionServiceTest.php`
- `tests/Feature/Newsletter/NewsletterSubscribeTest.php`
- `tests/Feature/Newsletter/NewsletterConfirmTest.php`
- `tests/Feature/Newsletter/AdminNewsletterTest.php`
- `resources/js/components/public/NewsletterSubscribe.test.tsx`
- `resources/js/pages/cpanel/newsletter/List.test.tsx`

---

## Task 1: Subscriber model, migration, factory, repository

**Files:**
- Create: `database/migrations/2026_08_07_000900_create_newsletter_subscribers_table.php`
- Create: `app/Http/Models/NewsletterSubscriber.php`
- Create: `database/factories/NewsletterSubscriberFactory.php`
- Create: `app/Repositories/NewsletterSubscriberRepository.php`
- Test: `tests/Feature/Newsletter/NewsletterSubscriptionServiceTest.php` (repository portion; the service is added in Task 3 — this file starts as repository tests and grows)

**Interfaces:**
- Produces:
  - `NewsletterSubscriber` model: fillable `email, status, token, locale, source, user_id, confirmed_at, unsubscribed_at`; casts `confirmed_at`/`unsubscribed_at` → `datetime`; `$hidden = ['token']`; `isConfirmed(): bool`, `isPending(): bool`.
  - `NewsletterSubscriberRepository`:
    - `findByEmail(string $email): ?NewsletterSubscriber`
    - `findByToken(string $token): ?NewsletterSubscriber`
    - `create($attributes): NewsletterSubscriber` (untyped param — overrides `BaseRepository::create`)
    - `save(NewsletterSubscriber $subscriber): void`
    - `paginateFiltered(?string $status, ?string $search, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator`
    - `confirmedEmails(): \Illuminate\Support\Collection` (rows: email, locale, source, confirmed_at)

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Newsletter/NewsletterSubscriptionServiceTest.php`:

```php
<?php

namespace Tests\Feature\Newsletter;

use App\Http\Models\NewsletterSubscriber;
use App\Repositories\NewsletterSubscriberRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function repo(): NewsletterSubscriberRepository
    {
        return app(NewsletterSubscriberRepository::class);
    }

    public function test_repository_creates_and_finds_by_email_and_token(): void
    {
        $sub = $this->repo()->create([
            'email' => 'reader@example.com',
            'status' => 'pending',
            'token' => str_repeat('a', 64),
            'locale' => 'en',
            'source' => 'footer',
        ]);

        $this->assertInstanceOf(NewsletterSubscriber::class, $sub);
        $this->assertTrue($sub->isPending());
        $this->assertFalse($sub->isConfirmed());

        $this->assertSame($sub->id, $this->repo()->findByEmail('reader@example.com')->id);
        $this->assertSame($sub->id, $this->repo()->findByToken(str_repeat('a', 64))->id);
        $this->assertNull($this->repo()->findByEmail('missing@example.com'));
        $this->assertNull($this->repo()->findByToken('nope'));
    }

    public function test_token_is_hidden_from_array_output(): void
    {
        $sub = $this->repo()->create([
            'email' => 'hide@example.com', 'status' => 'pending',
            'token' => str_repeat('b', 64), 'source' => 'footer',
        ]);

        $this->assertArrayNotHasKey('token', $sub->toArray());
    }

    public function test_paginate_filters_by_status_and_searches_email(): void
    {
        NewsletterSubscriber::factory()->create(['email' => 'a@keep.com', 'status' => 'confirmed']);
        NewsletterSubscriber::factory()->create(['email' => 'b@keep.com', 'status' => 'pending']);
        NewsletterSubscriber::factory()->create(['email' => 'c@other.com', 'status' => 'confirmed']);

        $confirmed = $this->repo()->paginateFiltered('confirmed', null, 10);
        $this->assertSame(2, $confirmed->total());

        $searched = $this->repo()->paginateFiltered(null, 'keep', 10);
        $this->assertSame(2, $searched->total());

        $both = $this->repo()->paginateFiltered('confirmed', 'keep', 10);
        $this->assertSame(1, $both->total());
    }

    public function test_confirmed_emails_returns_only_confirmed(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'yes@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'no@example.com', 'status' => 'pending']);

        $emails = $this->repo()->confirmedEmails()->pluck('email')->all();

        $this->assertSame(['yes@example.com'], $emails);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=NewsletterSubscriptionServiceTest`
Expected: FAIL — `Class "App\Http\Models\NewsletterSubscriber" not found`.

- [ ] **Step 3: Write the migration**

Create `database/migrations/2026_08_07_000900_create_newsletter_subscribers_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Newsletter subscribers (Phase 1). A standalone list — not tied to the users
 * table — so anyone can subscribe by email. `token` is a stable, unguessable
 * per-subscriber secret used in BOTH the confirm and unsubscribe URLs; it never
 * expires so unsubscribe links keep working forever. `created_at` doubles as the
 * consent timestamp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('status', 16)->default('pending'); // pending|confirmed|unsubscribed
            $table->string('token', 64)->unique();
            $table->string('locale', 8)->nullable();
            $table->string('source', 32)->default('footer');  // footer|admin
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
```

- [ ] **Step 4: Write the model**

Create `app/Http/Models/NewsletterSubscriber.php`:

```php
<?php

namespace App\Http\Models;

use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A newsletter subscriber (Phase 1). Standalone — not a User. Status moves
 * pending -> confirmed (double opt-in) or -> unsubscribed. The token is a stable
 * secret embedded in confirm/unsubscribe links, kept out of any serialized
 * output via $hidden.
 */
class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    protected $fillable = [
        'email',
        'status',
        'token',
        'locale',
        'source',
        'user_id',
        'confirmed_at',
        'unsubscribed_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    protected static function newFactory(): NewsletterSubscriberFactory
    {
        return NewsletterSubscriberFactory::new();
    }
}
```

- [ ] **Step 5: Write the factory**

Create `database/factories/NewsletterSubscriberFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Http\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    protected $model = NewsletterSubscriber::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'status' => NewsletterSubscriber::STATUS_PENDING,
            'token' => bin2hex(random_bytes(32)),
            'locale' => 'en',
            'source' => 'footer',
            'user_id' => null,
            'confirmed_at' => null,
            'unsubscribed_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => NewsletterSubscriber::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn () => [
            'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ]);
    }
}
```

- [ ] **Step 6: Write the repository**

Create `app/Repositories/NewsletterSubscriberRepository.php`:

```php
<?php

namespace App\Repositories;

use App\Http\Models\NewsletterSubscriber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * All ORM access for newsletter subscribers. The service/controller layers call
 * only these methods (arch LayeringTest keeps Eloquent out of them).
 */
class NewsletterSubscriberRepository extends BaseRepository
{
    public function __construct(NewsletterSubscriber $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    public function findByEmail(string $email): ?NewsletterSubscriber
    {
        return $this->model::query()->where('email', $email)->first();
    }

    public function findByToken(string $token): ?NewsletterSubscriber
    {
        return $this->model::query()->where('token', $token)->first();
    }

    /**
     * Insert a subscriber. Untyped param keeps the signature compatible with
     * BaseRepository::create while bypassing its translatable machinery (this
     * model is not translatable).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create($attributes): NewsletterSubscriber
    {
        return $this->model::query()->create($attributes);
    }

    public function save(NewsletterSubscriber $subscriber): void
    {
        $subscriber->save();
    }

    /**
     * Admin list: optionally narrowed by status and an email LIKE search,
     * newest first.
     */
    public function paginateFiltered(?string $status, ?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->model::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->where('email', 'like', '%'.$search.'%'))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Confirmed subscribers shaped for CSV export.
     *
     * @return Collection<int, NewsletterSubscriber>
     */
    public function confirmedEmails(): Collection
    {
        return $this->model::query()
            ->where('status', NewsletterSubscriber::STATUS_CONFIRMED)
            ->orderBy('email')
            ->get(['email', 'locale', 'source', 'confirmed_at']);
    }
}
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --filter=NewsletterSubscriptionServiceTest`
Expected: PASS (4 tests).

- [ ] **Step 8: Verify layering + style**

Run: `php -d memory_limit=1G artisan test tests/Arch/LayeringTest.php`
Expected: PASS (repository is the only ORM toucher).
Run: `vendor/bin/pint app/Http/Models/NewsletterSubscriber.php app/Repositories/NewsletterSubscriberRepository.php database/factories/NewsletterSubscriberFactory.php database/migrations/2026_08_07_000900_create_newsletter_subscribers_table.php`
Expected: PASS / auto-fixed.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Models/NewsletterSubscriber.php app/Repositories/NewsletterSubscriberRepository.php database/factories/NewsletterSubscriberFactory.php database/migrations/2026_08_07_000900_create_newsletter_subscribers_table.php tests/Feature/Newsletter/NewsletterSubscriptionServiceTest.php
git commit -m "Newsletter: subscriber model, migration, factory, repository"
```

---

## Task 2: Confirmation mailable + email view + English public strings

**Files:**
- Create: `app/Mail/NewsletterConfirmationMail.php`
- Create: `resources/views/emails/newsletter-confirmation.blade.php`
- Create: `resources/lang/en/default/newsletter.php`
- Test: `tests/Feature/Newsletter/NewsletterSubscribeTest.php` (mailable portion; grows in Task 4)

**Interfaces:**
- Consumes: `NewsletterSubscriber` (Task 1), route name `newsletter.confirm` (added in Task 4 — the mailable references `route('newsletter.confirm', $token)`, so this test is written to only assert the mailable builds with the subscriber; the route-dependent render is exercised in Task 4).
- Produces: `NewsletterConfirmationMail` (constructor `public NewsletterSubscriber $subscriber`), queued via `ShouldQueue`.

- [ ] **Step 1: Write the English strings first (dependency for the mailable subject)**

Create `resources/lang/en/default/newsletter.php`:

```php
<?php

/**
 * Public-facing newsletter strings (Phase 1): footer widget, confirm/unsubscribe
 * pages, and the double opt-in confirmation email. Consumed by the public Inertia
 * pages via react-i18next (default/newsletter.*) and by NewsletterConfirmationMail.
 */

return [
    // Footer subscribe widget
    'widget_heading' => 'Subscribe to the newsletter',
    'widget_subtitle' => 'Occasional updates. No spam. Unsubscribe anytime.',
    'widget_placeholder' => 'you@example.com',
    'widget_button' => 'Subscribe',
    'widget_submitted' => 'Thanks — check your inbox to confirm your subscription.',

    // Generic, non-enumerating flash returned by subscribe/resubscribe
    'check_inbox' => 'If that address is new, check your inbox to confirm your subscription.',

    // Confirmation email
    'email_subject' => 'Confirm your newsletter subscription',
    'email_heading' => 'Confirm your subscription',
    'email_intro' => 'You (or someone using this address) asked to subscribe to our newsletter. Confirm below to start receiving it.',
    'email_button' => 'Confirm subscription',
    'email_fallback' => 'If the button does not work, copy and paste this link into your browser:',
    'email_ignore' => 'If you did not request this, you can safely ignore this email.',

    // Confirm result page (public/NewsletterConfirm)
    'confirm_confirmed_title' => 'Subscription confirmed',
    'confirm_confirmed_body' => 'Thanks — your subscription is now active.',
    'confirm_already_title' => 'Already confirmed',
    'confirm_already_body' => 'This subscription was already confirmed. Nothing else to do.',
    'confirm_invalid_title' => 'Invalid link',
    'confirm_invalid_body' => 'This confirmation link is not valid. It may have been mistyped.',

    // Unsubscribe result page (public/NewsletterUnsubscribe)
    'unsub_done_title' => 'You have been unsubscribed',
    'unsub_done_body' => 'You will no longer receive the newsletter. Changed your mind?',
    'unsub_resubscribe_button' => 'Re-subscribe',
    'unsub_invalid_title' => 'Invalid link',
    'unsub_invalid_body' => 'This unsubscribe link is not valid.',
    'unsub_resubmitted' => 'Check your inbox to confirm your subscription again.',
];
```

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/Newsletter/NewsletterSubscribeTest.php`:

```php
<?php

namespace Tests\Feature\Newsletter;

use App\Http\Models\NewsletterSubscriber;
use App\Mail\NewsletterConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_mail_is_queueable_and_carries_the_subscriber(): void
    {
        $sub = NewsletterSubscriber::factory()->create(['email' => 'x@example.com']);

        $mail = new NewsletterConfirmationMail($sub);

        $this->assertInstanceOf(ShouldQueue::class, $mail);
        $this->assertSame('x@example.com', $mail->subscriber->email);
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --filter=NewsletterSubscribeTest`
Expected: FAIL — `Class "App\Mail\NewsletterConfirmationMail" not found`.

- [ ] **Step 4: Write the mailable**

Create `app/Mail/NewsletterConfirmationMail.php`:

```php
<?php

namespace App\Mail;

use App\Http\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Double opt-in confirmation email. Queued (ShouldQueue) and localized to the
 * subscriber's captured locale by the sender (service calls ->locale(...)).
 * Reuses the same stable token for the confirm link.
 */
class NewsletterConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber) {}

    public function build(): self
    {
        return $this
            ->subject(__('default/newsletter.email_subject'))
            ->markdown('emails.newsletter-confirmation', [
                'confirmUrl' => route('newsletter.confirm', $this->subscriber->token),
            ]);
    }
}
```

- [ ] **Step 5: Write the email view**

Create `resources/views/emails/newsletter-confirmation.blade.php`:

```blade
@component('mail::message')
# {{ __('default/newsletter.email_heading') }}

{{ __('default/newsletter.email_intro') }}

@component('mail::button', ['url' => $confirmUrl])
{{ __('default/newsletter.email_button') }}
@endcomponent

{{ __('default/newsletter.email_fallback') }}

[{{ $confirmUrl }}]({{ $confirmUrl }})

{{ __('default/newsletter.email_ignore') }}

@endcomponent
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=NewsletterSubscribeTest`
Expected: PASS (1 test).

- [ ] **Step 7: Style check + commit**

Run: `vendor/bin/pint app/Mail/NewsletterConfirmationMail.php resources/lang/en/default/newsletter.php`
Expected: PASS / auto-fixed.

```bash
git add app/Mail/NewsletterConfirmationMail.php resources/views/emails/newsletter-confirmation.blade.php resources/lang/en/default/newsletter.php tests/Feature/Newsletter/NewsletterSubscribeTest.php
git commit -m "Newsletter: queued double opt-in confirmation mailable + en strings"
```

---

## Task 3: Subscription service (idempotent subscribe / confirm / unsubscribe / resubscribe)

**Files:**
- Create: `app/Services/Newsletter/NewsletterSubscriptionService.php`
- Test: extend `tests/Feature/Newsletter/NewsletterSubscriptionServiceTest.php`

**Interfaces:**
- Consumes: `NewsletterSubscriberRepository` (Task 1), `NewsletterConfirmationMail` (Task 2).
- Produces: `NewsletterSubscriptionService`:
  - `subscribe(string $email, string $source, ?string $locale, ?int $userId = null): NewsletterSubscriber`
  - `confirm(string $token): ?NewsletterSubscriber`
  - `unsubscribe(string $token): ?NewsletterSubscriber`
  - `resubscribe(string $token): ?NewsletterSubscriber`

- [ ] **Step 1: Write the failing tests (append to the Task 1 test file)**

Append these methods to `tests/Feature/Newsletter/NewsletterSubscriptionServiceTest.php` (add `use App\Mail\NewsletterConfirmationMail;`, `use App\Services\Newsletter\NewsletterSubscriptionService;`, `use Illuminate\Support\Facades\Mail;` to the imports):

```php
    private function service(): NewsletterSubscriptionService
    {
        return app(NewsletterSubscriptionService::class);
    }

    public function test_subscribe_new_email_creates_pending_and_queues_mail(): void
    {
        Mail::fake();

        $sub = $this->service()->subscribe('new@example.com', 'footer', 'de');

        $this->assertTrue($sub->isPending());
        $this->assertSame('de', $sub->locale);
        $this->assertSame(64, strlen($sub->token));
        $this->assertDatabaseCount('newsletter_subscribers', 1);
        Mail::assertQueued(NewsletterConfirmationMail::class, fn ($m) => $m->subscriber->email === 'new@example.com');
    }

    public function test_subscribe_existing_pending_does_not_duplicate_but_resends(): void
    {
        Mail::fake();
        NewsletterSubscriber::factory()->create(['email' => 'dup@example.com', 'status' => 'pending']);

        $this->service()->subscribe('dup@example.com', 'footer', 'en');

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        Mail::assertQueued(NewsletterConfirmationMail::class);
    }

    public function test_subscribe_existing_confirmed_is_noop_and_sends_nothing(): void
    {
        Mail::fake();
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'has@example.com']);

        $this->service()->subscribe('has@example.com', 'footer', 'en');

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        Mail::assertNothingQueued();
    }

    public function test_subscribe_unsubscribed_reactivates_to_pending_and_sends(): void
    {
        Mail::fake();
        NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'back@example.com']);

        $sub = $this->service()->subscribe('back@example.com', 'footer', 'en');

        $this->assertTrue($sub->isPending());
        Mail::assertQueued(NewsletterConfirmationMail::class);
    }

    public function test_confirm_flips_pending_to_confirmed(): void
    {
        $sub = NewsletterSubscriber::factory()->create(['token' => str_repeat('c', 64), 'status' => 'pending']);

        $result = $this->service()->confirm(str_repeat('c', 64));

        $this->assertTrue($result->isConfirmed());
        $this->assertNotNull($result->confirmed_at);
    }

    public function test_confirm_unknown_token_returns_null(): void
    {
        $this->assertNull($this->service()->confirm('does-not-exist'));
    }

    public function test_unsubscribe_and_resubscribe_round_trip(): void
    {
        Mail::fake();
        $sub = NewsletterSubscriber::factory()->confirmed()->create(['token' => str_repeat('d', 64)]);

        $un = $this->service()->unsubscribe(str_repeat('d', 64));
        $this->assertSame('unsubscribed', $un->status);
        $this->assertNotNull($un->unsubscribed_at);

        $re = $this->service()->resubscribe(str_repeat('d', 64));
        $this->assertTrue($re->isPending());
        Mail::assertQueued(NewsletterConfirmationMail::class);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=NewsletterSubscriptionServiceTest`
Expected: FAIL — `Class "App\Services\Newsletter\NewsletterSubscriptionService" not found`.

- [ ] **Step 3: Write the service**

Create `app/Services/Newsletter/NewsletterSubscriptionService.php`:

```php
<?php

namespace App\Services\Newsletter;

use App\Http\Models\NewsletterSubscriber;
use App\Mail\NewsletterConfirmationMail;
use App\Repositories\NewsletterSubscriberRepository;
use Illuminate\Support\Facades\Mail;

/**
 * Domain logic for the double opt-in lifecycle. subscribe() is idempotent and
 * non-enumerating: it never tells the caller whether the email already existed,
 * and it only sends a confirmation when a confirmation is actually warranted
 * (new, still-pending, or reactivated). All persistence goes through the
 * repository.
 */
class NewsletterSubscriptionService
{
    public function __construct(private NewsletterSubscriberRepository $repo) {}

    public function subscribe(string $email, string $source, ?string $locale, ?int $userId = null): NewsletterSubscriber
    {
        $email = mb_strtolower(trim($email));
        $existing = $this->repo->findByEmail($email);

        if ($existing === null) {
            $sub = $this->repo->create([
                'email' => $email,
                'status' => NewsletterSubscriber::STATUS_PENDING,
                'token' => $this->newToken(),
                'locale' => $locale,
                'source' => $source,
                'user_id' => $userId,
            ]);

            $this->sendConfirmation($sub);

            return $sub;
        }

        // Already confirmed: nothing to do, send nothing.
        if ($existing->isConfirmed()) {
            return $existing;
        }

        // Pending or unsubscribed: (re)arm to pending and resend confirmation.
        $existing->status = NewsletterSubscriber::STATUS_PENDING;
        $existing->unsubscribed_at = null;
        if ($locale !== null) {
            $existing->locale = $locale;
        }
        $this->repo->save($existing);

        $this->sendConfirmation($existing);

        return $existing;
    }

    public function confirm(string $token): ?NewsletterSubscriber
    {
        $sub = $this->repo->findByToken($token);

        if ($sub === null) {
            return null;
        }

        if ($sub->isPending()) {
            $sub->status = NewsletterSubscriber::STATUS_CONFIRMED;
            $sub->confirmed_at = now();
            $this->repo->save($sub);
        }

        return $sub;
    }

    public function unsubscribe(string $token): ?NewsletterSubscriber
    {
        $sub = $this->repo->findByToken($token);

        if ($sub === null) {
            return null;
        }

        $sub->status = NewsletterSubscriber::STATUS_UNSUBSCRIBED;
        $sub->unsubscribed_at = now();
        $this->repo->save($sub);

        return $sub;
    }

    public function resubscribe(string $token): ?NewsletterSubscriber
    {
        $sub = $this->repo->findByToken($token);

        if ($sub === null) {
            return null;
        }

        $sub->status = NewsletterSubscriber::STATUS_PENDING;
        $sub->unsubscribed_at = null;
        $this->repo->save($sub);

        $this->sendConfirmation($sub);

        return $sub;
    }

    private function sendConfirmation(NewsletterSubscriber $sub): void
    {
        Mail::to($sub->email)
            ->locale($sub->locale ?? config('app.locale'))
            ->queue(new NewsletterConfirmationMail($sub));
    }

    private function newToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=NewsletterSubscriptionServiceTest`
Expected: PASS (all repository + service tests).

- [ ] **Step 5: Verify layering + style + commit**

Run: `php -d memory_limit=1G artisan test tests/Arch/LayeringTest.php`
Expected: PASS (service uses the repository, no direct ORM).
Run: `vendor/bin/pint app/Services/Newsletter/NewsletterSubscriptionService.php tests/Feature/Newsletter/NewsletterSubscriptionServiceTest.php`

```bash
git add app/Services/Newsletter/NewsletterSubscriptionService.php tests/Feature/Newsletter/NewsletterSubscriptionServiceTest.php
git commit -m "Newsletter: idempotent subscription service (subscribe/confirm/unsubscribe/resubscribe)"
```

---

## Task 4: Public controller, routes, form request, flash wiring

**Files:**
- Create: `app/Http/Requests/SubscribeNewsletterRequest.php`
- Create: `app/Http/Controllers/NewsletterController.php`
- Modify: `routes/web.php` (new `newsletter` group before the front catch-all)
- Modify: `app/Http/Middleware/HandleInertiaRequests.php:81-85` (add `newsletter_status` flash)
- Test: extend `tests/Feature/Newsletter/NewsletterSubscribeTest.php`, create `tests/Feature/Newsletter/NewsletterConfirmTest.php`

**Interfaces:**
- Consumes: `NewsletterSubscriptionService` (Task 3).
- Produces: routes `newsletter.subscribe` (POST), `newsletter.confirm` (GET `{token}`), `newsletter.unsubscribe` (GET `{token}`), `newsletter.resubscribe` (POST). Inertia pages `public/NewsletterConfirm` and `public/NewsletterUnsubscribe` with a `status` prop.

- [ ] **Step 1: Write the failing subscribe tests (append to NewsletterSubscribeTest.php)**

Append to `tests/Feature/Newsletter/NewsletterSubscribeTest.php` (add `use Illuminate\Support\Facades\Mail;`):

```php
    public function test_subscribe_endpoint_creates_pending_and_flashes_generic_message(): void
    {
        Mail::fake();

        $this->post('/newsletter/subscribe', ['email' => 'foot@example.com'])
            ->assertRedirect()
            ->assertSessionHas('newsletter_status', 'submitted');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'foot@example.com', 'status' => 'pending', 'source' => 'footer',
        ]);
        Mail::assertQueued(NewsletterConfirmationMail::class);
    }

    public function test_subscribe_endpoint_is_non_enumerating_for_confirmed_email(): void
    {
        Mail::fake();
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'known@example.com']);

        // Same generic outcome, no second row, no mail.
        $this->post('/newsletter/subscribe', ['email' => 'known@example.com'])
            ->assertRedirect()
            ->assertSessionHas('newsletter_status', 'submitted');

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        Mail::assertNothingQueued();
    }

    public function test_honeypot_filled_is_silently_accepted_with_no_row_or_mail(): void
    {
        Mail::fake();

        $this->post('/newsletter/subscribe', ['email' => 'bot@example.com', 'website' => 'http://spam'])
            ->assertRedirect()
            ->assertSessionHas('newsletter_status', 'submitted');

        $this->assertDatabaseCount('newsletter_subscribers', 0);
        Mail::assertNothingQueued();
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->post('/newsletter/subscribe', ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_subscribe_is_throttled_after_five_per_minute(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/newsletter/subscribe', ['email' => "r{$i}@example.com"])->assertRedirect();
        }

        $this->post('/newsletter/subscribe', ['email' => 'sixth@example.com'])->assertStatus(429);
    }
```

- [ ] **Step 2: Write the failing confirm/unsubscribe tests**

Create `tests/Feature/Newsletter/NewsletterConfirmTest.php`:

```php
<?php

namespace Tests\Feature\Newsletter;

use App\Http\Models\NewsletterSubscriber;
use App\Mail\NewsletterConfirmationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NewsletterConfirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_valid_token_renders_confirmed_status(): void
    {
        NewsletterSubscriber::factory()->create(['token' => str_repeat('e', 64), 'status' => 'pending']);

        $this->get('/newsletter/confirm/'.str_repeat('e', 64))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/NewsletterConfirm')
                ->where('status', 'confirmed'));

        $this->assertDatabaseHas('newsletter_subscribers', ['token' => str_repeat('e', 64), 'status' => 'confirmed']);
    }

    public function test_confirm_already_confirmed_renders_already_status(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create(['token' => str_repeat('f', 64)]);

        $this->get('/newsletter/confirm/'.str_repeat('f', 64))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/NewsletterConfirm')
                ->where('status', 'already'));
    }

    public function test_confirm_unknown_token_renders_invalid_status(): void
    {
        $this->get('/newsletter/confirm/unknown-token')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/NewsletterConfirm')
                ->where('status', 'invalid'));
    }

    public function test_unsubscribe_valid_token_marks_unsubscribed(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create(['token' => str_repeat('g', 64)]);

        $this->get('/newsletter/unsubscribe/'.str_repeat('g', 64))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/NewsletterUnsubscribe')
                ->where('status', 'done'));

        $this->assertDatabaseHas('newsletter_subscribers', ['token' => str_repeat('g', 64), 'status' => 'unsubscribed']);
    }

    public function test_unsubscribe_unknown_token_renders_invalid(): void
    {
        $this->get('/newsletter/unsubscribe/nope')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/NewsletterUnsubscribe')
                ->where('status', 'invalid'));
    }

    public function test_resubscribe_flips_back_to_pending_and_sends_mail(): void
    {
        Mail::fake();
        NewsletterSubscriber::factory()->unsubscribed()->create(['token' => str_repeat('h', 64)]);

        $this->post('/newsletter/resubscribe', ['token' => str_repeat('h', 64)])
            ->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', ['token' => str_repeat('h', 64), 'status' => 'pending']);
        Mail::assertQueued(NewsletterConfirmationMail::class);
    }
}
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `php artisan test --filter="NewsletterSubscribeTest|NewsletterConfirmTest"`
Expected: FAIL — no `/newsletter/*` routes (404/405).

- [ ] **Step 4: Write the form request**

Create `app/Http/Requests/SubscribeNewsletterRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a public newsletter subscribe. The captcha rule is a no-op when no
 * reCAPTCHA keys are configured (dev/tests). The `website` honeypot is validated
 * loosely here; the controller treats a filled honeypot as a silent bot accept.
 */
class SubscribeNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'g-recaptcha-response' => ['nullable', 'captcha'],
            'email' => ['required', 'email'],
            'website' => ['nullable', 'string'], // honeypot; bots fill it
        ];
    }
}
```

- [ ] **Step 5: Write the controller**

Create `app/Http/Controllers/NewsletterController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeNewsletterRequest;
use App\Services\Newsletter\NewsletterSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public double opt-in endpoints. Thin: all lifecycle logic lives in
 * NewsletterSubscriptionService. Responses never reveal whether an email was
 * already known (no enumeration).
 */
class NewsletterController extends Controller
{
    public function __construct(private NewsletterSubscriptionService $service) {}

    public function subscribe(SubscribeNewsletterRequest $request): RedirectResponse
    {
        // Honeypot: a filled `website` field means a bot. Accept silently — no
        // row, no mail, same generic outcome so bots can't tell.
        if (! $request->filled('website')) {
            $this->service->subscribe(
                $request->validated('email'),
                'footer',
                get_current_lang(),
                $request->user()?->id,
            );
        }

        return back()
            ->with('newsletter_status', 'submitted')
            ->with('success', __('default/newsletter.check_inbox'));
    }

    public function confirm(string $token): Response
    {
        $sub = $this->service->confirm($token);

        $status = match (true) {
            $sub === null => 'invalid',
            $sub->confirmed_at !== null && ! $sub->wasChanged('status') && $sub->getOriginal('status') === 'confirmed' => 'already',
            default => 'confirmed',
        };

        return Inertia::render('public/NewsletterConfirm', ['status' => $status]);
    }

    public function unsubscribe(string $token): Response
    {
        $sub = $this->service->unsubscribe($token);

        return Inertia::render('public/NewsletterUnsubscribe', [
            'status' => $sub === null ? 'invalid' : 'done',
            'token' => $sub?->token,
        ]);
    }

    public function resubscribe(Request $request): RedirectResponse
    {
        $token = (string) $request->input('token');
        $this->service->resubscribe($token);

        return back()->with('success', __('default/newsletter.unsub_resubmitted'));
    }
}
```

Note on the confirm `already` detection: `confirm()` only flips a *pending* row, so an already-confirmed row is returned unchanged. Detect "already" by checking the row was confirmed before this call. Simplify by having the service tell us — but to keep the service return minimal, the controller derives it: an already-confirmed subscriber has `confirmed_at` set and its `status` did not change during this request. The `wasChanged`/`getOriginal` combination above is fragile; replace it with the clearer form below.

Replace the `confirm()` method body's `$status` computation with:

```php
        $status = 'invalid';

        if ($sub !== null) {
            // confirm() flips pending->confirmed. If it was already confirmed
            // before this call, confirmed_at was set on a prior request and the
            // status did not transition now.
            $status = $sub->wasChanged('status') ? 'confirmed' : 'already';
        }
```

Because `save()` inside `confirm()` runs only for pending rows, `wasChanged('status')` is true exactly when this call performed the flip → `confirmed`; otherwise the row was already confirmed → `already`. (`wasChanged` reflects the last save on this instance within the request.)

- [ ] **Step 6: Register the routes (before the front catch-all)**

In `routes/web.php`, add this block immediately after the health routes (around line 46, before the Inertia smoke route). It is deliberately OUTSIDE the `site_lockdown` group so confirm/unsubscribe links keep working even during a site lockdown:

```php
/*
|--------------------------------------------------------------------------
| Newsletter (Phase 1): public double opt-in
|--------------------------------------------------------------------------
| Registered before the front catch-all ({locale?}/{slug?}) so /newsletter/*
| is not swallowed. Outside site_lockdown so unsubscribe links always work.
*/
Route::prefix('newsletter')->group(function () {
    Route::post('/subscribe', 'NewsletterController@subscribe')
        ->middleware('throttle:5,1')->name('newsletter.subscribe');
    Route::get('/confirm/{token}', 'NewsletterController@confirm')->name('newsletter.confirm');
    Route::get('/unsubscribe/{token}', 'NewsletterController@unsubscribe')->name('newsletter.unsubscribe');
    Route::post('/resubscribe', 'NewsletterController@resubscribe')
        ->middleware('throttle:5,1')->name('newsletter.resubscribe');
});
```

Note: routes/web.php uses string controller references with an implicit `App\Http\Controllers` namespace (see existing `'SeoController@sitemap'`). `NewsletterController` is in that namespace, so `'NewsletterController@subscribe'` resolves correctly.

- [ ] **Step 7: Add the `newsletter_status` flash**

In `app/Http/Middleware/HandleInertiaRequests.php`, extend the `flash` array (currently lines 81-85):

```php
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'newsletter_status' => fn () => $request->session()->get('newsletter_status'),
            ],
```

- [ ] **Step 8: Run tests to verify they pass**

Run: `php artisan test --filter="NewsletterSubscribeTest|NewsletterConfirmTest"`
Expected: PASS (all subscribe + confirm/unsubscribe tests).

Note: the Inertia render tests need the page components to exist as `.tsx` files for `assertInertia` — they do NOT, since `assertInertia` inspects the server response, not the built bundle. Confirm this passes without the React files. (Inertia's `AssertableInertia` only checks the JSON payload the server emits.)

- [ ] **Step 9: Style + layering + commit**

Run: `vendor/bin/pint app/Http/Controllers/NewsletterController.php app/Http/Requests/SubscribeNewsletterRequest.php app/Http/Middleware/HandleInertiaRequests.php`
Run: `php -d memory_limit=1G artisan test tests/Arch/LayeringTest.php`
Expected: PASS.

```bash
git add app/Http/Controllers/NewsletterController.php app/Http/Requests/SubscribeNewsletterRequest.php app/Http/Middleware/HandleInertiaRequests.php routes/web.php tests/Feature/Newsletter/
git commit -m "Newsletter: public subscribe/confirm/unsubscribe/resubscribe endpoints"
```

---

## Task 5: Public React — confirm & unsubscribe pages + footer subscribe widget

**Files:**
- Create: `resources/js/pages/public/NewsletterConfirm.tsx`
- Create: `resources/js/pages/public/NewsletterUnsubscribe.tsx`
- Create: `resources/js/components/public/NewsletterSubscribe.tsx`
- Create: `resources/js/components/public/NewsletterSubscribe.test.tsx`
- Modify: `resources/js/layouts/PublicLayout.tsx` (mount the widget in the footer)
- Modify: `resources/js/lib/types.ts` (add `newsletter_status` to flash)

**Interfaces:**
- Consumes: shared `shell` prop (PublicLayout), `default/newsletter.*` i18n keys, flash `newsletter_status`, routes `/newsletter/subscribe`, `/newsletter/resubscribe`.
- Produces: `NewsletterSubscribe` component (no props; reads flash + posts). The two pages default-export a component using `PublicLayout`.

- [ ] **Step 1: Add `newsletter_status` to the shared flash type**

In `resources/js/lib/types.ts`, extend the `flash` block (lines 31-35):

```typescript
    flash: {
        status: string | null;
        success: string | null;
        error: string | null;
        newsletter_status: string | null;
    };
```

- [ ] **Step 2: Write the failing widget test**

Create `resources/js/components/public/NewsletterSubscribe.test.tsx`:

```tsx
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { NewsletterSubscribe } from './NewsletterSubscribe';

const post = vi.hoisted(() => vi.fn());
let flash: Record<string, unknown> = {};

vi.mock('@inertiajs/react', () => ({
  usePage: () => ({ props: { flash } }),
  useForm: () => ({
    data: { email: '', website: '' },
    setData: vi.fn(),
    post,
    processing: false,
    reset: vi.fn(),
    errors: {},
  }),
}));

vi.mock('react-i18next', () => ({
  useTranslation: () => ({ t: (k: string) => k }),
}));

describe('NewsletterSubscribe', () => {
  beforeEach(() => {
    post.mockClear();
    flash = {};
  });

  it('renders the form and posts to the subscribe route on submit', () => {
    render(<NewsletterSubscribe />);
    const form = screen.getByTestId('newsletter-form');
    fireEvent.submit(form);
    expect(post).toHaveBeenCalledWith('/newsletter/subscribe', expect.any(Object));
  });

  it('shows the thank-you line when flash.newsletter_status is submitted', () => {
    flash = { newsletter_status: 'submitted' };
    render(<NewsletterSubscribe />);
    expect(screen.getByTestId('newsletter-submitted')).toBeInTheDocument();
    expect(screen.queryByTestId('newsletter-form')).not.toBeInTheDocument();
  });
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `npx vitest run resources/js/components/public/NewsletterSubscribe.test.tsx`
Expected: FAIL — cannot resolve `./NewsletterSubscribe`.

- [ ] **Step 4: Write the widget**

Create `resources/js/components/public/NewsletterSubscribe.tsx`:

```tsx
import { useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { FormEvent } from 'react';

interface Flash {
  newsletter_status?: string | null;
}

/**
 * Footer newsletter subscribe widget. Posts an email (plus a hidden honeypot)
 * to /newsletter/subscribe and swaps itself for a thank-you line once the server
 * flashes newsletter_status=submitted. Styled with public tokens (.btn-accent).
 */
export function NewsletterSubscribe() {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const { flash } = usePage<{ flash: Flash }>().props;

  const form = useForm({ email: '', website: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/newsletter/subscribe', {
      preserveScroll: true,
      onSuccess: () => form.reset('email'),
    });
  };

  const submitted = flash?.newsletter_status === 'submitted';

  return (
    <div className="max-w-sm" data-testid="newsletter-widget">
      <h3 className="text-[13px] font-semibold uppercase tracking-wide text-[var(--text)]">
        {tr('default/newsletter.widget_heading', 'Subscribe to the newsletter')}
      </h3>
      <p className="mt-1 text-sm text-[var(--text-subtle)]">
        {tr('default/newsletter.widget_subtitle', 'Occasional updates. No spam. Unsubscribe anytime.')}
      </p>

      {submitted ? (
        <p className="mt-4 text-sm font-medium text-[var(--accent)]" data-testid="newsletter-submitted">
          {tr('default/newsletter.widget_submitted', 'Thanks — check your inbox to confirm your subscription.')}
        </p>
      ) : (
        <form onSubmit={submit} className="mt-4 flex gap-2" data-testid="newsletter-form">
          {/* Honeypot: hidden from users, bots fill it. */}
          <input
            type="text"
            name="website"
            tabIndex={-1}
            autoComplete="off"
            aria-hidden="true"
            className="hidden"
            value={form.data.website}
            onChange={(e) => form.setData('website', e.target.value)}
          />
          <input
            type="email"
            name="email"
            required
            placeholder={tr('default/newsletter.widget_placeholder', 'you@example.com')}
            aria-label={tr('default/newsletter.widget_heading', 'Subscribe to the newsletter')}
            value={form.data.email}
            onChange={(e) => form.setData('email', e.target.value)}
            data-testid="newsletter-email"
            className="h-10 flex-1 rounded-md border border-[var(--border)] bg-[var(--bg)] px-3 text-sm text-[var(--text)] outline-none focus:border-[var(--accent)] focus:ring-2 focus:ring-[var(--ring)]"
          />
          <button
            type="submit"
            disabled={form.processing}
            className="btn-accent h-10 shrink-0 px-4 text-sm font-medium"
            data-testid="newsletter-submit"
          >
            {tr('default/newsletter.widget_button', 'Subscribe')}
          </button>
        </form>
      )}
      {form.errors.email && (
        <p className="mt-2 text-xs text-[var(--danger,#dc2626)]" data-testid="newsletter-error">
          {form.errors.email}
        </p>
      )}
    </div>
  );
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `npx vitest run resources/js/components/public/NewsletterSubscribe.test.tsx`
Expected: PASS (2 tests).

- [ ] **Step 6: Mount the widget in the footer**

In `resources/js/layouts/PublicLayout.tsx`: add the import at the top (after the existing imports):

```tsx
import { NewsletterSubscribe } from '@/components/public/NewsletterSubscribe';
```

Then, inside the `<footer>`'s inner container, add the widget as a new row above the existing wordmark/socials flex row. Replace the opening of the footer content block:

```tsx
                <div className="mx-auto max-w-[76rem] px-5 py-14 sm:px-8">
                    <div className="mb-10 border-b border-[var(--border)] pb-10">
                        <NewsletterSubscribe />
                    </div>
                    <div className="flex flex-col gap-8 sm:flex-row sm:items-start sm:justify-between">
```

(The rest of the footer — wordmark, copyright, socials — is unchanged; this only wraps a new bordered section above it.)

- [ ] **Step 7: Write the confirm page**

Create `resources/js/pages/public/NewsletterConfirm.tsx`:

```tsx
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';

type ConfirmStatus = 'confirmed' | 'already' | 'invalid';

interface Props {
  shell: Shell;
  status: ConfirmStatus;
}

const COPY: Record<ConfirmStatus, { title: string; body: string }> = {
  confirmed: { title: 'default/newsletter.confirm_confirmed_title', body: 'default/newsletter.confirm_confirmed_body' },
  already: { title: 'default/newsletter.confirm_already_title', body: 'default/newsletter.confirm_already_body' },
  invalid: { title: 'default/newsletter.confirm_invalid_title', body: 'default/newsletter.confirm_invalid_body' },
};

export default function NewsletterConfirm({ shell, status }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const copy = COPY[status] ?? COPY.invalid;

  return (
    <PublicLayout shell={shell}>
      <Head title={tr(copy.title, 'Newsletter')} />
      <section className="mx-auto max-w-[42rem] px-5 py-24 text-center sm:px-8" data-testid="newsletter-confirm">
        <h1 className="text-3xl font-semibold tracking-tight text-[var(--text)]" data-testid="confirm-title">
          {tr(copy.title, 'Newsletter')}
        </h1>
        <p className="mt-4 text-[var(--text-subtle)]">{tr(copy.body, '')}</p>
        <a href={shell.homeUrl} className="btn-accent mt-8 inline-flex h-10 items-center px-5 text-sm font-medium">
          {tr('default/header.home', 'Home')}
        </a>
      </section>
    </PublicLayout>
  );
}
```

- [ ] **Step 8: Write the unsubscribe page**

Create `resources/js/pages/public/NewsletterUnsubscribe.tsx`:

```tsx
import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PublicLayout } from '@/layouts/PublicLayout';
import type { Shell } from '@/layouts/PublicLayout';
import type { FormEvent } from 'react';

type UnsubStatus = 'done' | 'invalid';

interface Props {
  shell: Shell;
  status: UnsubStatus;
  token: string | null;
}

export default function NewsletterUnsubscribe({ shell, status, token }: Props) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const form = useForm({ token: token ?? '' });

  const resubscribe = (e: FormEvent) => {
    e.preventDefault();
    form.post('/newsletter/resubscribe', { preserveScroll: true });
  };

  const title = status === 'done' ? 'default/newsletter.unsub_done_title' : 'default/newsletter.unsub_invalid_title';
  const body = status === 'done' ? 'default/newsletter.unsub_done_body' : 'default/newsletter.unsub_invalid_body';

  return (
    <PublicLayout shell={shell}>
      <Head title={tr(title, 'Newsletter')} />
      <section className="mx-auto max-w-[42rem] px-5 py-24 text-center sm:px-8" data-testid="newsletter-unsubscribe">
        <h1 className="text-3xl font-semibold tracking-tight text-[var(--text)]" data-testid="unsub-title">
          {tr(title, 'Newsletter')}
        </h1>
        <p className="mt-4 text-[var(--text-subtle)]">{tr(body, '')}</p>

        {status === 'done' && token && (
          <form onSubmit={resubscribe} className="mt-8">
            <button type="submit" disabled={form.processing} className="btn-accent inline-flex h-10 items-center px-5 text-sm font-medium" data-testid="resubscribe">
              {tr('default/newsletter.unsub_resubscribe_button', 'Re-subscribe')}
            </button>
          </form>
        )}
      </section>
    </PublicLayout>
  );
}
```

- [ ] **Step 9: Run the widget test + typecheck + build**

Run: `npx vitest run resources/js/components/public/NewsletterSubscribe.test.tsx`
Expected: PASS.
Run: `npm run build`
Expected: build succeeds (all three new `.tsx` files compile; `public/NewsletterConfirm` / `public/NewsletterUnsubscribe` resolve via the pages glob).

- [ ] **Step 10: Commit**

```bash
git add resources/js/components/public/ resources/js/pages/public/NewsletterConfirm.tsx resources/js/pages/public/NewsletterUnsubscribe.tsx resources/js/layouts/PublicLayout.tsx resources/js/lib/types.ts
git commit -m "Newsletter: footer subscribe widget + confirm/unsubscribe pages"
```

---

## Task 6: `manage_newsletter` permission (middleware, alias, policy, seeder, ability, data migration)

**Files:**
- Create: `app/Http/Middleware/ManageNewsletter.php`
- Create: `database/migrations/2026_08_07_001000_add_manage_newsletter_permission.php`
- Modify: `app/Http/Kernel.php` (import + alias)
- Modify: `app/Policies/UserPolicy.php` (`manage_newsletter()` method)
- Modify: `database/seeds/UserPermissionsSeeder.php` (row)
- Modify: `app/Http/Middleware/HandleInertiaRequests.php` (`ABILITIES`)
- Modify: `resources/js/lib/types.ts` (`Ability` union)
- Test: create `tests/Feature/Newsletter/AdminNewsletterTest.php` (guard + backfill portion; grows in Task 7)

**Interfaces:**
- Consumes: `App\Http\Models\UserRoles`, existing `UserPermissionsSeeder`, `DatabaseSeeder`.
- Produces: middleware alias `manage_newsletter`; `UserPolicy::manage_newsletter(): bool`; shared ability `auth.can.manage_newsletter`.

- [ ] **Step 1: Write the failing guard + backfill test**

Create `tests/Feature/Newsletter/AdminNewsletterTest.php`:

```php
<?php

namespace Tests\Feature\Newsletter;

use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminNewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function adminUser(): User
    {
        // The seeded Administrator role (id 1) has every permission.
        return User::factory()->create(['role_id' => 1]);
    }

    public function test_panel_user_without_manage_newsletter_is_denied(): void
    {
        $role = UserRoles::create([
            'name' => 'PanelNoNewsletter',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_newsletter' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/agentic-cms-laravel-admin/newsletter')->assertStatus(401);
    }

    public function test_seeded_administrator_has_manage_newsletter(): void
    {
        $admin = $this->adminUser();

        $this->assertTrue($admin->can('manage_newsletter', UserRoles::class));
    }

    public function test_permission_row_exists(): void
    {
        $this->assertDatabaseHas('user_permissions', ['name' => 'manage_newsletter']);
    }

    public function test_migration_backfills_full_access_role(): void
    {
        // A full-access role created WITHOUT the flag (simulating a live install
        // before this migration) is backfilled to 1 by re-running up().
        $role = UserRoles::create([
            'name' => 'LegacyAdmin',
            'permissions' => json_encode([
                'see_admin_panel' => 1, 'manage_users' => 1, 'manage_posts' => 1,
            ]),
        ]);

        (new \AddManageNewsletterPermission)->up();

        $perms = json_decode(DB::table('user_roles')->find($role->id)->permissions, true);
        $this->assertSame(1, $perms['manage_newsletter']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminNewsletterTest`
Expected: FAIL — route 404 / permission absent / class `AddManageNewsletterPermission` not found.

- [ ] **Step 3: Write the middleware**

Create `app/Http/Middleware/ManageNewsletter.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Http\Models\UserRoles;
use Closure;
use Illuminate\Support\Facades\Auth;

class ManageNewsletter
{
    /**
     * Gate the admin newsletter routes behind the manage_newsletter capability.
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user()->cannot('manage_newsletter', UserRoles::class)) {
            abort(401);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Alias it in the Kernel**

In `app/Http/Kernel.php`: add the import next to the other `Manage*` imports:

```php
use App\Http\Middleware\ManageNewsletter;
```

And in the `$routeMiddleware` (aliases) array, next to `'manage_media'`:

```php
        'manage_newsletter' => ManageNewsletter::class,
```

- [ ] **Step 5: Add the policy method**

In `app/Policies/UserPolicy.php`, next to `manage_media()`:

```php
    public function manage_newsletter(): bool
    {
        return $this->has('manage_newsletter');
    }
```

- [ ] **Step 6: Add the seeder row**

In `database/seeds/UserPermissionsSeeder.php`, add to the inserted rows (next to `['name' => 'manage_media']`):

```php
                ['name' => 'manage_newsletter'],
```

- [ ] **Step 7: Add to shared abilities + the TS union**

In `app/Http/Middleware/HandleInertiaRequests.php`, add to the `ABILITIES` list (next to `'manage_media'`):

```php
        'manage_newsletter',
```

In `resources/js/lib/types.ts`, extend the `Ability` union (add before the closing of the type):

```typescript
    | 'manage_newsletter';
```

(Insert it as the new last member; move the trailing `;` accordingly. Note `manage_media` is intentionally absent from this union today — leave that as-is; only add `manage_newsletter`, which the Newsletter nav item in Task 8 requires.)

- [ ] **Step 8: Write the data migration**

Create `database/migrations/2026_08_07_001000_add_manage_newsletter_permission.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduce the manage_newsletter permission and backfill existing roles, so a
 * live install's full-access role (Administrator) keeps access once the admin
 * newsletter routes go behind manage_newsletter. A role holding every existing
 * flag is treated as full-access and gets manage_newsletter = 1; others get 0.
 * Idempotent: skips roles that already carry the key.
 */
class AddManageNewsletterPermission extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->insertOrIgnore([['name' => 'manage_newsletter']]);
        }

        if (! Schema::hasTable('user_roles')) {
            return;
        }

        foreach (DB::table('user_roles')->get() as $role) {
            $perms = json_decode($role->permissions ?? '', true) ?: [];

            if (array_key_exists('manage_newsletter', $perms)) {
                continue;
            }

            $fullAccess = count($perms) > 0 && ! in_array(0, array_values($perms), true);
            $perms['manage_newsletter'] = $fullAccess ? 1 : 0;

            DB::table('user_roles')->where('id', $role->id)
                ->update(['permissions' => json_encode($perms)]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_roles')) {
            foreach (DB::table('user_roles')->get() as $role) {
                $perms = json_decode($role->permissions ?? '', true) ?: [];
                unset($perms['manage_newsletter']);
                DB::table('user_roles')->where('id', $role->id)
                    ->update(['permissions' => json_encode($perms)]);
            }
        }

        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->where('name', 'manage_newsletter')->delete();
        }
    }
}
```

Note: the guard test hits `/agentic-cms-laravel-admin/newsletter`, which does not exist yet (added in Task 7). Until then `test_panel_user_without_manage_newsletter_is_denied` will 404, not 401. Mark that one test skipped for now with `$this->markTestSkipped('route added in Task 7');` at its top, OR implement Task 7 before running the full class. The other three tests (backfill, seeded admin, permission row) pass now.

- [ ] **Step 9: Run the passing subset**

Run: `php artisan test --filter=AdminNewsletterTest`
Expected: 3 pass, 1 skipped (guard, pending Task 7 route).

- [ ] **Step 10: Style + commit**

Run: `vendor/bin/pint app/Http/Middleware/ManageNewsletter.php app/Policies/UserPolicy.php app/Http/Kernel.php database/seeds/UserPermissionsSeeder.php app/Http/Middleware/HandleInertiaRequests.php database/migrations/2026_08_07_001000_add_manage_newsletter_permission.php`

```bash
git add app/Http/Middleware/ManageNewsletter.php app/Policies/UserPolicy.php app/Http/Kernel.php database/seeds/UserPermissionsSeeder.php app/Http/Middleware/HandleInertiaRequests.php resources/js/lib/types.ts database/migrations/2026_08_07_001000_add_manage_newsletter_permission.php tests/Feature/Newsletter/AdminNewsletterTest.php
git commit -m "Newsletter: manage_newsletter permission + Administrator backfill migration"
```

---

## Task 7: Admin service, form request, controller, routes

**Files:**
- Create: `app/Services/CPanel/CPanelNewsletterService.php`
- Create: `app/Http/Requests/StoreNewsletterSubscriberRequest.php`
- Create: `app/Http/Controllers/CPanel/CPanelNewsletterController.php`
- Modify: `routes/web.php` (admin newsletter group)
- Modify: `tests/Feature/Newsletter/AdminNewsletterTest.php` (unskip guard, add CRUD/export tests)

**Interfaces:**
- Consumes: `NewsletterSubscriberRepository` (Task 1), `manage_newsletter` middleware (Task 6).
- Produces:
  - `CPanelNewsletterService`: `list(?string $status, ?string $search, int $perPage): LengthAwarePaginator`, `add(string $email): NewsletterSubscriber`, `delete(int $id): void`, `exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse`.
  - Routes: `cpanel_newsletter_list` (GET `/`), `cpanel_newsletter_store` (POST `/`), `cpanel_newsletter_destroy` (DELETE `/{id}`), `cpanel_newsletter_export` (GET `/export`).

- [ ] **Step 1: Unskip the guard test + add CRUD/export tests**

In `tests/Feature/Newsletter/AdminNewsletterTest.php`: remove the `markTestSkipped` line from `test_panel_user_without_manage_newsletter_is_denied`, and append (add `use App\Http\Models\NewsletterSubscriber;` and `use Inertia\Testing\AssertableInertia;`):

```php
    public function test_index_lists_subscribers_with_props(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'one@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'two@example.com', 'status' => 'pending']);

        $this->actingAs($this->adminUser())
            ->get('/agentic-cms-laravel-admin/newsletter')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('cpanel/newsletter/List')
                ->has('subscribers.data', 2));
    }

    public function test_index_filters_by_status_and_searches(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'keep@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'drop@example.com', 'status' => 'pending']);

        $this->actingAs($this->adminUser())
            ->get('/agentic-cms-laravel-admin/newsletter?status=confirmed')
            ->assertInertia(fn (AssertableInertia $p) => $p->has('subscribers.data', 1)->where('filters.status', 'confirmed'));

        $this->actingAs($this->adminUser())
            ->get('/agentic-cms-laravel-admin/newsletter?search=keep')
            ->assertInertia(fn (AssertableInertia $p) => $p->has('subscribers.data', 1));
    }

    public function test_store_adds_a_confirmed_admin_subscriber(): void
    {
        $this->actingAs($this->adminUser())
            ->post('/agentic-cms-laravel-admin/newsletter', ['email' => 'manual@example.com'])
            ->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'manual@example.com', 'status' => 'confirmed', 'source' => 'admin',
        ]);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        NewsletterSubscriber::factory()->create(['email' => 'exists@example.com']);

        $this->actingAs($this->adminUser())
            ->post('/agentic-cms-laravel-admin/newsletter', ['email' => 'exists@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_destroy_removes_a_subscriber(): void
    {
        $sub = NewsletterSubscriber::factory()->create();

        $this->actingAs($this->adminUser())
            ->delete("/agentic-cms-laravel-admin/newsletter/{$sub->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('newsletter_subscribers', ['id' => $sub->id]);
    }

    public function test_export_streams_confirmed_csv(): void
    {
        NewsletterSubscriber::factory()->confirmed()->create(['email' => 'export@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'skip@example.com', 'status' => 'pending']);

        $response = $this->actingAs($this->adminUser())
            ->get('/agentic-cms-laravel-admin/newsletter/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('export@example.com', $csv);
        $this->assertStringNotContainsString('skip@example.com', $csv);
    }

    public function test_every_admin_route_is_forbidden_without_permission(): void
    {
        $role = UserRoles::create([
            'name' => 'NoNews',
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_newsletter' => 0]),
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $sub = NewsletterSubscriber::factory()->create();

        $this->actingAs($user)->post('/agentic-cms-laravel-admin/newsletter', ['email' => 'x@example.com'])->assertStatus(401);
        $this->actingAs($user)->delete("/agentic-cms-laravel-admin/newsletter/{$sub->id}")->assertStatus(401);
        $this->actingAs($user)->get('/agentic-cms-laravel-admin/newsletter/export')->assertStatus(401);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AdminNewsletterTest`
Expected: FAIL — admin routes 404.

- [ ] **Step 3: Write the admin service**

Create `app/Services/CPanel/CPanelNewsletterService.php`:

```php
<?php

namespace App\Services\CPanel;

use App\Http\Models\NewsletterSubscriber;
use App\Repositories\NewsletterSubscriberRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin-side newsletter management: listing/filtering, manual add (admin vouches,
 * so the row is created already confirmed), delete, and a CSV export of confirmed
 * subscribers. All persistence goes through the repository.
 */
class CPanelNewsletterService
{
    public function __construct(private NewsletterSubscriberRepository $repo) {}

    public function list(?string $status, ?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->repo->paginateFiltered($status, $search, $perPage);
    }

    /**
     * Manually add a subscriber. Admin vouches for consent, so no opt-in email:
     * the row is created already confirmed, source=admin.
     */
    public function add(string $email): NewsletterSubscriber
    {
        return $this->repo->create([
            'email' => mb_strtolower(trim($email)),
            'status' => NewsletterSubscriber::STATUS_CONFIRMED,
            'token' => bin2hex(random_bytes(32)),
            'source' => 'admin',
            'confirmed_at' => now(),
        ]);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    /**
     * Stream confirmed subscribers as CSV (email,locale,source,confirmed_at).
     */
    public function exportCsv(): StreamedResponse
    {
        $rows = $this->repo->confirmedEmails();
        $filename = 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'locale', 'source', 'confirmed_at']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->email,
                    $row->locale,
                    $row->source,
                    $row->confirmed_at?->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
```

Note: `$this->repo->delete($id)` uses `BaseRepository::delete`, which calls `Model::destroy` (hard delete — the model has no soft-deletes). Correct for this table.

- [ ] **Step 4: Write the admin form request**

Create `app/Http/Requests/StoreNewsletterSubscriberRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a manual (admin) newsletter subscriber add. Email must be unique so
 * the admin never creates a duplicate of an existing subscriber.
 */
class StoreNewsletterSubscriberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:newsletter_subscribers,email'],
        ];
    }
}
```

- [ ] **Step 5: Write the admin controller**

Create `app/Http/Controllers/CPanel/CPanelNewsletterController.php`:

```php
<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\StoreNewsletterSubscriberRequest;
use App\Services\CPanel\CPanelNewsletterService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin newsletter subscriber management. Gated by manage_newsletter (route
 * group middleware). Thin: shaping + delegation to CPanelNewsletterService.
 */
class CPanelNewsletterController extends CPanelBaseController
{
    public function __construct(private CPanelNewsletterService $newsletter)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $subscribers = $this->newsletter->list(
            is_string($status) ? $status : null,
            is_string($search) ? $search : null,
            $this->per_page,
        );

        $subscribers->getCollection()->transform(fn ($s) => [
            'id' => $s->id,
            'email' => $s->email,
            'status' => $s->status,
            'locale' => $s->locale,
            'source' => $s->source,
            'subscribed' => $s->created_at?->format('d.m.Y'),
        ]);

        return Inertia::render('cpanel/newsletter/List', [
            'subscribers' => $subscribers,
            'filters' => [
                'status' => is_string($status) ? $status : null,
                'search' => is_string($search) ? $search : null,
            ],
        ]);
    }

    public function store(StoreNewsletterSubscriberRequest $request)
    {
        $this->newsletter->add($request->validated('email'));

        return back()->with('success', __('cpanel/newsletter.added'));
    }

    public function destroy(int $id)
    {
        $this->newsletter->delete($id);

        return back()->with('success', __('cpanel/newsletter.deleted'));
    }

    public function export(): StreamedResponse
    {
        return $this->newsletter->exportCsv();
    }
}
```

- [ ] **Step 6: Register the admin routes**

In `routes/web.php`, add this group inside the admin `Route::prefix('agentic-cms-laravel-admin')...->group(...)` block (e.g. after the `media` group, before the closing `});` at line 230):

```php
    Route::prefix('newsletter')->middleware('manage_newsletter')->group(function () {
        Route::get('/', 'CPanelNewsletterController@index')->name('cpanel_newsletter_list');
        Route::get('/export', 'CPanelNewsletterController@export')->name('cpanel_newsletter_export');
        Route::post('/', 'CPanelNewsletterController@store')->name('cpanel_newsletter_store');
        Route::delete('/{id}', 'CPanelNewsletterController@destroy')->name('cpanel_newsletter_destroy')->where('id', '[0-9]+');
    });
```

(`/export` is registered before `/{id}` implicitly by being a distinct static path; order is safe because `{id}` is constrained to digits.)

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --filter=AdminNewsletterTest`
Expected: PASS (all guard + CRUD + export + backfill tests).

- [ ] **Step 8: Layering + style + commit**

Run: `php -d memory_limit=1G artisan test tests/Arch/LayeringTest.php`
Run: `vendor/bin/pint app/Services/CPanel/CPanelNewsletterService.php app/Http/Requests/StoreNewsletterSubscriberRequest.php app/Http/Controllers/CPanel/CPanelNewsletterController.php`

```bash
git add app/Services/CPanel/CPanelNewsletterService.php app/Http/Requests/StoreNewsletterSubscriberRequest.php app/Http/Controllers/CPanel/CPanelNewsletterController.php routes/web.php tests/Feature/Newsletter/AdminNewsletterTest.php
git commit -m "Newsletter: admin list/add/delete/CSV-export behind manage_newsletter"
```

---

## Task 8: Admin React List page + Sidebar nav + English admin strings

**Files:**
- Create: `resources/js/pages/cpanel/newsletter/List.tsx`
- Create: `resources/js/pages/cpanel/newsletter/List.test.tsx`
- Create: `resources/lang/en/cpanel/newsletter.php`
- Modify: `resources/js/lib/admin-nav.ts` (Newsletter nav item)
- Modify: `resources/lang/en/cpanel/menu.php` (`'newsletter'` label)

**Interfaces:**
- Consumes: `subscribers` (Paginator of `{id,email,status,locale,source,subscribed}`) + `filters` ({status,search}) props (Task 7), `cpanel/newsletter.*` i18n, routes `cpanel_newsletter_*`.
- Produces: default-exported `List` page with `.layout = AdminLayout`.

- [ ] **Step 1: Write the English admin strings**

Create `resources/lang/en/cpanel/newsletter.php`:

```php
<?php

/**
 * Admin newsletter subscriber list (Phase 1). Consumed by
 * resources/js/pages/cpanel/newsletter/List.tsx via the flattened
 * cpanel/newsletter.* i18n keys.
 */

return [
    'list_headline' => 'Newsletter',
    'table_email' => 'Email',
    'table_status' => 'Status',
    'table_locale' => 'Locale',
    'table_source' => 'Source',
    'table_subscribed' => 'Subscribed',
    'status_confirmed' => 'Confirmed',
    'status_pending' => 'Pending',
    'status_unsubscribed' => 'Unsubscribed',
    'filter_all' => 'All',
    'search_placeholder' => 'Search email…',
    'add_placeholder' => 'new@example.com',
    'add_button' => 'Add subscriber',
    'delete' => 'Delete',
    'delete_confirm' => 'Delete this subscriber?',
    'export_button' => 'Export CSV',
    'not_found' => 'No subscribers yet',
    'added' => 'Subscriber added.',
    'deleted' => 'Subscriber deleted.',
];
```

- [ ] **Step 2: Add the menu label + nav item**

In `resources/lang/en/cpanel/menu.php`, add before the closing `];`:

```php
    'newsletter' => 'Newsletter',
```

In `resources/js/lib/admin-nav.ts`, add to the Settings group's `items` array (after the Security item, before Users, or at the end):

```typescript
    { key: 'cpanel/menu.newsletter', fallback: 'Newsletter', href: `${A}/newsletter`, component: 'cpanel/newsletter', ability: 'manage_newsletter' },
```

- [ ] **Step 3: Write the failing List test**

Create `resources/js/pages/cpanel/newsletter/List.test.tsx`:

```tsx
import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import List from './List';

vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children }: { children: React.ReactNode }) => <a>{children}</a>,
  router: { delete: vi.fn(), post: vi.fn() },
  useForm: () => ({ data: { email: '' }, setData: vi.fn(), post: vi.fn(), processing: false, reset: vi.fn(), errors: {} }),
}));

vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

const paginator = (rows: unknown[]) => ({ data: rows, current_page: 1, last_page: 1, total: rows.length });

describe('cpanel newsletter List', () => {
  it('renders a row per subscriber with the right status pill', () => {
    render(
      <List
        subscribers={paginator([
          { id: 1, email: 'a@example.com', status: 'confirmed', locale: 'en', source: 'footer', subscribed: '01.08.2026' },
          { id: 2, email: 'b@example.com', status: 'pending', locale: 'de', source: 'admin', subscribed: '02.08.2026' },
        ])}
        filters={{ status: null, search: null }}
      />,
    );

    expect(screen.getByText('a@example.com')).toBeInTheDocument();
    expect(screen.getByText('b@example.com')).toBeInTheDocument();
    expect(screen.getByText('cpanel/newsletter.status_confirmed')).toBeInTheDocument();
    expect(screen.getByText('cpanel/newsletter.status_pending')).toBeInTheDocument();
  });

  it('highlights the active status filter chip', () => {
    render(
      <List subscribers={paginator([])} filters={{ status: 'confirmed', search: null }} />,
    );
    expect(screen.getByTestId('filter-confirmed')).toHaveattribute?.('aria-current', 'true');
  });
});
```

Note: `toHaveattribute` is a typo guard the RTL matcher will reject; use `toHaveAttribute`. Correct the second test's assertion to:

```tsx
    expect(screen.getByTestId('filter-confirmed')).toHaveAttribute('aria-current', 'true');
```

- [ ] **Step 4: Run test to verify it fails**

Run: `npx vitest run resources/js/pages/cpanel/newsletter/List.test.tsx`
Expected: FAIL — cannot resolve `./List`.

- [ ] **Step 5: Write the List page**

Create `resources/js/pages/cpanel/newsletter/List.tsx`:

```tsx
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Pagination } from '@/components/admin/Pagination';
import { StatusPill } from '@/components/admin/StatusPill';
import type { PillTone } from '@/components/admin/StatusPill';
import type { Paginator } from '@/lib/types';
import type { FormEvent, ReactElement } from 'react';

interface Row {
  id: number;
  email: string;
  status: 'pending' | 'confirmed' | 'unsubscribed';
  locale: string | null;
  source: string;
  subscribed: string | null;
}
interface ListProps {
  subscribers: Paginator<Row>;
  filters: { status: string | null; search: string | null };
}

const BASE = '/agentic-cms-laravel-admin/newsletter';

const STATUS_META: Record<Row['status'], { tone: PillTone; key: string; fallback: string }> = {
  confirmed: { tone: 'success', key: 'cpanel/newsletter.status_confirmed', fallback: 'Confirmed' },
  pending: { tone: 'warning', key: 'cpanel/newsletter.status_pending', fallback: 'Pending' },
  unsubscribed: { tone: 'muted', key: 'cpanel/newsletter.status_unsubscribed', fallback: 'Unsubscribed' },
};

const FILTERS: Array<{ value: string | null; key: string; fallback: string }> = [
  { value: null, key: 'cpanel/newsletter.filter_all', fallback: 'All' },
  { value: 'confirmed', key: 'cpanel/newsletter.status_confirmed', fallback: 'Confirmed' },
  { value: 'pending', key: 'cpanel/newsletter.status_pending', fallback: 'Pending' },
  { value: 'unsubscribed', key: 'cpanel/newsletter.status_unsubscribed', fallback: 'Unsubscribed' },
];

export default function List({ subscribers, filters }: ListProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const rows = subscribers.data;

  const addForm = useForm({ email: '' });
  const searchForm = useForm({ search: filters.search ?? '' });

  const add = (e: FormEvent) => {
    e.preventDefault();
    addForm.post(BASE, { preserveScroll: true, onSuccess: () => addForm.reset('email') });
  };

  const search = (e: FormEvent) => {
    e.preventDefault();
    router.get(BASE, { status: filters.status ?? undefined, search: searchForm.data.search || undefined }, { preserveState: true });
  };

  const del = (id: number) => {
    if (!window.confirm(tr('cpanel/newsletter.delete_confirm', 'Delete this subscriber?'))) return;
    router.delete(`${BASE}/${id}`, { preserveScroll: true });
  };

  const filterHref = (status: string | null) =>
    `${BASE}?${new URLSearchParams({
      ...(status ? { status } : {}),
      ...(filters.search ? { search: filters.search } : {}),
    }).toString()}`;

  return (
    <>
      <Head title={tr('cpanel/newsletter.list_headline', 'Newsletter')} />
      <div className="mb-5 flex items-center justify-between gap-3">
        <h1 className="text-[22px] font-semibold tracking-tight">
          {tr('cpanel/newsletter.list_headline', 'Newsletter')}
        </h1>
        <a
          href={`${BASE}/export`}
          className="rounded-md border border-strong px-3 py-1.5 text-[12.5px] font-medium text-fg transition-colors hover:bg-surface-2"
          data-testid="newsletter-export"
        >
          {tr('cpanel/newsletter.export_button', 'Export CSV')}
        </a>
      </div>

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form onSubmit={add} className="flex gap-2" data-testid="newsletter-add">
          <input
            type="email"
            required
            placeholder={tr('cpanel/newsletter.add_placeholder', 'new@example.com')}
            value={addForm.data.email}
            onChange={(e) => addForm.setData('email', e.target.value)}
            className="h-9 w-64 rounded-md border border-border bg-surface px-3 text-[13.5px] outline-none focus:border-strong"
            data-testid="newsletter-add-email"
          />
          <button type="submit" disabled={addForm.processing} className="rounded-md bg-fg px-3 py-1.5 text-[12.5px] font-medium text-bg">
            {tr('cpanel/newsletter.add_button', 'Add subscriber')}
          </button>
        </form>

        <form onSubmit={search} className="flex gap-2">
          <input
            type="search"
            placeholder={tr('cpanel/newsletter.search_placeholder', 'Search email…')}
            value={searchForm.data.search}
            onChange={(e) => searchForm.setData('search', e.target.value)}
            className="h-9 w-56 rounded-md border border-border bg-surface px-3 text-[13.5px] outline-none focus:border-strong"
            data-testid="newsletter-search"
          />
        </form>
      </div>

      <div className="mb-4 flex flex-wrap gap-1.5" data-testid="newsletter-filters">
        {FILTERS.map((f) => {
          const active = (filters.status ?? null) === f.value;
          return (
            <Link
              key={f.value ?? 'all'}
              href={filterHref(f.value)}
              data-testid={`filter-${f.value ?? 'all'}`}
              aria-current={active ? 'true' : undefined}
              className={`rounded-full px-3 py-1 text-[12.5px] font-medium transition-colors ${
                active ? 'bg-fg text-bg' : 'border border-border text-muted hover:bg-surface-2'
              }`}
            >
              {tr(f.key, f.fallback)}
            </Link>
          );
        })}
      </div>

      <div className="admin-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-[13.5px]">
            <thead>
              <tr className="text-left text-[11px] uppercase tracking-wide text-faint">
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/newsletter.table_email', 'Email')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/newsletter.table_status', 'Status')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/newsletter.table_locale', 'Locale')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/newsletter.table_source', 'Source')}</th>
                <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/newsletter.table_subscribed', 'Subscribed')}</th>
                <th className="w-[90px] border-b admin-sep px-4 py-2.5" />
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 && (
                <tr>
                  <td colSpan={6} className="border-b admin-sep px-4 py-8 text-center text-muted">
                    {tr('cpanel/newsletter.not_found', 'No subscribers yet')}
                  </td>
                </tr>
              )}
              {rows.map((r) => {
                const meta = STATUS_META[r.status];
                return (
                  <tr key={r.id} className="transition-colors hover:bg-surface-2">
                    <td className="border-b admin-sep px-4 py-3 font-medium text-fg">{r.email}</td>
                    <td className="border-b admin-sep px-4 py-3">
                      <StatusPill tone={meta.tone} label={tr(meta.key, meta.fallback)} />
                    </td>
                    <td className="border-b admin-sep px-4 py-3 uppercase text-muted">{r.locale ?? '—'}</td>
                    <td className="border-b admin-sep px-4 py-3 text-muted">{r.source}</td>
                    <td className="whitespace-nowrap border-b admin-sep px-4 py-3 tabular-nums text-faint">{r.subscribed ?? '—'}</td>
                    <td className="border-b admin-sep px-4 py-3 text-right">
                      <button onClick={() => del(r.id)} className="text-[12.5px] text-muted hover:text-error">
                        {tr('cpanel/newsletter.delete', 'Delete')}
                      </button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
        <Pagination meta={subscribers} />
      </div>
    </>
  );
}

List.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Newsletter">{page}</AdminLayout>
);
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `npx vitest run resources/js/pages/cpanel/newsletter/List.test.tsx`
Expected: PASS (2 tests).

- [ ] **Step 7: Build + Sidebar smoke**

Run: `npm run build`
Expected: succeeds. The Sidebar renders the Newsletter item for users with `manage_newsletter` (it reads `NAV_GROUPS` and gates on `auth.can[item.ability]`).

- [ ] **Step 8: Commit**

```bash
git add resources/js/pages/cpanel/newsletter/ resources/lang/en/cpanel/newsletter.php resources/lang/en/cpanel/menu.php resources/js/lib/admin-nav.ts
git commit -m "Newsletter: admin List page + sidebar nav + en admin strings"
```

---

## Task 9: German + Russian translations + full regression

**Files:**
- Create: `resources/lang/de/default/newsletter.php`, `resources/lang/ru/default/newsletter.php`
- Create: `resources/lang/de/cpanel/newsletter.php`, `resources/lang/ru/cpanel/newsletter.php`
- Modify: `resources/lang/de/cpanel/menu.php`, `resources/lang/ru/cpanel/menu.php` (`'newsletter'` label)

**Interfaces:**
- Consumes: the English key sets from Tasks 2 and 8 (same keys, translated values).

- [ ] **Step 1: Write `resources/lang/de/default/newsletter.php`**

Mirror every key from `resources/lang/en/default/newsletter.php` with German values:

```php
<?php

return [
    'widget_heading' => 'Newsletter abonnieren',
    'widget_subtitle' => 'Gelegentliche Updates. Kein Spam. Jederzeit abbestellbar.',
    'widget_placeholder' => 'du@example.com',
    'widget_button' => 'Abonnieren',
    'widget_submitted' => 'Danke — bitte bestätige dein Abo über den Link in deinem Postfach.',
    'check_inbox' => 'Falls die Adresse neu ist, bestätige dein Abo über den Link in deinem Postfach.',
    'email_subject' => 'Bestätige dein Newsletter-Abo',
    'email_heading' => 'Abo bestätigen',
    'email_intro' => 'Du (oder jemand mit dieser Adresse) hat unseren Newsletter abonniert. Bestätige unten, um ihn zu erhalten.',
    'email_button' => 'Abo bestätigen',
    'email_fallback' => 'Falls der Button nicht funktioniert, kopiere diesen Link in deinen Browser:',
    'email_ignore' => 'Falls du das nicht angefragt hast, kannst du diese E-Mail ignorieren.',
    'confirm_confirmed_title' => 'Abo bestätigt',
    'confirm_confirmed_body' => 'Danke — dein Abo ist jetzt aktiv.',
    'confirm_already_title' => 'Bereits bestätigt',
    'confirm_already_body' => 'Dieses Abo wurde bereits bestätigt. Nichts weiter zu tun.',
    'confirm_invalid_title' => 'Ungültiger Link',
    'confirm_invalid_body' => 'Dieser Bestätigungslink ist ungültig. Vielleicht ein Tippfehler.',
    'unsub_done_title' => 'Du wurdest abgemeldet',
    'unsub_done_body' => 'Du erhältst den Newsletter nicht mehr. Meinung geändert?',
    'unsub_resubscribe_button' => 'Erneut abonnieren',
    'unsub_invalid_title' => 'Ungültiger Link',
    'unsub_invalid_body' => 'Dieser Abmeldelink ist ungültig.',
    'unsub_resubmitted' => 'Bitte bestätige dein Abo erneut über den Link in deinem Postfach.',
];
```

- [ ] **Step 2: Write `resources/lang/ru/default/newsletter.php`**

```php
<?php

return [
    'widget_heading' => 'Подписаться на рассылку',
    'widget_subtitle' => 'Изредка обновления. Без спама. Отписаться можно в любой момент.',
    'widget_placeholder' => 'you@example.com',
    'widget_button' => 'Подписаться',
    'widget_submitted' => 'Спасибо — подтвердите подписку по ссылке в письме.',
    'check_inbox' => 'Если адрес новый, подтвердите подписку по ссылке в письме.',
    'email_subject' => 'Подтвердите подписку на рассылку',
    'email_heading' => 'Подтверждение подписки',
    'email_intro' => 'Вы (или кто-то с этим адресом) подписались на нашу рассылку. Подтвердите ниже, чтобы начать её получать.',
    'email_button' => 'Подтвердить подписку',
    'email_fallback' => 'Если кнопка не работает, скопируйте ссылку в браузер:',
    'email_ignore' => 'Если вы этого не запрашивали, просто проигнорируйте письмо.',
    'confirm_confirmed_title' => 'Подписка подтверждена',
    'confirm_confirmed_body' => 'Спасибо — подписка активна.',
    'confirm_already_title' => 'Уже подтверждено',
    'confirm_already_body' => 'Эта подписка уже была подтверждена. Ничего делать не нужно.',
    'confirm_invalid_title' => 'Недействительная ссылка',
    'confirm_invalid_body' => 'Ссылка подтверждения недействительна. Возможно, опечатка.',
    'unsub_done_title' => 'Вы отписались',
    'unsub_done_body' => 'Вы больше не будете получать рассылку. Передумали?',
    'unsub_resubscribe_button' => 'Подписаться снова',
    'unsub_invalid_title' => 'Недействительная ссылка',
    'unsub_invalid_body' => 'Ссылка отписки недействительна.',
    'unsub_resubmitted' => 'Подтвердите подписку снова по ссылке в письме.',
];
```

- [ ] **Step 3: Write `resources/lang/de/cpanel/newsletter.php`**

```php
<?php

return [
    'list_headline' => 'Newsletter',
    'table_email' => 'E-Mail',
    'table_status' => 'Status',
    'table_locale' => 'Sprache',
    'table_source' => 'Quelle',
    'table_subscribed' => 'Abonniert',
    'status_confirmed' => 'Bestätigt',
    'status_pending' => 'Ausstehend',
    'status_unsubscribed' => 'Abgemeldet',
    'filter_all' => 'Alle',
    'search_placeholder' => 'E-Mail suchen…',
    'add_placeholder' => 'neu@example.com',
    'add_button' => 'Abonnent hinzufügen',
    'delete' => 'Löschen',
    'delete_confirm' => 'Diesen Abonnenten löschen?',
    'export_button' => 'CSV exportieren',
    'not_found' => 'Noch keine Abonnenten',
    'added' => 'Abonnent hinzugefügt.',
    'deleted' => 'Abonnent gelöscht.',
];
```

- [ ] **Step 4: Write `resources/lang/ru/cpanel/newsletter.php`**

```php
<?php

return [
    'list_headline' => 'Рассылка',
    'table_email' => 'E-mail',
    'table_status' => 'Статус',
    'table_locale' => 'Язык',
    'table_source' => 'Источник',
    'table_subscribed' => 'Подписан',
    'status_confirmed' => 'Подтверждён',
    'status_pending' => 'Ожидает',
    'status_unsubscribed' => 'Отписан',
    'filter_all' => 'Все',
    'search_placeholder' => 'Поиск по e-mail…',
    'add_placeholder' => 'new@example.com',
    'add_button' => 'Добавить подписчика',
    'delete' => 'Удалить',
    'delete_confirm' => 'Удалить этого подписчика?',
    'export_button' => 'Экспорт CSV',
    'not_found' => 'Пока нет подписчиков',
    'added' => 'Подписчик добавлен.',
    'deleted' => 'Подписчик удалён.',
];
```

- [ ] **Step 5: Add the menu label to de + ru**

In `resources/lang/de/cpanel/menu.php` add `'newsletter' => 'Newsletter',` before the closing `];`.
In `resources/lang/ru/cpanel/menu.php` add `'newsletter' => 'Рассылка',` before the closing `];`.

- [ ] **Step 6: Verify locale parity**

Run:
```bash
cd "/Users/huseyn0w/Desktop/SWE/Elman Group/agentic-cms/agentic-cms-laravel"
for f in default/newsletter cpanel/newsletter; do
  echo "== $f =="
  php -r "echo count(include 'resources/lang/en/$f.php');" ; echo -n " en / "
  php -r "echo count(include 'resources/lang/de/$f.php');" ; echo -n " de / "
  php -r "echo count(include 'resources/lang/ru/$f.php');" ; echo " ru"
done
```
Expected: the three counts match for each file (en == de == ru).

- [ ] **Step 7: Full regression**

Run: `php artisan test --filter=Newsletter`
Expected: PASS (all newsletter feature tests).
Run: `php artisan test --exclude-group=arch`
Expected: PASS (no regressions in the wider suite).
Run: `php -d memory_limit=1G artisan test tests/Arch/LayeringTest.php`
Expected: PASS.
Run: `npx vitest run`
Expected: PASS (full frontend suite incl. the two new tests).
Run: `composer analyse`
Expected: PASS (Larastan level 5 clean).
Run: `vendor/bin/pint --test`
Expected: PASS.
Run: `npm run build`
Expected: succeeds.

- [ ] **Step 8: Commit**

```bash
git add resources/lang/de/ resources/lang/ru/
git commit -m "Newsletter: de + ru translations for public + admin strings"
```

---

## Self-Review

**1. Spec coverage** (against `docs/superpowers/specs/2026-08-07-newsletter-phase1-subscribers-design.md`):

- Data model (table, columns, casts, `$hidden` token, `isConfirmed`/`isPending`) → Task 1. ✅
- Layering (Repository/Service/CPanelService) → Tasks 1, 3, 7. ✅
- Public subscribe (footer widget, throttle:5,1, captcha nullable, honeypot, FormRequest, idempotent + non-enumerating, generic flash, `newsletter_status` flash) → Tasks 4, 5. ✅
- Confirm (GET `{token}`, before catch-all, `confirmed`/`already`/`invalid`, PublicLayout page) → Tasks 4, 5. ✅
- Unsubscribe (one-click GET, `done`/`invalid`, resubscribe POST) → Tasks 4, 5. ✅
- Email (queued `ShouldQueue`, markdown, localized to subscriber locale, confirm button + fallback link) → Tasks 2, 3. ✅
- Admin `manage_newsletter` (middleware, alias, policy, seeder, shared ability, data migration backfill, nav item) → Tasks 6, 8. ✅
- Admin screens (index list + status filter chips + email search + StatusPill + Pagination, manual add = confirmed/source=admin, delete, CSV export of confirmed) → Tasks 7, 8. ✅
- i18n `cpanel/newsletter.php` + `cpanel/menu.newsletter` + `default/newsletter.php` en/de/ru → Tasks 2, 8, 9. ✅
- Security/GDPR (double opt-in, forever unsubscribe via stable token, unguessable token, no enumeration, throttle, honeypot, captcha, consent timestamps, token hidden) → Tasks 1, 3, 4. ✅
- Tests (all the spec's backend + frontend cases) → distributed across tasks. ✅

No gaps.

**2. Placeholder scan:** No "TBD"/"handle edge cases"/"similar to Task N" — every code step contains complete code. One deliberate correction-in-place is documented in Task 4 Step 5 (the `confirm()` `already` detection) and Task 8 Step 3 (the `toHaveAttribute` typo guard); both give the final correct code explicitly.

**3. Type consistency:**
- Repository method names (`findByEmail`, `findByToken`, `create`, `save`, `paginateFiltered`, `confirmedEmails`) are used identically in the service (Tasks 3, 7). ✅
- Service signatures (`subscribe/confirm/unsubscribe/resubscribe`) match their controller call sites (Task 4). ✅
- `CPanelNewsletterService` methods (`list/add/delete/exportCsv`) match the controller (Task 7). ✅
- Prop shapes: controller `index` emits `subscribers` (paginator of `{id,email,status,locale,source,subscribed}`) + `filters` `{status,search}`; the List page and its test consume exactly those (Tasks 7, 8). ✅
- Flash `newsletter_status` added in HandleInertiaRequests (Task 4), typed in `types.ts` (Task 5), read by the widget (Task 5). ✅
- `Ability` union gains `manage_newsletter` (Task 6) before the nav item uses it (Task 8). ✅
- Route names (`newsletter.confirm` used by the mailable in Task 2) exist by Task 4; Task 2's test avoids rendering the mailable so it does not depend on the route prematurely. ✅

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-08-07-newsletter-phase1-subscribers.md`.

User preference on record: **inline TDD execution** (executing-plans), not subagent ceremony. On approval I'll execute Tasks 1→9 in this session, committing per task and checkpointing for review.
