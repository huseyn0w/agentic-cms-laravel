# Phase 2 — Auth on Inertia + React (design)

**Status:** approved design (Q&A 2026-07-25). Part of the Blade → Inertia migration
(`~/.claude/plans/wild-percolating-allen.md`). Builds on Phase 1 i18n (the `messages`
prop + `t()` are already live; all auth lang files are already flattened into the
dictionary).

**Goal:** Migrate the five auth screens (login, register, forgot-password,
reset-password, verify-email) to Inertia + React, **fully removing `laravel/ui`**,
with a Vercel-style split-screen layout rendered on the project's existing warm brand
tokens, in light and dark. Social login is reduced to **Google only**.

## Why this scope

Removing `laravel/ui` means dropping the five framework auth traits
(`AuthenticatesUsers`, `RegistersUsers`, `SendsPasswordResetEmails`, `ResetsPasswords`,
`VerifiesEmails`) and the `Auth::routes()` macro. Those traits are the only thing that
still renders Blade auth views (`view('auth.login')` etc.) and provide the POST
handlers. Once we override every render path to `Inertia::render(...)` we already have
to reimplement the trait glue, so keeping the traits buys nothing. We hand-write the
routes and thin controllers instead, moving validation into FormRequests. The domain
services (`SocialAuthService`, `UserRegistrationService`, `PasswordResetService`,
`UserRepository`) are untouched — they already own all business logic.

## Architecture

```
routes/auth.php                     ← new; 12 hand-written routes, replaces Auth::routes()
app/Http/Controllers/Auth/*         ← thin: GET → Inertia::render, POST → service + redirect
app/Http/Requests/Auth/*Request     ← LoginRequest, RegisterRequest, ForgotPasswordRequest,
                                        ResetPasswordRequest (validation moves out of traits)
resources/js/layouts/AuthLayout.tsx ← split-screen shell (brand panel + form panel), theming
resources/js/pages/auth/*.tsx       ← Login, Register, ForgotPassword, ResetPassword, VerifyEmail
resources/js/components/auth/*       ← TextField, GoogleButton (Button already conceptually exists as x-button; port a minimal React Button)
```

Data still flows the Inertia way: controllers pass props (never JSON), `useForm` posts
back, a 422 populates the `errors` prop. No API layer.

### The 12 routes (replacing `Auth::routes(['verify' => true])`)

`Auth::routes(['verify'=>true])` currently registers exactly these. We reproduce them
verbatim in `routes/auth.php` (same URIs, same route names — names are load-bearing:
`route('login')`, `route('password.request')`, `route('verification.resend')` are used
across the app and in redirects):

| Method | URI | Name | Controller@method | Middleware |
|---|---|---|---|---|
| GET | `login` | `login` | `LoginController@showLoginForm` | `guest` |
| POST | `login` | — | `LoginController@login` | `guest` |
| POST | `logout` | `logout` | `LoginController@logout` | — |
| GET | `register` | `register` | `RegisterController@showRegistrationForm` | `guest`, `registration_enabled` |
| POST | `register` | — | `RegisterController@register` | `guest`, `registration_enabled` |
| GET | `password/reset` | `password.request` | `ForgotPasswordController@showLinkRequestForm` | `guest` |
| POST | `password/email` | `password.email` | `ForgotPasswordController@sendResetLinkEmail` | `guest` |
| GET | `password/reset/{token}` | `password.reset` | `ResetPasswordController@showResetForm` | `guest` |
| POST | `password/reset` | `password.update` | `ResetPasswordController@reset` | `guest` |
| GET | `email/verify` | `verification.notice` | `VerificationController@show` | `auth` |
| GET | `email/verify/{id}/{hash}` | `verification.verify` | `VerificationController@verify` | `auth`, `signed`, `throttle:6,1` |
| POST | `email/resend` | `verification.resend` | `VerificationController@resend` | `auth`, `throttle:6,1` |

Plus the existing custom logout alias at `web.php:16`
(`GET /logout` → `cpanel-logout`) and the two social routes stay, narrowed to Google
(see below). `routes/auth.php` is required from `routes/web.php` in place of the
`Auth::routes()` line. `web.php:14` (`Auth::routes(['verify' => true]);`) is deleted.

**Controller method names change** because we drop the traits: `showLoginForm`,
`showRegistrationForm`, `showLinkRequestForm`, `showResetForm`, `show` (verify notice)
now live in our controllers and return `Inertia::render`. The POST handlers
(`login`, `register`, `sendResetLinkEmail`, `reset`, `verify`, `resend`) are
reimplemented thin (the trait versions were ~15 lines each of stock Laravel; we inline
the needed parts and delegate persistence to the existing services).

### Controllers (thin, post-trait)

Each controller keeps its current constructor (middleware + injected service) and its
current preserved override. We add the render + POST methods the traits used to supply.

- **LoginController** — keep `credentials()` (email-or-username), keep
  `redirectToProvider`/`handleProviderCallback` (Google-only now). Add:
  - `showLoginForm()` → `Inertia::render('auth/Login', ['status' => session('status'), 'canResetPassword' => Route::has('password.request'), 'membershipEnabled' => (bool) get_general_settings('membership')])`.
  - `login(LoginRequest $request)` → **use the framework-core
    `Illuminate\Foundation\Auth\ThrottlesLogins` trait** (this is core Laravel, NOT
    `laravel/ui`) for full lockout parity: `hasTooManyLoginAttempts` →
    `fireLockoutEvent` + `sendLockoutResponse` (throws `ValidationException` keyed on
    `throttleKey()` with `trans('auth.throttle')`); otherwise
    `Auth::attempt($this->credentials($request), $request->boolean('remember'))`; on
    success `clearLoginAttempts` + `$request->session()->regenerate()` + redirect
    intended (`/`); on failure `incrementLoginAttempts` +
    `throw ValidationException::withMessages(['email' => [trans('auth.failed')]])`.
    `username()` returns `'email'` (the trait keys throttle on `email` + IP, matching the
    old behavior). Lockout = 5 attempts / 1 minute (trait defaults `maxAttempts()=5`,
    `decayMinutes()=1`).
  - `logout(Request $request)` → `Auth::logout()`, invalidate + regenerate token,
    redirect `/`.
- **RegisterController** — keep constructor (`guest` + `registration_enabled`, injected
  `UserRegistrationService`). Add `showRegistrationForm()` →
  `Inertia::render('auth/Register')`. Add `register(RegisterRequest $request)` →
  `$user = $this->registrar->register($request->validated()); Auth::login($user);
  event(new Registered($user)); return redirect('/');` (the `Registered` event is what
  sends the verification email when enabled — the trait fired it; we must keep it).
- **ForgotPasswordController** — drop `SendsPasswordResetEmails`. Add
  `showLinkRequestForm()` → `Inertia::render('auth/ForgotPassword', ['status' => session('status')])`.
  Add `sendResetLinkEmail(ForgotPasswordRequest $request)` →
  `$status = Password::sendResetLink($request->only('email'));` then redirect back with
  `->with('status', trans($status))` on success or `withErrors(['email' => trans($status)])`.
- **ResetPasswordController** — drop `ResetsPasswords`, **keep `setUserPassword()`**
  (double-hash guard). Add `showResetForm(Request $request, $token)` →
  `Inertia::render('auth/ResetPassword', ['token' => $token, 'email' => $request->email])`.
  Add `reset(ResetPasswordRequest $request)` → `Password::reset(...)` with the closure
  calling `$this->setUserPassword($user, $password); $user->save(); Auth::login($user);`
  then redirect `/` with status, or `withErrors(['email' => trans($status)])`.
- **VerificationController** — drop `VerifiesEmails`. Keep constructor middleware. Add
  `show(Request $request)` → if already verified redirect `/`, else
  `Inertia::render('auth/VerifyEmail', ['status' => session('resent') ? 'resent' : null])`.
  Add `verify(EmailVerificationRequest $request)` (Laravel core FormRequest that checks
  the signed id/hash) → `$request->fulfill(); return redirect('/')->with('verified', true);`.
  Add `resend(Request $request)` → `$request->user()->sendEmailVerificationNotification();
  return back()->with('resent', true);`.

All of `Registered`, `Password`, `EmailVerificationRequest`, `ThrottlesLogins` are
`Illuminate\*` framework core, not `laravel/ui`. Only the five `*Users`/`Verifies`/
`Resets`/`Sends` traits and the `Auth::routes()` macro come from `laravel/ui`.

### FormRequests

Move the validation that lived in the traits/`validator()` into requests. Rules copied
verbatim from current code:

- **LoginRequest** — `email` (the field is named `email` but accepts username too):
  `['email' => ['required','string'], 'password' => ['required','string']]`.
  `authorize(): true`.
- **RegisterRequest** — verbatim from `RegisterController::validator()`:
  `name` required string max:255; `username` required string max:10 unique:users;
  `email` required string email max:255 unique:users; `password` required string min:8
  confirmed.
- **ForgotPasswordRequest** — `['email' => ['required','email']]`.
- **ResetPasswordRequest** — `['token' => ['required'], 'email' => ['required','email'],
  'password' => ['required','string','min:8','confirmed']]`.

### Google-only social login

- `config/services.php`: **remove** `facebook`, `github`, `linkedin` blocks; **add**
  ```php
  'google' => [
      'client_id' => env('GOOGLE_CLIENT_ID'),
      'client_secret' => env('GOOGLE_CLIENT_SECRET'),
      'redirect' => env('GOOGLE_CALLBACK_URL'),
  ],
  ```
- `routes/web.php:276-277`: narrow the `where('provider', ...)` constraint from
  `twitter|facebook|linkedin|google|github` to `google`. Method + names unchanged.
- `SocialAuthService` / `LoginController::handleProviderCallback` stay provider-generic
  (they already are) — no logic change. The account-takeover guard, `role_id` default,
  membership gating, and `SocialEmailNotVerifiedException` handling are preserved as-is.
- UI: the Login page shows **one** "Continue with Google" button above the email/password
  form, with an "or" divider (mirrors the current login blade structure, minus the 3
  extra provider buttons). No social buttons on Register (a Google sign-up happens
  through the same `/login/google` flow; the Login page is the single social entry point,
  same as today).
- Delete `resources/views/auth/social.blade.php` in Phase 5 cleanup (kept until the
  Blade views are removed so nothing 500s mid-migration; the React GoogleButton is the
  live path once routes point at Inertia).

## Frontend design

### AuthLayout (split-screen)

`resources/js/layouts/AuthLayout.tsx` — two-column on `lg+`, single column below.

- **Root:** wraps everything in a `theme-default` class container so the scoped base
  reset + typography from `resources/css/app.css` apply (the Inertia root `app.blade.php`
  does NOT carry `.theme-default`, unlike the legacy theme layout). Background `bg-bg`,
  text `text-fg`. `min-h-screen`.
- **Left brand panel** (`hidden lg:flex`, ~45% width): solid brand surface
  (`bg-primary text-primary-contrast`) with the wordmark "Agentic CMS", the tagline
  "AI First CMS you run from your AI assistant", and a subtle decorative pattern
  (CSS-only, e.g. a faint dotted/grid overlay using tokens — no external asset).
- **Right form panel** (full width on mobile, ~55% on `lg+`): centered card column,
  `max-w-[440px]`, holds the page's `children` (heading + form).
- **Theme bootstrap:** on mount, read `localStorage['agentic-cms-theme']` (fallback
  `prefers-color-scheme`) and toggle `.dark` on `document.documentElement` — same key and
  behavior as `front.js` (`THEME_KEY = 'agentic-cms-theme'`). This makes the Inertia auth
  pages honor the user's saved theme without loading the legacy `front.js`. Optionally a
  small light/dark toggle in the form panel header (nice-to-have; not required for
  parity — see Open decision T-toggle).

### Pages (`resources/js/pages/auth/`)

Each page renders inside `<AuthLayout>`, uses `useForm` from `@inertiajs/react`, and
gets its copy through `t()` (react-i18next, already wired). Field errors come from the
Inertia `errors` prop (populated by the FormRequest 422). Keys are the SAME lang keys
the blades used, so no new translations are needed:

- **Login.tsx** — heading `t('login.login_page_headline')`, GoogleButton
  (`t('login.login_with')` label context → "Continue with Google"), divider `t('login.or')`,
  then form: username-or-email field (`t('login.username_or_email')`,
  **`data-testid="login-username"`**, `name="email"`), password
  (`t('login.password')`, **`data-testid="login-password"`**), remember checkbox
  (`t('login.remember_me')`), submit (`t('login.login')`, **`data-testid="login-submit"`**),
  forgot-password link (`t('login.forgot_password')` → `route('password.request')` via
  Inertia `<Link>`). Shows `status` flash when present. The three `data-testid`s are
  **mandatory** (Pest browser suite depends on them).
- **Register.tsx** — fields name/email/username/password/password_confirmation
  (labels from `registration.*`), submit `t('registration.register_btn')`, link back to
  login. Posts to `route('register')`.
- **ForgotPassword.tsx** — email field (`custom-passwords.email`), submit
  `t('custom-passwords.send_password_link')`, success `status` alert, link back to login.
  Posts to `route('password.email')`.
- **ResetPassword.tsx** — hidden `token` (from prop), email (prefilled from prop),
  password, password_confirmation (labels from `custom-passwords.*`), submit
  `t('custom-passwords.reset_password_btn')`. Posts to `route('password.update')`.
- **VerifyEmail.tsx** — copy from `email.*`; resend link posts to
  `route('verification.resend')`; shows "fresh link" alert when `status === 'resent'`.

### Shared React components (`resources/js/components/`)

Minimal ports of the Blade equivalents (semantic Tailwind classes already exist):

- **Button.tsx** — port of `x-button` variants (primary/outline/ghost) + sizes +
  loading spinner + `data-testid` passthrough. Renders `<button>` or `<a>`/`<Link>`.
- **TextField.tsx** — port of `x-field` + `.field-input`: label (with required `*`),
  input slot, error `<p role="alert" id="{name}-error">`, `aria-invalid`/`aria-describedby`
  wiring. Used by all auth forms.
- **GoogleButton.tsx** — outline button with the Google `G` SVG (inline), links to
  `/login/google` (a full navigation, not Inertia — it is an external OAuth redirect, so
  a plain `<a href>`).

### Theming / tokens

No new tokens. Reuse `resources/css/tokens.css` (warm palette + `.dark`) and the
semantic Tailwind bridge (`bg-primary`, `text-fg`, `border-strong`, `field-input`).
Tailwind `content` already globs `resources/js/**/*.js` — **must be widened to include
`.tsx`** (`resources/js/**/*.{js,ts,jsx,tsx}`) or the new classes get purged. Verify
`preflight:false` doesn't break the form panel (the `theme-default` wrapper supplies the
scoped reset).

## Must preserve (regression surface — do not change behavior)

1. **Double-hash guard.** `ResetPasswordController::setUserPassword()` passes PLAINTEXT
   (User mutator hashes). Same single-hash path in `UserRegistrationService` and social
   signup. Covered by `PasswordResetLoginTest`.
2. **Email-or-username login.** `LoginController::credentials()` picks `email` vs
   `username` column via `FILTER_VALIDATE_EMAIL`. Field stays named `email`.
3. **Social account-takeover guard**, `role_id=2` default for new users, **membership
   gating** on social signup (`LoginController` membership check), email verification
   (`Registered` event → notification when `email_verification` setting on).
4. **`registration_enabled` middleware** on both register routes (form + POST).
5. **`data-testid="login-username|login-password|login-submit"`** on the React login page
   — `tests/Browser/AuthAdminTest.php` (CI `e2e`, `BROWSER_TESTS=1`) fills/clicks them.
6. **Route names** `login`, `logout`, `register`, `password.request`, `password.email`,
   `password.reset`, `password.update`, `verification.notice`, `verification.verify`,
   `verification.resend` — unchanged (redirects and `route()` calls across the app rely
   on them).
7. **Custom logout alias** `GET /logout` → `cpanel-logout` (`web.php:16`) stays.

## Testing plan (TDD, all three locales where copy is asserted)

**Backend (Pest Feature, `AssertableInertia`):**
- Rewrite `AuthFlowTest` "page renders 200" assertions to assert the Inertia **component**
  (`->component('auth/Login')` etc.) and key props; keep all POST-behavior assertions
  (login success/failure, logout, register success/validation, password-reset
  notification) exactly as they are — they are transport-agnostic and must stay green.
- New `tests/Feature/Auth/AuthInertiaRenderTest.php`: each GET renders the right
  component with the right props (`canResetPassword`, `status`, `token`/`email` on reset,
  `membershipEnabled` on login) on en/de/ru.
- FormRequest unit/feature coverage: each request's rules (valid passes, invalid 422 with
  the expected error keys). Register validation, reset validation, forgot validation.
- Keep green as-is: `EmailVerificationTest`, `MembershipToggleTest`,
  `PasswordResetLoginTest`, `SocialAuthServiceTest`, `SocialMembershipAndVerificationTest`,
  `UserRegistrationServiceTest`, `SocialLoginLinkingTest`, `ChangePasswordRequestTest`.
  Social tests call controller methods directly and are provider-agnostic → safe.
- New: assert `/login/google` route resolves and `/login/github` (and facebook/linkedin)
  now **404** (constraint narrowed to `google`).
- New: `config('services.google')` present; facebook/github/linkedin **absent**.

**Frontend (Vitest + RTL):** render each of the 5 pages inside a test wrapper that mocks
`@inertiajs/react` `useForm`/`usePage` and stubs `Head`/`Link` (same pattern as
`Demo.test.tsx`). Assert: fields render with labels, the three login `data-testid`s exist,
the GoogleButton links to `/login/google`, errors render from the `errors` prop, `t()`
strings resolve (not raw keys). Test `AuthLayout` renders brand panel + children and
applies `.dark` from `localStorage`.

**Browser (Pest, unchanged):** `AuthAdminTest` login-by-testid passes against the React
login page with no edit — the `data-testid`s are reproduced.

**laravel/ui removal verification:** after the composer removal, `composer install` clean,
full suite green, `grep -r "laravel/ui\|Auth::routes\|AuthenticatesUsers\|RegistersUsers\|
SendsPasswordResetEmails\|ResetsPasswords\|VerifiesEmails" app/ routes/ composer.json`
returns nothing.

## Task decomposition (≈10–12 TDD tasks, order)

1. `routes/auth.php` scaffold + delete `Auth::routes()` line; keep controllers rendering
   Blade temporarily (routes point at new controller methods that still
   `Inertia::render` is not ready) — **actually** fold this into task 2 to avoid a broken
   intermediate. (Right-size during planning.)
2. FormRequests (Login/Register/ForgotPassword/ResetPassword) + tests.
3. LoginController thin rewrite (showLoginForm + login + logout) → Inertia + `routes/auth.php`
   wired; `AuthFlowTest` login/logout converted; render test.
4. RegisterController thin rewrite (+ `Registered` event) + tests.
5. Forgot/Reset controllers thin rewrite (+ preserve `setUserPassword`) + tests.
6. VerificationController thin rewrite + tests.
7. Google-only: `config/services.php` swap + route constraint narrow + tests (old
   providers 404, google present).
8. Remove `laravel/ui` from composer + drop the five traits usage confirmed gone +
   removal-verification test/grep.
9. Frontend shared components: Button, TextField, GoogleButton + Vitest.
10. AuthLayout (split-screen + theme bootstrap) + Vitest.
11. The 5 React pages + Vitest each; wire `Inertia::render` component names to match.
12. Full verification: backend suite, vitest, `npm run build`, `tsc --noEmit`, browser
    testids sanity, laravel/ui grep clean.

(Backend controller tasks precede/parallel the React page tasks; the pages are what the
converted render tests ultimately assert against via `AssertableInertia` component names,
so component-name strings must be agreed up front: `auth/Login`, `auth/Register`,
`auth/ForgotPassword`, `auth/ResetPassword`, `auth/VerifyEmail`.)

## Resolved decisions (2026-07-25)

- **Login throttle:** inline the framework-core `ThrottlesLogins` trait for full parity
  (5 attempts / 1 min, keyed on email+IP, `trans('auth.throttle')` lockout message). See
  the LoginController description above. Add a test asserting lockout after 5 failed
  attempts.
- **Theme toggle:** no visible toggle on auth pages — `AuthLayout` silently honors the
  stored theme (`localStorage['agentic-cms-theme']`, fallback `prefers-color-scheme`).
  The public shell already owns the visible toggle.
- **Component-name casing:** `auth/Login`, `auth/Register`, `auth/ForgotPassword`,
  `auth/ResetPassword`, `auth/VerifyEmail` (matches the `resolvePageComponent` glob
  `./pages/${name}.tsx` → `pages/auth/Login.tsx`).

## Out of scope (later phases)

Admin cpanel (Phase 3), public + SSR (Phase 4), Blade view deletion + `social.blade.php`
removal (Phase 5). Prefetch wiring lands with the nav components in Phase 3/4, not here.
