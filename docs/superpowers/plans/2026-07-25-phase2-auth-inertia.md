# Phase 2 — Auth on Inertia + React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the five auth screens to Inertia + React, fully removing `laravel/ui`, on the project's warm brand tokens (light + dark), with Google-only social login.

**Architecture:** Hand-written `routes/auth.php` replaces the `Auth::routes()` macro. Controllers become thin: GET → `Inertia::render`, POST → existing domain service + redirect. Validation moves into FormRequests. React pages use `useForm`; copy comes through the Phase-1 `t()` pipeline. Domain services (`SocialAuthService`, `UserRegistrationService`, `PasswordResetService`, `UserRepository`) are untouched.

**Tech Stack:** Laravel 12 / PHP 8.3, Inertia (inertiajs/inertia-laravel), React 19 + TS, Vite, Tailwind (semantic token bridge), react-i18next, Pest (Feature/Unit/Browser), Vitest + RTL.

**Spec:** `docs/superpowers/specs/2026-07-25-phase2-auth-inertia-design.md`

## Global Constraints

- **Preserve route names** verbatim: `login`, `logout`, `register`, `password.request`, `password.email`, `password.reset`, `password.update`, `verification.notice`, `verification.verify`, `verification.resend`. Other code calls `route()` with these.
- **Preserve `data-testid`** on the React login page: `login-username`, `login-password`, `login-submit` (Pest browser suite `tests/Browser/AuthAdminTest.php` depends on them). The username field is `name="email"`.
- **Double-hash guard:** password assignment passes PLAINTEXT (User mutator hashes once). Never call `Hash::make()` in reset/register/social paths. Regression: `tests/Feature/Auth/PasswordResetLoginTest.php` must stay green.
- **Email-or-username login:** keep `LoginController::credentials()` (`FILTER_VALIDATE_EMAIL` → `email` vs `username` column). Login field stays named `email`.
- **Login throttle parity:** inline framework-core `Illuminate\Foundation\Auth\ThrottlesLogins` (5 attempts / 1 min, keyed email+IP, `auth.throttle` message).
- **Preserve social guards:** account-takeover guard, `role_id` DB-default for new users, membership gating on social signup, `SocialEmailNotVerifiedException` handling, email verification via `Registered` event. All live in `SocialAuthService`/`LoginController::handleProviderCallback` — do not change their logic.
- **`registration_enabled` middleware** stays on both register routes (GET + POST).
- **Custom logout alias** `GET /logout` → `cpanel-logout` (`routes/web.php:16`) stays.
- **Component names** (for `Inertia::render` ↔ `resolvePageComponent`): `auth/Login`, `auth/Register`, `auth/ForgotPassword`, `auth/ResetPassword`, `auth/VerifyEmail` → `resources/js/pages/auth/{Login,Register,ForgotPassword,ResetPassword,VerifyEmail}.tsx`.
- **No visible theme toggle** on auth pages; `AuthLayout` silently honors `localStorage['agentic-cms-theme']` (fallback `prefers-color-scheme`), toggling `.dark` on `<html>`.
- **Tests must stay green throughout:** `AuthFlowTest`, `EmailVerificationTest`, `MembershipToggleTest`, `PasswordResetLoginTest`, `SocialAuthServiceTest`, `SocialMembershipAndVerificationTest`, `UserRegistrationServiceTest`, `SocialLoginLinkingTest`, `ChangePasswordRequestTest`. Their assertions are transport-agnostic; a `get('/login')` returning an Inertia 200 keeps `assertStatus(200)` green.
- **TDD, DRY, YAGNI, frequent commits.** Each task: red → green → refactor → commit.

## File Structure

- `routes/auth.php` — new; 12 auth routes (replaces `Auth::routes()`).
- `app/Http/Requests/Auth/{LoginRequest,RegisterRequest,ForgotPasswordRequest,ResetPasswordRequest}.php` — new.
- `app/Http/Controllers/Auth/{LoginController,RegisterController,ForgotPasswordController,ResetPasswordController,VerificationController}.php` — rewritten thin (traits dropped incrementally).
- `config/services.php` — google in, facebook/github/linkedin out.
- `composer.json` — remove `laravel/ui`.
- `resources/js/components/{Button,TextField,GoogleButton}.tsx` (+ `.test.tsx`) — new.
- `resources/js/layouts/AuthLayout.tsx` (+ `.test.tsx`) — new.
- `resources/js/pages/auth/{Login,Register,ForgotPassword,ResetPassword,VerifyEmail}.tsx` (+ `.test.tsx`) — new.
- `tailwind.config.js` — widen `content` to include `.tsx`.
- Tests: `tests/Feature/Auth/AuthInertiaRenderTest.php`, `tests/Feature/Auth/LoginThrottleTest.php`, `tests/Feature/Auth/GoogleOnlySocialTest.php`, `tests/Unit/Http/Requests/Auth/*RequestTest.php` (or Feature), `tests/Feature/Auth/LaravelUiRemovedTest.php`.

## Execution ordering rationale

Routing is decoupled from the Blade→Inertia swap: **Task 2** reproduces `Auth::routes()` as `routes/auth.php` pointing at the SAME method names the traits already provide (`showLoginForm`, `login`, `register`, …), so the app is byte-for-byte identical and green while still trait-backed. Tasks 3–6 then swap one controller at a time (drop trait, add Inertia render + POST body) — routes/auth.php is unchanged because method names are identical. Backend swaps are verified via `AssertableInertia` (the server returns a 200 Inertia page object even before the React component file exists; the component resolves client-side). React pages (Tasks 9–11) land before the browser suite runs in Task 12.

---

### Task 1: Auth FormRequests

**Files:**
- Create: `app/Http/Requests/Auth/LoginRequest.php`, `RegisterRequest.php`, `ForgotPasswordRequest.php`, `ResetPasswordRequest.php`
- Test: `tests/Feature/Auth/FormRequestValidationTest.php`

**Interfaces:**
- Produces: four `FormRequest` classes with `authorize(): true` and `rules(): array`, consumed by controllers in Tasks 3–6.

- [ ] **Step 1: Write the failing test** — `tests/Feature/Auth/FormRequestValidationTest.php`

```php
<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    private function passes(string $requestClass, array $data): bool
    {
        return Validator::make($data, (new $requestClass)->rules())->passes();
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->assertTrue($this->passes(LoginRequest::class, ['email' => 'a@b.com', 'password' => 'x']));
        $this->assertFalse($this->passes(LoginRequest::class, ['email' => '', 'password' => '']));
    }

    public function test_forgot_password_requires_valid_email(): void
    {
        $this->assertTrue($this->passes(ForgotPasswordRequest::class, ['email' => 'a@b.com']));
        $this->assertFalse($this->passes(ForgotPasswordRequest::class, ['email' => 'not-an-email']));
    }

    public function test_reset_password_requires_token_email_and_confirmed_password(): void
    {
        $this->assertTrue($this->passes(ResetPasswordRequest::class, [
            'token' => 't', 'email' => 'a@b.com', 'password' => 'password1', 'password_confirmation' => 'password1',
        ]));
        $this->assertFalse($this->passes(ResetPasswordRequest::class, [
            'token' => 't', 'email' => 'a@b.com', 'password' => 'password1', 'password_confirmation' => 'nope',
        ]));
    }

    public function test_register_rules_match_legacy_validator(): void
    {
        $this->assertFalse($this->passes(RegisterRequest::class, [
            'name' => '', 'username' => '', 'email' => 'bad', 'password' => 'short', 'password_confirmation' => 'x',
        ]));
        $this->assertTrue($this->passes(RegisterRequest::class, [
            'name' => 'A', 'username' => 'abc', 'email' => 'fresh@example.com',
            'password' => 'password1', 'password_confirmation' => 'password1',
        ]));
    }

    public function test_all_requests_authorize(): void
    {
        foreach ([LoginRequest::class, RegisterRequest::class, ForgotPasswordRequest::class, ResetPasswordRequest::class] as $class) {
            $this->assertTrue((new $class)->authorize());
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FormRequestValidationTest`
Expected: FAIL (classes not found).

- [ ] **Step 3: Write the four FormRequests**

`app/Http/Requests/Auth/LoginRequest.php`:
```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        // The field is named `email` but accepts a username too; the controller's
        // credentials() decides which column to match. Keep validation permissive.
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
```

`app/Http/Requests/Auth/RegisterRequest.php` (rules verbatim from the old `RegisterController::validator()`):
```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:10', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
```

`app/Http/Requests/Auth/ForgotPasswordRequest.php`:
```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
```

`app/Http/Requests/Auth/ResetPasswordRequest.php`:
```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=FormRequestValidationTest`
Expected: PASS (5 tests).

- [ ] **Step 5: Lint + commit**

```bash
./vendor/bin/pint app/Http/Requests/Auth tests/Feature/Auth/FormRequestValidationTest.php
git add app/Http/Requests/Auth tests/Feature/Auth/FormRequestValidationTest.php
git commit -m "feat(auth): add auth FormRequests (login/register/forgot/reset)"
```

---

### Task 2: routes/auth.php replaces Auth::routes() (still trait-backed, app identical)

**Files:**
- Create: `routes/auth.php`
- Modify: `routes/web.php:14` (delete `Auth::routes(['verify' => true]);`, add `require __DIR__.'/auth.php';`)
- Test: `tests/Feature/Auth/AuthRoutesResolveTest.php`

**Interfaces:**
- Consumes: existing trait methods on the current controllers (`showLoginForm`, `login`, `logout`, `showRegistrationForm`, `register`, `showLinkRequestForm`, `sendResetLinkEmail`, `showResetForm`, `reset`, `show`, `verify`, `resend`).
- Produces: the 12 named routes other tasks/controllers rely on.

- [ ] **Step 1: Write the failing test** — asserts every auth route name still resolves after the macro is replaced.

```php
<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthRoutesResolveTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function authRouteNames(): array
    {
        return [
            'login' => ['login'],
            'logout' => ['logout'],
            'register' => ['register'],
            'password.request' => ['password.request'],
            'password.email' => ['password.email'],
            'password.reset' => ['password.reset'],
            'password.update' => ['password.update'],
            'verification.notice' => ['verification.notice'],
            'verification.verify' => ['verification.verify'],
            'verification.resend' => ['verification.resend'],
        ];
    }

    /**
     * @dataProvider authRouteNames
     */
    public function test_auth_route_is_registered(string $name): void
    {
        $this->assertTrue(Route::has($name), "Route [$name] must exist");
    }

    public function test_login_page_still_renders(): void
    {
        $this->get('/login')->assertStatus(200);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

First delete `Auth::routes(['verify' => true]);` from `routes/web.php` line 14 WITHOUT adding `routes/auth.php` yet, run the test → routes missing (FAIL). (This proves the macro was the source.)

Run: `php artisan test --filter=AuthRoutesResolveTest`
Expected: FAIL (routes not found).

- [ ] **Step 3: Create `routes/auth.php`** and wire it in.

`routes/auth.php`:
```php
<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| Hand-written replacement for the laravel/ui Auth::routes(['verify'=>true])
| macro. Same URIs and route names; method names match the current controller
| methods so this file is valid both before and after the Blade->Inertia swap.
*/

// Login / logout
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Registration
Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

// Password reset
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

// Email verification
Route::get('email/verify', [VerificationController::class, 'show'])->name('verification.notice');
Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
Route::post('email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
```

In `routes/web.php`, replace line 14 `Auth::routes(['verify' => true]);` with:
```php
require __DIR__.'/auth.php';
```

**Note:** the trait `login`/`register`/etc. methods do NOT have the route-name-less POST variants declared by attributes; the traits just expose public methods. `Route::post('login', [LoginController::class, 'login'])` binds fine to the `AuthenticatesUsers::login()` trait method (it is a public method on the controller instance). Same for the others. The `verify` route needs the `signed` + `throttle` middleware the trait's constructor already applies via `$this->middleware('signed')->only('verify')` and `throttle:6,1` — those are controller-level middleware, still active. Guest middleware likewise stays controller-level.

- [ ] **Step 4: Run test to verify it passes** — and the full existing auth suite is unchanged.

Run: `php artisan test --filter=AuthRoutesResolveTest`
Expected: PASS.
Run: `php artisan test --filter=AuthFlowTest`
Expected: PASS (unchanged — routes are byte-identical in behavior).

- [ ] **Step 5: Commit**

```bash
git add routes/auth.php routes/web.php tests/Feature/Auth/AuthRoutesResolveTest.php
git commit -m "refactor(auth): replace Auth::routes() macro with hand-written routes/auth.php"
```

---

### Task 3: LoginController → Inertia + inline ThrottlesLogins

**Files:**
- Modify: `app/Http/Controllers/Auth/LoginController.php`
- Test: `tests/Feature/Auth/AuthInertiaRenderTest.php` (login part), `tests/Feature/Auth/LoginThrottleTest.php`

**Interfaces:**
- Consumes: `LoginRequest` (Task 1), `SocialAuthService` (unchanged), `ThrottlesLogins` (framework core).
- Produces: `Inertia::render('auth/Login', props)`; props `status`, `canResetPassword`, `membershipEnabled`.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/Auth/AuthInertiaRenderTest.php` (start with the login case; later tasks extend this file):
```php
<?php

namespace Tests\Feature\Auth;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AuthInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_renders_inertia_component_with_props(): void
    {
        $this->get('/login')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('auth/Login')
                ->has('canResetPassword')
                ->has('membershipEnabled')
        );
    }
}
```

`tests/Feature/Auth/LoginThrottleTest.php`:
```php
<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        RateLimiter::clear('');
    }

    public function test_lockout_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['role_id' => 2, 'password' => 'secret123']);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'wrong',
            ])->assertSessionHasErrors('email');
        }

        // 6th attempt is locked out; the error message is the throttle string.
        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts',
            session('errors')->first('email')
        );
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter="AuthInertiaRenderTest|LoginThrottleTest"`
Expected: FAIL (component is not `auth/Login`; no lockout).

- [ ] **Step 3: Rewrite `LoginController`**

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\SocialAuthService;
use App\Services\Auth\SocialEmailNotVerifiedException;
use Auth;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class LoginController extends Controller
{
    use ThrottlesLogins;

    protected $redirectTo = '/';

    public function __construct(private SocialAuthService $socialAuth)
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm(): InertiaResponse
    {
        return Inertia::render('auth/Login', [
            'status' => session('status'),
            'canResetPassword' => \Route::has('password.request'),
            'membershipEnabled' => (bool) get_general_settings('membership'),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            $this->sendLockoutResponse($request); // throws ValidationException
        }

        if (Auth::attempt($this->credentials($request), $request->boolean('remember'))) {
            $request->session()->regenerate();
            $this->clearLoginAttempts($request);

            return redirect()->intended($this->redirectTo);
        }

        $this->incrementLoginAttempts($request);

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * The field the login form submits and the throttle key are both `email`.
     * credentials() still decides email-vs-username at attempt time.
     */
    public function username(): string
    {
        return 'email';
    }

    public function redirectToProvider($provider): SymfonyRedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider): RedirectResponse
    {
        $socialUser = Socialite::driver($provider)->user();

        try {
            $authUser = $this->socialAuth->findOrLink($socialUser, $provider);
        } catch (SocialEmailNotVerifiedException $e) {
            return redirect()->route('login')
                ->with('status', trans('default/auth.social_email_unverified'));
        }

        if ($authUser) {
            Auth::login($authUser, true);

            return redirect($this->redirectTo);
        }

        if (! get_general_settings('membership')) {
            return redirect()->route('login')
                ->with('status', trans('default/auth.registration_disabled'));
        }

        $validator = $this->socialAuth->validateNew($socialUser);

        if ($validator !== true) {
            return redirect('login')
                ->withErrors($validator)
                ->withInput();
        }

        $registered_user = $this->socialAuth->create($socialUser, $provider);
        Auth::login($registered_user, true);

        return redirect($this->redirectTo);
    }

    protected function credentials(Request $request): array
    {
        $field = filter_var($request->get($this->username()), FILTER_VALIDATE_EMAIL)
            ? $this->username()
            : 'username';

        return [
            $field => $request->get($this->username()),
            'password' => $request->password,
        ];
    }
}
```

**Notes:** `use Auth;`, `use Socialite;`, `use \Route` facade — match the existing file's facade-alias style (the original used bare `Auth`/`Socialite`). Use the fully-qualified `\Route::has(...)` or add `use Route;` to satisfy Pint's `fully_qualified_strict_types`; prefer adding `use Illuminate\Support\Facades\Route;` and calling `Route::has()`. Adjust imports until `./vendor/bin/pint --test` is clean.

- [ ] **Step 4: Run tests to verify they pass** — plus `AuthFlowTest` login/logout unchanged.

Run: `php artisan test --filter="AuthInertiaRenderTest|LoginThrottleTest|AuthFlowTest"`
Expected: PASS.

- [ ] **Step 5: Lint + commit**

```bash
./vendor/bin/pint app/Http/Controllers/Auth/LoginController.php tests/Feature/Auth
git add app/Http/Controllers/Auth/LoginController.php tests/Feature/Auth/AuthInertiaRenderTest.php tests/Feature/Auth/LoginThrottleTest.php
git commit -m "feat(auth): LoginController renders Inertia + inline ThrottlesLogins"
```

---

### Task 4: RegisterController → Inertia + Registered event

**Files:**
- Modify: `app/Http/Controllers/Auth/RegisterController.php`
- Test: `tests/Feature/Auth/AuthInertiaRenderTest.php` (add register case)

**Interfaces:**
- Consumes: `RegisterRequest` (Task 1), `UserRegistrationService` (unchanged).
- Produces: `Inertia::render('auth/Register')`; POST creates user, fires `Registered`, logs in, redirects `/`.

- [ ] **Step 1: Add the failing render test** to `AuthInertiaRenderTest`:

```php
    public function test_register_renders_inertia_component(): void
    {
        $this->get('/register')->assertInertia(
            fn (AssertableInertia $page) => $page->component('auth/Register')
        );
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AuthInertiaRenderTest`
Expected: FAIL (register still Blade — component mismatch).

- [ ] **Step 3: Rewrite `RegisterController`**

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\UserRegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class RegisterController extends Controller
{
    protected $redirectTo = '/';

    public function __construct(private UserRegistrationService $registrar)
    {
        $this->middleware('guest');
        $this->middleware('registration_enabled');
    }

    public function showRegistrationForm(): InertiaResponse
    {
        return Inertia::render('auth/Register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = $this->registrar->register($request->validated());

        event(new Registered($user));

        Auth::login($user);

        return redirect($this->redirectTo);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass** — `AuthInertiaRenderTest`, `AuthFlowTest` (register cases), `EmailVerificationTest` (Registered event still fires the verification mail when enabled), `MembershipToggleTest`.

Run: `php artisan test --filter="AuthInertiaRenderTest|AuthFlowTest|EmailVerificationTest|MembershipToggleTest"`
Expected: PASS.

- [ ] **Step 5: Lint + commit**

```bash
./vendor/bin/pint app/Http/Controllers/Auth/RegisterController.php
git add app/Http/Controllers/Auth/RegisterController.php tests/Feature/Auth/AuthInertiaRenderTest.php
git commit -m "feat(auth): RegisterController renders Inertia, keeps Registered event"
```

---

### Task 5: Forgot + Reset password controllers → Inertia

**Files:**
- Modify: `app/Http/Controllers/Auth/ForgotPasswordController.php`, `ResetPasswordController.php`
- Test: `tests/Feature/Auth/AuthInertiaRenderTest.php` (add forgot + reset cases)

**Interfaces:**
- Consumes: `ForgotPasswordRequest`, `ResetPasswordRequest` (Task 1), `PasswordResetService` (unchanged), framework `Password` broker.
- Produces: `Inertia::render('auth/ForgotPassword', ['status'])` and `Inertia::render('auth/ResetPassword', ['token','email'])`.

- [ ] **Step 1: Add failing render tests** to `AuthInertiaRenderTest`:

```php
    public function test_forgot_password_renders_inertia_component(): void
    {
        $this->get('/password/reset')->assertInertia(
            fn (AssertableInertia $page) => $page->component('auth/ForgotPassword')
        );
    }

    public function test_reset_password_renders_inertia_component_with_token(): void
    {
        $this->get('/password/reset/sample-token?email=a@b.com')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('auth/ResetPassword')
                ->where('token', 'sample-token')
                ->where('email', 'a@b.com')
        );
    }
```

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --filter=AuthInertiaRenderTest`
Expected: FAIL (forgot/reset still Blade).

- [ ] **Step 3: Rewrite both controllers**

`app/Http/Controllers/Auth/ForgotPasswordController.php`:
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ForgotPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showLinkRequestForm(): InertiaResponse
    {
        return Inertia::render('auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    public function sendResetLinkEmail(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', trans($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => trans($status)]);
    }
}
```

`app/Http/Controllers/Auth/ResetPasswordController.php` (KEEP `setUserPassword`):
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Models\User;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordResetService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ResetPasswordController extends Controller
{
    protected $redirectTo = '/';

    public function __construct(private PasswordResetService $passwordReset)
    {
        $this->middleware('guest');
    }

    public function showResetForm(Request $request, string $token): InertiaResponse
    {
        return Inertia::render('auth/ResetPassword', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $this->setUserPassword($user, $password);
                $user->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));

                Auth::login($user);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect($this->redirectTo)->with('status', trans($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => trans($status)]);
    }

    /**
     * Assign the new password as PLAINTEXT — User's setPasswordAttribute mutator
     * hashes once. Calling Hash::make() here would double-hash and break login.
     */
    protected function setUserPassword(User $user, string $password): void
    {
        $this->passwordReset->setPassword($user, $password);
    }
}
```

- [ ] **Step 4: Run tests** — render tests, `AuthFlowTest` (password reset request), and the critical `PasswordResetLoginTest`.

Run: `php artisan test --filter="AuthInertiaRenderTest|AuthFlowTest|PasswordResetLoginTest"`
Expected: PASS (double-hash regression stays green).

- [ ] **Step 5: Lint + commit**

```bash
./vendor/bin/pint app/Http/Controllers/Auth/ForgotPasswordController.php app/Http/Controllers/Auth/ResetPasswordController.php
git add app/Http/Controllers/Auth/ForgotPasswordController.php app/Http/Controllers/Auth/ResetPasswordController.php tests/Feature/Auth/AuthInertiaRenderTest.php
git commit -m "feat(auth): Forgot/Reset password controllers render Inertia (keep double-hash guard)"
```

---

### Task 6: VerificationController → Inertia

**Files:**
- Modify: `app/Http/Controllers/Auth/VerificationController.php`
- Test: `tests/Feature/Auth/AuthInertiaRenderTest.php` (add verify case)

**Interfaces:**
- Consumes: framework `EmailVerificationRequest`.
- Produces: `Inertia::render('auth/VerifyEmail', ['status'])`.

- [ ] **Step 1: Add failing render test** to `AuthInertiaRenderTest`:

```php
    public function test_verify_notice_renders_inertia_for_unverified_user(): void
    {
        $user = \App\Http\Models\User::factory()->create([
            'role_id' => 2,
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)->get('/email/verify')->assertInertia(
            fn (AssertableInertia $page) => $page->component('auth/VerifyEmail')
        );
    }
```

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --filter=AuthInertiaRenderTest`
Expected: FAIL (verify still Blade).

- [ ] **Step 3: Rewrite `VerificationController`**

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class VerificationController extends Controller
{
    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    public function show(Request $request): InertiaResponse|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect($this->redirectTo)
            : Inertia::render('auth/VerifyEmail', [
                'status' => $request->session()->get('resent') ? 'resent' : null,
            ]);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->redirectTo);
        }

        $request->fulfill();

        return redirect($this->redirectTo)->with('verified', true);
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->redirectTo);
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('resent', true);
    }
}
```

- [ ] **Step 4: Run tests** — render test + `EmailVerificationTest` (full verify flow must stay green).

Run: `php artisan test --filter="AuthInertiaRenderTest|EmailVerificationTest"`
Expected: PASS.

- [ ] **Step 5: Lint + commit**

```bash
./vendor/bin/pint app/Http/Controllers/Auth/VerificationController.php
git add app/Http/Controllers/Auth/VerificationController.php tests/Feature/Auth/AuthInertiaRenderTest.php
git commit -m "feat(auth): VerificationController renders Inertia"
```

---

### Task 7: Google-only social login (config + route constraint)

**Files:**
- Modify: `config/services.php`, `routes/web.php:276-277`
- Test: `tests/Feature/Auth/GoogleOnlySocialTest.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: `config('services.google')`; `/login/google` resolves; other providers 404.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class GoogleOnlySocialTest extends TestCase
{
    public function test_google_service_is_configured(): void
    {
        $this->assertIsArray(config('services.google'));
        $this->assertArrayHasKey('redirect', config('services.google'));
    }

    public function test_removed_providers_are_not_configured(): void
    {
        $this->assertNull(config('services.facebook'));
        $this->assertNull(config('services.github'));
        $this->assertNull(config('services.linkedin'));
    }

    public function test_google_provider_route_resolves(): void
    {
        // The route exists (constraint allows google). It will try to hit
        // Socialite; we only assert it is NOT a 404 (route matched).
        $this->get('/login/google')->assertStatus(302); // Socialite redirect OR our redirect; not 404
    }

    /**
     * @dataProvider removedProviders
     */
    public function test_removed_provider_routes_404(string $provider): void
    {
        $this->get("/login/$provider")->assertNotFound();
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function removedProviders(): array
    {
        return [['github'], ['facebook'], ['linkedin'], ['twitter']];
    }
}
```

**Note on `test_google_provider_route_resolves`:** without valid Google creds Socialite may throw rather than 302. If it throws in the test env, assert the route matched a different way: `$this->get('/login/google')->assertStatus(500)` is NOT acceptable. Instead assert the route is registered: replace the body with `$this->assertTrue(collect(app('router')->getRoutes())->contains(fn ($r) => $r->uri() === 'login/{provider}'));` and that `google` passes the constraint via `app('router')->getRoutes()->match(...)`. The implementer should pick whichever cleanly proves "google matches, others 404" without depending on live OAuth. Keep the 404 assertions for removed providers as the primary guarantee.

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --filter=GoogleOnlySocialTest`
Expected: FAIL (google absent; removed providers still match).

- [ ] **Step 3: Edit `config/services.php`** — remove `facebook`, `github`, `linkedin`; add `google`:

```php
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_CALLBACK_URL'),
    ],
```

Edit `routes/web.php` lines 276-277: change both `->where('provider', 'twitter|facebook|linkedin|google|github')` to `->where('provider', 'google')`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=GoogleOnlySocialTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add config/services.php routes/web.php tests/Feature/Auth/GoogleOnlySocialTest.php
git commit -m "feat(auth): Google-only social login (drop facebook/github/linkedin)"
```

Also add `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_CALLBACK_URL` to `.env.example` if present (and remove the old provider keys). Include that edit in this commit.

---

### Task 8: Remove laravel/ui

**Files:**
- Modify: `composer.json` (remove `laravel/ui`)
- Test: `tests/Feature/Auth/LaravelUiRemovedTest.php`

**Interfaces:** none (removal only). Precondition: Tasks 3–6 dropped every `use *Users`/`ResetsPasswords`/`VerifiesEmails`/`SendsPasswordResetEmails` trait.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LaravelUiRemovedTest extends TestCase
{
    public function test_no_controller_uses_laravel_ui_auth_traits(): void
    {
        $files = glob(app_path('Http/Controllers/Auth/*.php'));
        foreach ($files as $file) {
            $src = file_get_contents($file);
            foreach ([
                'AuthenticatesUsers', 'RegistersUsers', 'SendsPasswordResetEmails',
                'ResetsPasswords', 'VerifiesEmails',
            ] as $trait) {
                $this->assertStringNotContainsString(
                    $trait, $src, "$file must not use the laravel/ui trait $trait"
                );
            }
        }
    }

    public function test_composer_json_has_no_laravel_ui(): void
    {
        $composer = file_get_contents(base_path('composer.json'));
        $this->assertStringNotContainsString('laravel/ui', $composer);
    }
}
```

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --filter=LaravelUiRemovedTest`
Expected: `test_composer_json_has_no_laravel_ui` FAILs (still present); the trait test should already pass if Tasks 3–6 are done.

- [ ] **Step 3: Remove the dependency**

```bash
composer remove laravel/ui
```

If `composer remove` fails on lockfile resolution, manually delete the `"laravel/ui": "^4.0"` line from `composer.json` require, then `composer update laravel/ui --no-install` is wrong — instead run `composer update --lock` to refresh the lock without installing new versions of others, or `composer install`. The implementer verifies `composer install` completes clean and no `laravel/ui` remains in `composer.lock`.

- [ ] **Step 4: Run test + full auth suite**

Run: `php artisan test --filter=LaravelUiRemovedTest`
Expected: PASS.
Run: `php artisan test --testsuite=Feature`
Expected: PASS (whole Feature suite green without laravel/ui).

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock
git commit -m "chore(auth): remove laravel/ui dependency (traits fully replaced)"
```

---

### Task 9: React shared components (Button, TextField, GoogleButton)

**Files:**
- Create: `resources/js/components/Button.tsx`, `TextField.tsx`, `GoogleButton.tsx`
- Test: `resources/js/components/Button.test.tsx`, `TextField.test.tsx`, `GoogleButton.test.tsx`
- Modify: `tailwind.config.js` (widen `content` to `.tsx`)

**Interfaces:**
- Produces: `Button` (variant/size/loading/`data-testid`), `TextField` (label/name/error/required + input via children or props), `GoogleButton` (href `/login/google`). Consumed by AuthLayout + pages (Tasks 10–11).

- [ ] **Step 1: Widen Tailwind content glob** so `.tsx` classes are not purged. In `tailwind.config.js` `content`, change `'./resources/js/**/*.js'` to `'./resources/js/**/*.{js,ts,jsx,tsx}'`.

- [ ] **Step 2: Write failing tests**

`resources/js/components/Button.test.tsx`:
```tsx
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { Button } from './Button';

describe('Button', () => {
    it('renders its label and passes data-testid', () => {
        render(<Button data-testid="login-submit">Sign in</Button>);
        const btn = screen.getByTestId('login-submit');
        expect(btn).toHaveTextContent('Sign in');
        expect(btn.tagName).toBe('BUTTON');
    });

    it('renders an anchor when href is set', () => {
        render(<Button href="/x">Go</Button>);
        expect(screen.getByRole('link', { name: 'Go' })).toHaveAttribute('href', '/x');
    });

    it('marks aria-busy when loading', () => {
        render(<Button loading>Save</Button>);
        expect(screen.getByRole('button')).toHaveAttribute('aria-busy', 'true');
    });
});
```

`resources/js/components/TextField.test.tsx`:
```tsx
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { TextField } from './TextField';

describe('TextField', () => {
    it('renders a label bound to the input', () => {
        render(<TextField name="email" label="Email" />);
        expect(screen.getByLabelText('Email')).toHaveAttribute('name', 'email');
    });

    it('renders an error with role=alert and wires aria-describedby', () => {
        render(<TextField name="email" label="Email" error="Required" />);
        expect(screen.getByRole('alert')).toHaveTextContent('Required');
        expect(screen.getByLabelText('Email')).toHaveAttribute('aria-invalid', 'true');
    });
});
```

`resources/js/components/GoogleButton.test.tsx`:
```tsx
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { GoogleButton } from './GoogleButton';

describe('GoogleButton', () => {
    it('links to the google oauth entrypoint', () => {
        render(<GoogleButton>Continue with Google</GoogleButton>);
        expect(screen.getByRole('link', { name: /google/i })).toHaveAttribute('href', '/login/google');
    });
});
```

- [ ] **Step 3: Run to verify fail**

Run: `npx vitest run resources/js/components`
Expected: FAIL (modules not found).

- [ ] **Step 4: Implement the components**

`resources/js/components/Button.tsx` (port of `x-button`; semantic classes already exist):
```tsx
import type { ButtonHTMLAttributes, ReactNode } from 'react';

type Variant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'destructive';
type Size = 'sm' | 'md' | 'lg';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Variant;
    size?: Size;
    href?: string;
    loading?: boolean;
    children: ReactNode;
}

const VARIANT: Record<Variant, string> = {
    primary: 'bg-primary text-primary-contrast hover:bg-primary-hover',
    secondary: 'bg-surface-2 text-fg hover:bg-border',
    outline: 'border border-strong bg-transparent text-fg hover:bg-surface-2',
    ghost: 'bg-transparent text-fg hover:bg-surface-2',
    destructive: 'bg-error text-primary-contrast hover:opacity-90',
};

const SIZE: Record<Size, string> = {
    sm: 'h-8 px-3 text-sm',
    md: 'h-10 px-4 text-base',
    lg: 'h-12 px-6 text-lg',
};

const BASE =
    'inline-flex items-center justify-center gap-2 font-sans font-medium rounded-md transition-colors duration-[var(--dur-fast)] ease-[var(--ease-out)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 active:scale-[0.98] motion-reduce:active:scale-100 disabled:cursor-not-allowed disabled:opacity-50 disabled:pointer-events-none';

export function Button({
    variant = 'primary',
    size = 'md',
    href,
    loading = false,
    children,
    className = '',
    type = 'button',
    ...rest
}: ButtonProps) {
    const classes = `${BASE} ${VARIANT[variant]} ${SIZE[size]} ${className}`.trim();

    if (href) {
        // eslint-disable-next-line jsx-a11y/anchor-has-content
        return (
            <a href={href} className={classes} {...(rest as Record<string, unknown>)}>
                {children}
            </a>
        );
    }

    return (
        <button type={type} className={classes} aria-busy={loading || undefined} {...rest}>
            {loading && (
                <svg className="animate-spin shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            )}
            {children}
        </button>
    );
}
```

`resources/js/components/TextField.tsx` (port of `x-field` + `.field-input`):
```tsx
import type { InputHTMLAttributes } from 'react';

interface TextFieldProps extends InputHTMLAttributes<HTMLInputElement> {
    name: string;
    label?: string;
    error?: string | null;
    required?: boolean;
}

export function TextField({ name, label, error, required = false, className = '', ...rest }: TextFieldProps) {
    const hasError = Boolean(error);
    const descId = hasError ? `${name}-error` : undefined;

    return (
        <div className="flex flex-col gap-y-1.5">
            {label && (
                <label htmlFor={name} className="font-sans font-medium text-sm text-fg">
                    {label}
                    {required && (
                        <>
                            <span className="text-error ml-0.5" aria-hidden="true">*</span>
                            <span className="sr-only"> (required)</span>
                        </>
                    )}
                </label>
            )}
            <input
                id={name}
                name={name}
                required={required}
                aria-invalid={hasError || undefined}
                aria-describedby={descId}
                className={`field-input ${hasError ? 'border-[var(--error)]' : ''} ${className}`.trim()}
                {...rest}
            />
            {hasError && (
                <p id={descId} role="alert" className="text-xs text-error">
                    {error}
                </p>
            )}
        </div>
    );
}
```

`resources/js/components/GoogleButton.tsx`:
```tsx
import type { ReactNode } from 'react';
import { Button } from './Button';

export function GoogleButton({ children }: { children: ReactNode }) {
    return (
        <Button href="/login/google" variant="outline" size="md" className="w-full">
            <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.76h3.56c2.08-1.92 3.28-4.74 3.28-8.09Z" />
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.56-2.76c-.98.66-2.24 1.06-3.72 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23Z" />
                <path fill="#FBBC05" d="M5.84 14.09a6.6 6.6 0 0 1 0-4.18V7.07H2.18a11 11 0 0 0 0 9.86l3.66-2.84Z" />
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1A11 11 0 0 0 2.18 7.07l3.66 2.84C6.71 7.31 9.14 5.38 12 5.38Z" />
            </svg>
            {children}
        </Button>
    );
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `npx vitest run resources/js/components`
Expected: PASS (6 tests).

- [ ] **Step 6: Commit**

```bash
git add resources/js/components tailwind.config.js
git commit -m "feat(auth): React Button/TextField/GoogleButton components + widen tailwind glob"
```

---

### Task 10: AuthLayout (split-screen + theme bootstrap)

**Files:**
- Create: `resources/js/layouts/AuthLayout.tsx`, `resources/js/layouts/AuthLayout.test.tsx`

**Interfaces:**
- Consumes: nothing (pure layout). Reads `localStorage['agentic-cms-theme']`.
- Produces: `<AuthLayout title subtitle>{children}</AuthLayout>` — split-screen shell. Consumed by all 5 pages (Task 11).

- [ ] **Step 1: Write the failing test**

`resources/js/layouts/AuthLayout.test.tsx`:
```tsx
import { render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { AuthLayout } from './AuthLayout';

describe('AuthLayout', () => {
    beforeEach(() => {
        localStorage.clear();
        document.documentElement.classList.remove('dark');
    });
    afterEach(() => localStorage.clear());

    it('renders the brand wordmark and the children', () => {
        render(
            <AuthLayout title="Welcome back">
                <p>form here</p>
            </AuthLayout>,
        );
        expect(screen.getByText('Agentic CMS')).toBeInTheDocument();
        expect(screen.getByText('Welcome back')).toBeInTheDocument();
        expect(screen.getByText('form here')).toBeInTheDocument();
    });

    it('applies the stored dark theme to <html>', () => {
        localStorage.setItem('agentic-cms-theme', 'dark');
        render(<AuthLayout title="x">y</AuthLayout>);
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });
});
```

- [ ] **Step 2: Run to verify fail**

Run: `npx vitest run resources/js/layouts`
Expected: FAIL (module not found).

- [ ] **Step 3: Implement `AuthLayout.tsx`**

```tsx
import type { ReactNode } from 'react';
import { useEffect } from 'react';

const THEME_KEY = 'agentic-cms-theme';

function applyStoredTheme(): void {
    const stored = localStorage.getItem(THEME_KEY);
    const dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.classList.toggle('dark', dark);
}

interface AuthLayoutProps {
    title: string;
    subtitle?: string;
    children: ReactNode;
}

export function AuthLayout({ title, subtitle, children }: AuthLayoutProps) {
    // Honor the user's saved theme; the Inertia root does not load front.js.
    useEffect(() => {
        applyStoredTheme();
    }, []);

    return (
        <div className="theme-default min-h-screen bg-bg text-fg lg:grid lg:grid-cols-[45fr_55fr]">
            {/* Brand panel */}
            <aside className="relative hidden overflow-hidden bg-primary text-primary-contrast lg:flex lg:flex-col lg:justify-between lg:p-12">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 opacity-[0.08]"
                    style={{
                        backgroundImage: 'radial-gradient(currentColor 1px, transparent 1px)',
                        backgroundSize: '22px 22px',
                    }}
                />
                <span className="relative font-serif text-2xl font-semibold tracking-tight">Agentic CMS</span>
                <p className="relative max-w-sm font-serif text-3xl leading-tight">
                    AI First CMS you run from your AI assistant
                </p>
                <span className="relative text-sm opacity-70">&copy; Agentic CMS</span>
            </aside>

            {/* Form panel */}
            <main className="flex min-h-screen items-center justify-center px-5 py-16 lg:py-20">
                <div className="w-full max-w-[440px]">
                    <div className="mb-8 text-center lg:text-left">
                        <h1 className="font-serif text-3xl font-semibold tracking-tight text-fg">{title}</h1>
                        {subtitle && <p className="mt-2 text-sm text-muted">{subtitle}</p>}
                    </div>
                    {children}
                </div>
            </main>
        </div>
    );
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `npx vitest run resources/js/layouts`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add resources/js/layouts/AuthLayout.tsx resources/js/layouts/AuthLayout.test.tsx
git commit -m "feat(auth): split-screen AuthLayout with theme bootstrap"
```

---

### Task 11: The 5 React auth pages

**Files:**
- Create: `resources/js/pages/auth/{Login,Register,ForgotPassword,ResetPassword,VerifyEmail}.tsx` and a `.test.tsx` for each.

**Interfaces:**
- Consumes: `AuthLayout`, `Button`, `TextField`, `GoogleButton`, `useForm`/`Link`/`Head` from `@inertiajs/react`, `useTranslation` from `react-i18next`.
- Produces: the components `Inertia::render('auth/Login')` etc. resolve to.

Testing pattern mirrors `resources/js/pages/Demo.test.tsx`: mock `@inertiajs/react` (`useForm` returns `{ data, setData, post, processing, errors }`; stub `Head`/`Link`/`usePage`), and i18n via the real `initI18n` with a small message map OR mock `useTranslation` to return `t = (k) => k`. Assert testids, labels, google link, and error rendering.

- [ ] **Step 1: Write the failing test for Login** — `resources/js/pages/auth/Login.test.tsx`

```tsx
import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    useForm: () => ({
        data: { email: '', password: '', remember: false },
        setData: vi.fn(),
        post: vi.fn(),
        processing: false,
        errors: {},
    }),
    Head: () => null,
    Link: ({ children, href }: { children: React.ReactNode; href: string }) => <a href={href}>{children}</a>,
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

import Login from './Login';

describe('Login page', () => {
    it('renders the login testids and the google button', () => {
        render(<Login canResetPassword membershipEnabled status={null} />);
        expect(screen.getByTestId('login-username')).toBeInTheDocument();
        expect(screen.getByTestId('login-password')).toBeInTheDocument();
        expect(screen.getByTestId('login-submit')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /google/i })).toHaveAttribute('href', '/login/google');
    });
});
```

- [ ] **Step 2: Run to verify fail**

Run: `npx vitest run resources/js/pages/auth/Login.test.tsx`
Expected: FAIL (module not found).

- [ ] **Step 3: Implement `Login.tsx`**

```tsx
import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/Button';
import { GoogleButton } from '@/components/GoogleButton';
import { TextField } from '@/components/TextField';
import { AuthLayout } from '@/layouts/AuthLayout';

interface LoginProps {
    canResetPassword: boolean;
    membershipEnabled: boolean;
    status?: string | null;
}

export default function Login({ canResetPassword, status }: LoginProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/login');
    }

    return (
        <AuthLayout title={t('login.login_page_headline')} subtitle={t('login.login')}>
            <Head title={t('login.login')} />

            {status && (
                <p role="status" className="mb-6 rounded-md border border-success bg-success-bg p-4 text-sm text-success">
                    {status}
                </p>
            )}

            <GoogleButton>{t('login.login_with')} Google</GoogleButton>

            <div className="my-6 flex items-center gap-3 text-xs uppercase tracking-wider text-subtle">
                <span className="h-px flex-1 bg-border" />
                <span>{t('login.or')}</span>
                <span className="h-px flex-1 bg-border" />
            </div>

            <form onSubmit={submit} className="space-y-5" noValidate>
                <TextField
                    name="email"
                    type="text"
                    label={t('login.username_or_email')}
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    autoComplete="email"
                    autoFocus
                    required
                    data-testid="login-username"
                />
                <TextField
                    name="password"
                    type="password"
                    label={t('login.password')}
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    autoComplete="current-password"
                    required
                    data-testid="login-password"
                />

                <label htmlFor="remember" className="flex cursor-pointer items-center gap-2.5 text-sm text-muted">
                    <input
                        id="remember"
                        type="checkbox"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                        className="rounded border-strong text-primary focus:ring-ring"
                    />
                    {t('login.remember_me')}
                </label>

                <div className="flex items-center justify-between gap-4 pt-1">
                    <Button type="submit" variant="primary" loading={processing} data-testid="login-submit">
                        {t('login.login')}
                    </Button>
                    {canResetPassword && (
                        <Link href="/password/reset" className="text-sm font-medium text-muted transition hover:text-primary">
                            {t('login.forgot_password')}
                        </Link>
                    )}
                </div>
            </form>
        </AuthLayout>
    );
}
```

- [ ] **Step 4: Run Login test to verify pass**

Run: `npx vitest run resources/js/pages/auth/Login.test.tsx`
Expected: PASS.

- [ ] **Step 5: Implement Register, ForgotPassword, ResetPassword, VerifyEmail** (each with a test first). They follow the same shape. Copy keys from the current blades:

`Register.tsx` — props: none required. Fields name/email/username/password/password_confirmation. Labels `registration.name|email|username|password|confirm_password`, heading `registration.register_page_headline`, subtitle `registration.register`, submit `registration.register_btn`, `post('/register')`, link to `/login` labeled `login.login`.

`ForgotPassword.tsx` — props `status?`. Field email (`custom-passwords.email`), heading `custom-passwords.reset_page_headline`, submit `custom-passwords.send_password_link`, `post('/password/email')`, success `status` alert, link to `/login`.

`ResetPassword.tsx` — props `token: string`, `email: string`. Hidden token in the form data (`useForm({ token, email, password: '', password_confirmation: '' })`), email prefilled, password + confirm (`custom-passwords.password|confirm_password`), submit `custom-passwords.reset_password_btn`, `post('/password/reset')`.

`VerifyEmail.tsx` — props `status?: 'resent' | null`. Heading `email.verify_page_headline`; body `email.check_email` + `email.not_receive_email` + resend `<Link>`-as-form to `/email/resend` (POST). Show `email.fresh_link` success alert when `status === 'resent'`. Resend uses `useForm({}).post('/email/resend')` triggered by a button.

Each test asserts: fields/labels render, `t()` keys resolve (mock returns key), the correct `post` target is wired (spy on `useForm().post`), and for ResetPassword that `token`/`email` seed the form.

- [ ] **Step 6: Run the whole auth page suite**

Run: `npx vitest run resources/js/pages/auth`
Expected: PASS (5 pages).

- [ ] **Step 7: Build check + commit**

Run: `npm run build` — Expected: manifest includes `resources/js/pages/auth/*` chunks, no `*.test.tsx` in the bundle.

```bash
git add resources/js/pages/auth
git commit -m "feat(auth): 5 Inertia React auth pages (login/register/forgot/reset/verify)"
```

---

### Task 12: Final verification

**Files:** none (verification only).

- [ ] **Step 1: Full backend suite**

Run: `php artisan test`
Expected: PASS (all Feature + Unit; if Arch tests OOM locally, run `php -d memory_limit=1024M artisan test --testsuite=Feature` and `--testsuite=Unit` separately — Arch is green in CI).

- [ ] **Step 2: Frontend suite + typecheck + build**

Run: `npx vitest run` → all green.
Run: `npx tsc --noEmit` → exit 0.
Run: `npm run build` → success, manifest has `app.tsx` + auth page chunks, no `*.test.tsx`.

- [ ] **Step 3: laravel/ui grep is clean**

Run:
```bash
grep -rn "laravel/ui\|Auth::routes\|AuthenticatesUsers\|RegistersUsers\|SendsPasswordResetEmails\|ResetsPasswords\|VerifiesEmails" app/ routes/ composer.json composer.lock
```
Expected: no matches (the Blade views under `resources/views/auth/**` still reference lang keys but no traits — they are removed in Phase 5, not here; they must not be *rendered* by any controller anymore, which the render tests already prove).

- [ ] **Step 4: Login data-testids present in the built React page**

Confirm `resources/js/pages/auth/Login.tsx` contains the three `data-testid`s (grep). The browser suite `tests/Browser/AuthAdminTest.php` runs only in CI (`BROWSER_TESTS=1`); it needs no edits.

- [ ] **Step 5: Ledger + done**

Append the Phase 2 completion line to `.git/sdd/progress.md` and stop for the final whole-branch review (subagent-driven-development dispatches the opus reviewer over `merge-base..HEAD`).

## Notes for the executor

- **Blade auth views stay on disk** until Phase 5. After Tasks 3–6 no controller renders them, so they are dead code, not a runtime path. Do NOT delete `resources/views/auth/**` or `social.blade.php` in this phase (deleting them is Phase 5; premature deletion risks a 500 if any missed reference remains).
- **`get_general_settings`/`get_current_lang`** are existing global helpers (`bootstrap/agentic-cms-laravel-helpers.php`) — safe to call from controllers.
- **Pint** enforces `fully_qualified_strict_types` + `ordered_imports`; import facades (`use Illuminate\Support\Facades\{Auth,Password,Route};`) rather than leading-slash calls, and run `./vendor/bin/pint` before each commit.
- **PHPStan**: run `php -d memory_limit=1024M ./vendor/bin/phpstan analyse` if the project gates on it; add `Inertia\Response` return types as shown to keep levels green.
- **`@/` alias in tests**: Vitest resolves `@/` via `resources/js` (config already set in Phase 1). Page imports use `@/components/...`, `@/layouts/...`.
