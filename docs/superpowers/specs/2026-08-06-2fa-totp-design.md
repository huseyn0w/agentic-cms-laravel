# Two-factor authentication (TOTP) — design

**Date:** 2026-08-06
**Branch:** `feat/inertia-migration`
**Status:** approved, ready for implementation plan

## Goal

Add TOTP-based two-factor authentication to the interactive web login: per-user
opt-in enrollment with recovery codes, plus an admin-wide switch that forces
anyone with admin-panel access to enroll. Copies the mechanism shape from
Laravel Fortify but stays inside the project's own conventions (Inertia + React,
`app/Http/Models`, repository/service layering, custom permission middleware,
the existing `security_settings` singleton and `security_audit_log`).

## Non-goals

- **API / Passport is out of scope.** 2FA guards only the interactive web
  login. OAuth bearer tokens are separate credentials and are unaffected.
- **Social login (Google) bypasses app 2FA.** The OAuth provider is itself the
  identity provider and can carry its own 2FA. `handleProviderCallback` logs the
  user in directly without a TOTP challenge. Documented, not a gap.
- No SMS/email OTP, no WebAuthn/passkeys, no per-role granularity (a single
  admin-wide switch only — per-role can be layered on later).

## Dependencies

Two new composer packages (dry-run resolved cleanly against packagist in the dev
sandbox; production installs them at deploy on Hostinger):

- `pragmarx/google2fa` `^9.0` — TOTP secret generation + code verification.
- `bacon/bacon-qr-code` `^3.1` — server-side SVG QR rendering (pulls
  `dasprid/enum`).

PHP `ext-gmp` / `ext-bcmath` / `ext-openssl` are present in the environment.

## Data model

### `users` table (new migration)

| Column | Type | Notes |
| --- | --- | --- |
| `two_factor_secret` | `text` nullable | base32 TOTP secret; `encrypted` cast |
| `two_factor_recovery_codes` | `text` nullable | JSON array; `encrypted` cast |
| `two_factor_confirmed_at` | `timestamp` nullable | null until the user verifies a first code |

A non-null `two_factor_secret` with a null `two_factor_confirmed_at` means
"enrollment pending" (secret issued, not yet proven). Both non-null means 2FA is
active. Column shape matches Fortify so the mental model is familiar.

### `security_settings` singleton (new column)

| Column | Type | Default |
| --- | --- | --- |
| `require_2fa_for_admins` | `boolean` | `false` |

Added via a new migration on the existing `security_settings` table. Exposed by
`get_security_settings('require_2fa_for_admins')` (already tolerant of a missing
row) and edited on the Security screen alongside the login-protection form.

### `User` model changes

- `$casts`: `two_factor_secret => 'encrypted'`, `two_factor_recovery_codes =>
  'encrypted'`, `two_factor_confirmed_at => 'datetime'`.
- `$hidden`: add `two_factor_secret`, `two_factor_recovery_codes`.
- **Not** added to `$fillable` — these are never mass-assignable; the service
  sets them explicitly via `forceFill(...)->save()`.
- Helper: `hasEnabledTwoFactor(): bool` — `two_factor_secret` present **and**
  `two_factor_confirmed_at` non-null. (A thin convenience read used by the flow
  and middleware; the authority on state stays the columns.)

## Components

### `App\Services\Auth\TwoFactorService`

Stateless wrapper over the two libraries. All TOTP knowledge lives here; nothing
else imports google2fa or bacon directly.

- `generateSecret(): string` — new base32 secret.
- `verify(string $secret, string $code): bool` — google2fa verify with a ±1
  time-step window; returns false on empty/malformed input.
- `qrCodeSvg(string $company, string $holder, string $secret): string` — build
  the `otpauth://totp/...` URI and render a self-contained SVG string (embedded
  by the frontend as an inline `<img>`/data URI). `$company` = site name,
  `$holder` = user email.
- `generateRecoveryCodes(int $count = 8): array` — 8 codes, format
  `xxxxxxxx-xxxxxxxx` (two 8-char base32 groups).

No DB access — the service is pure logic so it unit-tests against the RFC 6238
reference vectors and the library. Persistence lives in the repository.

### `App\Repositories\CPanelUserRepository` (extend) or a focused
`TwoFactorRepository`

Persistence for the three user columns, keeping the controller/service off the
ORM per LayeringTest:

- `startEnrollment(User $user, string $secret): void` — set secret, null
  `confirmed_at`, null recovery codes.
- `confirmEnrollment(User $user, array $recoveryCodes): void` — set
  `confirmed_at = now()`, store codes.
- `disable(User $user): void` — null all three columns.
- `replaceRecoveryCodes(User $user, array $codes): void`.
- `consumeRecoveryCode(User $user, string $code): bool` — match a stored code,
  remove it, persist; return whether one was consumed.

(Chosen home: add these to `CPanelUserRepository`. It already owns user
persistence; a separate repo would fragment the user aggregate.)

### Controllers

`App\Http\Controllers\Auth\TwoFactorController` (guarded by `auth`):

- `enable()` — start enrollment, return QR SVG + secret (Inertia back with props
  or a JSON-ish Inertia response the profile page reads).
- `confirm(Request)` — validate code against the pending secret; on success
  confirm + generate recovery codes, flash them once; on failure, validation
  error.
- `disable(Request)` — require current password, then clear.
- `recoveryCodes(Request)` — require current password, regenerate + return.

`App\Http\Controllers\Auth\TwoFactorChallengeController` (guarded by `guest`-ish
— actually accessible mid-login, see flow):

- `show()` — render `auth/TwoFactorChallenge` (reads pending state from session;
  redirects to `/login` if none).
- `store(Request)` — verify a TOTP code or a recovery code for the pending user;
  on success complete login; on failure audit + error.

Security-settings toggle `require_2fa_for_admins` is added to the existing
`ValidateSecuritySettings` rules and `CPanelSecurityController` prop map (no new
controller).

### `App\Http\Middleware\RequireTwoFactorEnrollment`

Modeled on `EnsureEmailIsVerifiedWhenRequired`:

```
if (! get_security_settings('require_2fa_for_admins')) return $next();     // no-op when off
$user = $request->user();
if ($user && $user->can('see_admin_panel', UserRoles::class)
          && ! $user->hasEnabledTwoFactor()) {
    // allow the 2FA enrollment routes + logout through, else redirect to enrollment
    return redirect()->route('cpanel_myprofile')->with('status', ...2fa_required...);
}
return $next();
```

Applied to the admin route group. Two exemptions keep the user from being
trapped in a redirect loop:

- The `/two-factor/*` enrollment endpoints live **outside** the admin prefix, so
  the admin-group middleware never runs on them — the user can always reach the
  enable/confirm actions.
- `cpanel_myprofile` (the redirect target, inside the admin group) is explicitly
  exempted from this middleware so the profile page — which hosts the enrollment
  UI — renders for a not-yet-enrolled admin.

`logout` is likewise reachable (its own route, outside this middleware).

## Login challenge flow

1. `LoginController@login`: after `Auth::attempt(...)` returns true, check
   `Auth::user()->hasEnabledTwoFactor()`.
   - **No 2FA:** current behavior unchanged — regenerate session, clear login
     attempts, redirect intended.
   - **2FA enabled:** capture `$userId` and the `remember` boolean, call
     `Auth::logout()` (undo the just-established session auth), put
     `['two_factor.user_id' => $userId, 'two_factor.remember' => $remember]`
     into the session, redirect to `route('two-factor.challenge')`. Do **not**
     clear login attempts yet (only a completed challenge counts as success).
2. `TwoFactorChallengeController@show` renders the challenge page; if the session
   has no pending user id, redirect to `/login`.
3. `@store`: load the pending user; verify the submitted code as TOTP first,
   then as a recovery code (consume it if matched). On success: `Auth::login`
   with the remembered flag, `session()->regenerate()`, clear the pending keys,
   clear login attempts, audit `login`. On failure: increment login attempts
   (shared throttle), audit `2fa_failed`, return a validation error.

The existing `ThrottlesLogins` guard wraps both the password step and the
challenge step (the challenge controller reuses `hasTooManyLoginAttempts` /
`incrementLoginAttempts` keyed on the pending user's email + IP).

## Audit integration

Reuse `security_audit_log` via the existing `AuditLogService`. New action
strings recorded directly by the 2FA controllers (not via framework events,
which don't cover these):

- `2fa_enabled` — on successful confirm.
- `2fa_disabled` — on disable.
- `2fa_failed` — on a failed challenge.

Successful challenge is already covered by the `login` action recorded when
`Auth::login` fires the `Login` event through `AuthAuditSubscriber`. Add the new
strings to the Security screen's action filter list and their i18n labels.

## Frontend (Inertia + React)

- **Profile 2FA section** (`resources/js/pages/cpanel/users/Form.tsx` or a
  dedicated `TwoFactor` panel component rendered there): three states —
  disabled (Enable button), pending (QR + secret + confirm-code input), enabled
  (status + recovery-codes reveal + Regenerate + Disable, both gated by a
  password field).
- **Challenge page** (`resources/js/pages/auth/TwoFactorChallenge.tsx`): single
  code input, a "use a recovery code instead" toggle, submit.
- **Security screen**: add the `require_2fa_for_admins` checkbox to the
  login-protection form.
- All strings via the `cpanel/security.*` and a new `auth`/`cpanel/twofactor`
  i18n namespace, en/de/ru.

## Testing (TDD, ~100% of touched code)

**Unit (Pest):** `TwoFactorService` — secret generation is base32; `verify`
accepts a code computed for a known secret/time and rejects a wrong one (RFC
6238 reference vector); `generateRecoveryCodes` returns 8 unique formatted
codes; `qrCodeSvg` returns a string containing `<svg`.

**Feature (Pest):**
- Enable → confirm persists `two_factor_confirmed_at` and issues 8 codes.
- A user with 2FA is redirected to the challenge on login, not logged in yet;
  correct TOTP completes login; wrong code fails and is audited `2fa_failed`.
- Recovery code completes login and is consumed (not reusable).
- Disable clears the columns (and requires the current password).
- `require_2fa_for_admins` on: an admin without 2FA hitting an admin route is
  redirected to enrollment; with 2FA confirmed, passes.
- Social login (`handleProviderCallback`) logs a 2FA-enabled user straight in
  (bypass).
- The login throttle counts failed challenges.
- Security settings screen persists `require_2fa_for_admins`.

**Frontend (Vitest):** profile panel renders each of the three states from
props; challenge page renders the code input and the recovery-code toggle.

## Rollout / safety

- All migrations are additive and nullable — no backfill, existing users are
  unaffected (2FA simply off).
- `require_2fa_for_admins` defaults off, so enabling 2FA is opt-in until an admin
  flips the switch.
- Recovery codes and the secret are encrypted at rest via the `encrypted` cast
  (Laravel `APP_KEY`).

## Resolved implementation decisions

- **Redirect-exemption:** `RequireTwoFactorEnrollment` exempts the
  `cpanel_myprofile` route (see the middleware section) so a non-enrolled admin
  can always render the profile page that hosts enrollment. No loop.
- **QR delivery:** the enable/confirm endpoints return the QR SVG and secret as
  **Inertia props** (a partial reload of the profile page), not a separate JSON
  endpoint — consistent with the rest of the admin. The React panel reads
  `two_factor` props (`qr_svg`, `secret`, `recovery_codes`, `status`).
