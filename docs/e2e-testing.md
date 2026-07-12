# End-to-end (browser) tests — Laravel Dusk

The PHPUnit suite (`php artisan test`) verifies behaviour at the HTTP/DB level on
in-memory SQLite. It does **not** render CSS or run JavaScript, so it cannot catch
visual/UI regressions (an unstyled login form, dark-on-dark sidebar text, a link
missing its port). The **Dusk** suite covers exactly that: it drives a real headless
Chrome against the running app and asserts both **behaviour** (login, language
switch, saving settings) and **applied styles** (computed colours/layout).

> Already paid off: the Dusk suite caught a real bug where `.theme-default button`
> stripped the brand background off the login submit button — see
> `tests/Browser/AuthAndAdminTest.php`.

## How it runs (isolation)

- Runs **on the host** (uses your installed Google Chrome) against the app served at
  `http://127.0.0.1:8000`.
- Uses a **dedicated database `agentic_cms_laravel_dusk`** inside the dockerised MySQL (reached
  from the host on the published port `33060`). It **never touches the dev `agentic_cms_laravel`
  DB**.
- `.env.dusk.local` (gitignored; bootstrapped from `.env.dusk.local.example`) sets
  `DUSK=true`, which makes `tests/CreatesApplication.php` skip its SQLite `:memory:`
  pin so the test process and the served app share the MySQL `agentic_cms_laravel_dusk` DB.

## Run it

```bash
# One command — ensures the dusk DB, matches ChromeDriver, migrates+seeds,
# serves the app on :8000, runs Dusk, then stops the server.
make dusk

# Filter to a subset:
make dusk ARGS="--filter=AuthAndAdminTest"
```

Requirements: Docker stack up (`make up`), Google Chrome installed, host PHP with
`pdo_mysql` + `imagick`. First run downloads a matching ChromeDriver.

### Manual (without the make target)

```bash
cp .env.dusk.local.example .env.dusk.local         # first time only
php artisan dusk:chrome-driver --detect             # match your Chrome
php artisan migrate:fresh --seed --env=dusk.local --force
php artisan serve --env=dusk.local --port=8000 &    # serve with the dusk env
php artisan dusk                                     # run the browser suite
```

## What's covered

| File | Verifies |
|---|---|
| `tests/Browser/AuthAndAdminTest.php` | Login form is brand-styled (button bg) + admin signs in; dark sidebar link text is light/readable (computed colour). |
| `tests/Browser/PublicSiteTest.php` | Homepage is styled (flex header); post links carry the correct host:port; EN→RU language switch loads the Russian site; a post opens. |
| `tests/Browser/GeoSettingsBrowserTest.php` | Admin fills the GEO settings form (input/select/textarea/checkbox), saves, sees the success flash, and the data shows up in `/llms.txt`. |

Helper: `tests/Browser/Concerns/ReadsComputedStyle.php` parses `getComputedStyle`
values so tests can assert "this element is actually the brand colour / readable",
not just that markup exists.

## Writing more

Tests use `DatabaseMigrations` + `$this->seed(DatabaseSeeder::class)` for a clean,
seeded DB per test. Dusk reuses the browser across tests in a class, so if a test
changes session state (e.g. the locale cookie) clear it at the start of the next:
`$browser->visit('/')->driver->manage()->deleteAllCookies();`.

Scope selectors to a specific form when a page has several (e.g. the public header's
search form): `form[action*="/login"] button[type=submit]`, not just
`form button[type=submit]`.
