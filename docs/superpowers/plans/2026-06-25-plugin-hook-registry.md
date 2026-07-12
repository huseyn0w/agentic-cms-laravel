# Plugin / Hook Registry (P9) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add action/filter/render-region hooks plus runtime-toggleable in-repo plugins, with a bundled reading-time sample, on a Laravel-events engine.

**Architecture:** A `Hooks` singleton wraps the event dispatcher (string-named `hook.{action,filter,region}.*` events). In-repo plugins implement a contract and register listeners in `boot()`. `PluginManager` discovers plugins on the filesystem, syncs them into a `plugins` table, and boots only the enabled ones via `PluginServiceProvider`. An admin screen toggles `enabled`.

**Tech Stack:** Laravel 11, PHP 8.3, Eloquent, Blade, PHPUnit (in-memory SQLite).

## Global Constraints

- Strict layering: controller → service → repository → model. Controllers: no business logic / no ORM. Services: no ORM (repositories only). `Hooks`/`PluginManager` are support classes; the filesystem scan is not data access; the only ORM lives in `CPanelPluginRepository`.
- Models live in `app/Http/Models/` (admin under `CPanel/`).
- Test isolation pinned to in-memory SQLite (`tests/CreatesApplication.php`) — never weaken.
- Pint (Laravel preset) + PHPStan/Larastan level 5 stay clean; add NO new baseline entries.
- All code/comments/identifiers in English; lang keys for en + ru.
- Plugins are in-repo only (no upload/exec). Region output is trusted, echoed raw.
- Commit each verified slice; no `Co-Authored-By` trailer.

---

### Task 1: Hooks engine

**Files:**
- Create: `app/Support/Hooks.php`
- Test: `tests/Unit/HooksTest.php`

**Interfaces:**
- Produces: `App\Support\Hooks` with `action(string,...$args):void`, `onAction(string,callable):void`, `filter(string,mixed,...$args):mixed`, `onFilter(string,callable):void`, `region(string,array $context=[]):string`, `onRegion(string,callable):void`. Constructed with an `Illuminate\Contracts\Events\Dispatcher`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Support\Hooks;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class HooksTest extends TestCase
{
    private function hooks(): Hooks
    {
        return new Hooks(Event::getFacadeRoot());
    }

    public function test_action_invokes_registered_listeners(): void
    {
        $hooks = $this->hooks();
        $seen = [];
        $hooks->onAction('post.viewed', function ($id) use (&$seen) { $seen[] = $id; });

        $hooks->action('post.viewed', 42);

        $this->assertSame([42], $seen);
    }

    public function test_filter_returns_mutated_value_in_order(): void
    {
        $hooks = $this->hooks();
        $hooks->onFilter('the_content', fn ($html) => $html.'-a');
        $hooks->onFilter('the_content', fn ($html) => $html.'-b');

        $this->assertSame('x-a-b', $hooks->filter('the_content', 'x'));
    }

    public function test_filter_without_listeners_returns_value_unchanged(): void
    {
        $this->assertSame('y', $this->hooks()->filter('untouched', 'y'));
    }

    public function test_region_concatenates_listener_fragments(): void
    {
        $hooks = $this->hooks();
        $hooks->onRegion('footer', fn () => '<a>');
        $hooks->onRegion('footer', fn () => '<b>');

        $this->assertSame('<a><b>', $hooks->region('footer'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=HooksTest`
Expected: FAIL (class `App\Support\Hooks` not found).

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Support;

use Illuminate\Contracts\Events\Dispatcher;

/**
 * P9 hook engine: a thin, Laravel-idiomatic wrapper over the event dispatcher.
 * Hooks are string-named events namespaced by kind so plugins can subscribe to
 * a specific hook by name:
 *   - actions: fire-and-forget side effects.
 *   - filters: a value is passed through listeners (each may return a new value).
 *   - regions: listeners return HTML fragments that are concatenated for a
 *     named template region (rendered via the @hook Blade directive).
 */
class Hooks
{
    public function __construct(private Dispatcher $events) {}

    public function onAction(string $name, callable $callback): void
    {
        $this->events->listen($this->key('action', $name), $callback);
    }

    public function action(string $name, mixed ...$args): void
    {
        $this->events->dispatch($this->key('action', $name), $args);
    }

    public function onFilter(string $name, callable $callback): void
    {
        $this->events->listen($this->key('filter', $name), function (array $payload) use ($callback) {
            // $payload[0] is the mutable container; the rest are extra args.
            $container = $payload[0];
            $args = array_slice($payload, 1);
            $container->value = $callback($container->value, ...$args);
        });
    }

    public function filter(string $name, mixed $value, mixed ...$args): mixed
    {
        $container = new HookValue($value);
        $this->events->dispatch($this->key('filter', $name), array_merge([$container], $args));

        return $container->value;
    }

    public function onRegion(string $name, callable $callback): void
    {
        $this->events->listen($this->key('region', $name), $callback);
    }

    public function region(string $name, array $context = []): string
    {
        $fragments = $this->events->dispatch($this->key('region', $name), [$context]);

        return implode('', array_map(fn ($f) => (string) $f, array_filter($fragments)));
    }

    private function key(string $kind, string $name): string
    {
        return "hook.{$kind}.{$name}";
    }
}
```

Also create `app/Support/HookValue.php`:

```php
<?php

namespace App\Support;

/** Mutable container so filter listeners can return a new value on an event engine. */
class HookValue
{
    public function __construct(public mixed $value) {}
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=HooksTest`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Support/Hooks.php app/Support/HookValue.php tests/Unit/HooksTest.php
git commit -m "feat(hooks): event-backed action/filter/region engine"
```

---

### Task 2: `@hook` Blade directive + container binding

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/HookDirectiveTest.php`

**Interfaces:**
- Consumes: `App\Support\Hooks` (Task 1).
- Produces: container singleton bound as `hooks` (and `Hooks::class`); Blade `@hook('region')` echoing `app('hooks')->region('region')`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Support\Hooks;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class HookDirectiveTest extends TestCase
{
    public function test_hooks_is_a_shared_singleton(): void
    {
        $this->assertInstanceOf(Hooks::class, app('hooks'));
        $this->assertSame(app('hooks'), app('hooks'));
    }

    public function test_hook_directive_renders_region_output(): void
    {
        app('hooks')->onRegion('footer', fn () => '<span id="z">hi</span>');

        $rendered = Blade::render("@hook('footer')");

        $this->assertStringContainsString('<span id="z">hi</span>', $rendered);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=HookDirectiveTest`
Expected: FAIL (`hooks` not bound / directive unknown).

- [ ] **Step 3: Write minimal implementation**

In `AppServiceProvider::register()` add:

```php
$this->app->singleton(\App\Support\Hooks::class, fn ($app) => new \App\Support\Hooks($app['events']));
$this->app->alias(\App\Support\Hooks::class, 'hooks');
```

In `AppServiceProvider::boot()` add:

```php
\Illuminate\Support\Facades\Blade::directive('hook', function ($expression) {
    return "<?php echo app('hooks')->region({$expression}); ?>";
});
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=HookDirectiveTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Providers/AppServiceProvider.php tests/Feature/HookDirectiveTest.php
git commit -m "feat(hooks): @hook Blade directive + hooks singleton"
```

---

### Task 3: Plugins table, model, contract, repository

**Files:**
- Create: `database/migrations/2026_06_25_000400_create_plugins_table.php`
- Create: `app/Http/Models/CPanel/Plugin.php`
- Create: `app/Plugins/Contracts/PluginInterface.php`
- Create: `app/Repositories/CPanelPluginRepository.php`
- Test: `tests/Feature/Plugins/PluginRepositoryTest.php`

**Interfaces:**
- Produces:
  - `App\Plugins\Contracts\PluginInterface`: `slug():string`, `name():string`, `description():string`, `boot(\App\Support\Hooks $hooks):void`.
  - `App\Http\Models\CPanel\Plugin` (table `plugins`, fillable `slug`,`enabled`, cast `enabled`=>bool).
  - `App\Repositories\CPanelPluginRepository` with `ensureExists(string $slug):void`, `enabledSlugs():array<string>`, `setEnabled(string $slug, bool $enabled):void`, `allKeyedBySlug():\Illuminate\Support\Collection`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Plugins;

use App\Repositories\CPanelPluginRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function repo(): CPanelPluginRepository
    {
        return app(CPanelPluginRepository::class);
    }

    public function test_ensure_exists_inserts_disabled_then_is_idempotent(): void
    {
        $repo = $this->repo();
        $repo->ensureExists('reading-time');
        $repo->ensureExists('reading-time');

        $this->assertDatabaseCount('plugins', 1);
        $this->assertDatabaseHas('plugins', ['slug' => 'reading-time', 'enabled' => false]);
    }

    public function test_set_enabled_and_enabled_slugs(): void
    {
        $repo = $this->repo();
        $repo->ensureExists('reading-time');
        $repo->ensureExists('other');
        $repo->setEnabled('reading-time', true);

        $this->assertSame(['reading-time'], $repo->enabledSlugs());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PluginRepositoryTest`
Expected: FAIL (no `plugins` table / classes missing).

- [ ] **Step 3: Write minimal implementation**

Migration `2026_06_25_000400_create_plugins_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
```

Model `app/Http/Models/CPanel/Plugin.php`:

```php
<?php

namespace App\Http\Models\CPanel;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected $table = 'plugins';

    protected $fillable = ['slug', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];
}
```

Contract `app/Plugins/Contracts/PluginInterface.php`:

```php
<?php

namespace App\Plugins\Contracts;

use App\Support\Hooks;

/** An in-repo plugin: stable metadata plus a boot() that registers hook listeners. */
interface PluginInterface
{
    public function slug(): string;

    public function name(): string;

    public function description(): string;

    public function boot(Hooks $hooks): void;
}
```

Repository `app/Repositories/CPanelPluginRepository.php`:

```php
<?php

namespace App\Repositories;

use App\Http\Models\CPanel\Plugin;
use Illuminate\Support\Collection;

class CPanelPluginRepository
{
    public function ensureExists(string $slug): void
    {
        Plugin::firstOrCreate(['slug' => $slug], ['enabled' => false]);
    }

    /** @return array<int, string> */
    public function enabledSlugs(): array
    {
        return Plugin::where('enabled', true)->pluck('slug')->all();
    }

    public function setEnabled(string $slug, bool $enabled): void
    {
        Plugin::where('slug', $slug)->update(['enabled' => $enabled]);
    }

    /** @return Collection<string, Plugin> */
    public function allKeyedBySlug(): Collection
    {
        return Plugin::all()->keyBy('slug');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PluginRepositoryTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_06_25_000400_create_plugins_table.php app/Http/Models/CPanel/Plugin.php app/Plugins/Contracts/PluginInterface.php app/Repositories/CPanelPluginRepository.php tests/Feature/Plugins/PluginRepositoryTest.php
git commit -m "feat(plugins): plugins table, model, contract, repository"
```

---

### Task 4: PluginManager (discover / sync / loadEnabled)

**Files:**
- Create: `app/Support/PluginManager.php`
- Create: `app/Plugins/ReadingTime/ReadingTimePlugin.php` (used as a discovery fixture here; its filter is wired in Task 6)
- Test: `tests/Feature/Plugins/PluginManagerTest.php`

**Interfaces:**
- Consumes: `Hooks` (Task 1), `CPanelPluginRepository` (Task 3), `PluginInterface` (Task 3).
- Produces: `App\Support\PluginManager` with `discover():array<string,PluginInterface>` (keyed by slug), `sync():void`, `loadEnabled(Hooks $hooks):void`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Plugins;

use App\Repositories\CPanelPluginRepository;
use App\Support\Hooks;
use App\Support\PluginManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PluginManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_discover_finds_the_reading_time_plugin(): void
    {
        $manager = app(PluginManager::class);
        $this->assertArrayHasKey('reading-time', $manager->discover());
    }

    public function test_sync_registers_discovered_plugins_as_disabled(): void
    {
        app(PluginManager::class)->sync();
        $this->assertDatabaseHas('plugins', ['slug' => 'reading-time', 'enabled' => false]);
    }

    public function test_load_enabled_boots_only_enabled_plugins(): void
    {
        $manager = app(PluginManager::class);
        $manager->sync();

        $hooks = new Hooks(Event::getFacadeRoot());

        // Disabled: filter must NOT run.
        $manager->loadEnabled($hooks);
        $this->assertSame('body', $hooks->filter('the_content', 'body'));

        // Enable, reload into a fresh Hooks: filter now runs.
        app(CPanelPluginRepository::class)->setEnabled('reading-time', true);
        $hooks2 = new Hooks(Event::getFacadeRoot());
        $manager->loadEnabled($hooks2);
        $this->assertStringContainsString('min read', $hooks2->filter('the_content', str_repeat('word ', 400)));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PluginManagerTest`
Expected: FAIL (`PluginManager` / plugin missing).

- [ ] **Step 3: Write minimal implementation**

`app/Plugins/ReadingTime/ReadingTimePlugin.php`:

```php
<?php

namespace App\Plugins\ReadingTime;

use App\Plugins\Contracts\PluginInterface;
use App\Support\Hooks;

/** Sample plugin: prepend an estimated reading time to the post body via a filter. */
class ReadingTimePlugin implements PluginInterface
{
    private const WORDS_PER_MINUTE = 200;

    public function slug(): string
    {
        return 'reading-time';
    }

    public function name(): string
    {
        return 'Reading time';
    }

    public function description(): string
    {
        return 'Adds an estimated reading time badge to the top of each post.';
    }

    public function boot(Hooks $hooks): void
    {
        $hooks->onFilter('the_content', function (string $html): string {
            $words = str_word_count(strip_tags($html));
            $minutes = max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
            $badge = '<p class="reading-time">'.$minutes.' min read</p>';

            return $badge.$html;
        });
    }
}
```

`app/Support/PluginManager.php`:

```php
<?php

namespace App\Support;

use App\Plugins\Contracts\PluginInterface;
use App\Repositories\CPanelPluginRepository;
use Illuminate\Support\Str;

/**
 * Discovers in-repo plugins on the filesystem, syncs them into the plugins table,
 * and boots the enabled ones. Filesystem scanning is not data access; the only DB
 * access is delegated to CPanelPluginRepository.
 */
class PluginManager
{
    public function __construct(private CPanelPluginRepository $repository) {}

    /** @return array<string, PluginInterface> keyed by slug */
    public function discover(): array
    {
        $plugins = [];

        foreach (glob(app_path('Plugins/*/*Plugin.php')) ?: [] as $file) {
            $class = $this->classFromFile($file);

            if ($class && is_subclass_of($class, PluginInterface::class)) {
                $plugin = new $class;
                $plugins[$plugin->slug()] = $plugin;
            }
        }

        return $plugins;
    }

    public function sync(): void
    {
        foreach (array_keys($this->discover()) as $slug) {
            $this->repository->ensureExists($slug);
        }
    }

    public function loadEnabled(Hooks $hooks): void
    {
        $enabled = $this->repository->enabledSlugs();
        $discovered = $this->discover();

        foreach ($enabled as $slug) {
            if (isset($discovered[$slug])) {
                $discovered[$slug]->boot($hooks);
            }
        }
    }

    private function classFromFile(string $file): ?string
    {
        $name = Str::of($file)
            ->after(app_path().DIRECTORY_SEPARATOR)
            ->replace(DIRECTORY_SEPARATOR, '\\')
            ->beforeLast('.php');

        $class = 'App\\'.$name;

        return class_exists($class) ? $class : null;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PluginManagerTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Support/PluginManager.php app/Plugins/ReadingTime/ReadingTimePlugin.php tests/Feature/Plugins/PluginManagerTest.php
git commit -m "feat(plugins): filesystem discovery, sync, and enabled-only boot"
```

---

### Task 5: PluginServiceProvider (boot wiring)

**Files:**
- Create: `app/Providers/PluginServiceProvider.php`
- Modify: `config/app.php` (register provider in `providers` array)
- Test: `tests/Feature/Plugins/PluginBootIntegrationTest.php`

**Interfaces:**
- Consumes: `Hooks`, `PluginManager`.
- Produces: app boot runs `PluginManager::sync()` then `loadEnabled(app('hooks'))`, guarded by `Schema::hasTable('plugins')`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Plugins;

use App\Repositories\CPanelPluginRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginBootIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_plugin_filter_is_active_on_the_global_hooks_after_boot(): void
    {
        // Simulate a real request lifecycle: enable, then re-bootstrap the app.
        app(CPanelPluginRepository::class)->firstOrCreateThenEnable();

        $this->refreshApplication();

        $filtered = app('hooks')->filter('the_content', str_repeat('word ', 400));
        $this->assertStringContainsString('min read', $filtered);
    }
}
```

> Note: add a tiny helper on the repository for the test's intent, OR inline it.
> To avoid a test-only production method, replace the first line with:
> ```php
> $this->seed(\Database\Seeders\DatabaseSeeder::class);
> app(\App\Support\PluginManager::class)->sync();
> app(CPanelPluginRepository::class)->setEnabled('reading-time', true);
> ```
> and drop the `firstOrCreateThenEnable()` reference.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PluginBootIntegrationTest`
Expected: FAIL (provider not wired; filter inactive after boot).

- [ ] **Step 3: Write minimal implementation**

`app/Providers/PluginServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Support\Hooks;
use App\Support\PluginManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function boot(PluginManager $manager): void
    {
        // The plugins table may not exist yet during fresh installs / migrations.
        if (! Schema::hasTable('plugins')) {
            return;
        }

        $manager->sync();
        $manager->loadEnabled($this->app->make(Hooks::class));
    }
}
```

Register in `config/app.php` providers array (after `App\Providers\AppServiceProvider::class`):

```php
App\Providers\PluginServiceProvider::class,
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PluginBootIntegrationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Providers/PluginServiceProvider.php config/app.php tests/Feature/Plugins/PluginBootIntegrationTest.php
git commit -m "feat(plugins): boot discovered + enabled plugins via provider"
```

---

### Task 6: Wire content filter + render regions into the theme

**Files:**
- Modify: `resources/views/default/posts/post.blade.php` (content render line)
- Modify: `resources/views/default/header.blade.php` (add `@hook('header')`)
- Modify: `resources/views/default/footer.blade.php` (add `@hook('footer')`)
- Modify: `resources/views/default/partials/seo-meta.blade.php` or layout head (add `@hook('head')`)
- Test: `tests/Feature/Plugins/ReadingTimeRenderTest.php`

**Interfaces:**
- Consumes: `app('hooks')->filter('the_content', ...)`, `@hook(...)`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Plugins;

use App\Repositories\CPanelPluginRepository;
use App\Support\PluginManager;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingTimeRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        app(PluginManager::class)->sync();
    }

    public function test_reading_time_absent_when_plugin_disabled(): void
    {
        $this->assertStringNotContainsString('min read', $this->get('/posts/post-example')->getContent());
    }

    public function test_reading_time_shown_when_plugin_enabled(): void
    {
        app(CPanelPluginRepository::class)->setEnabled('reading-time', true);
        $this->refreshApplication();
        $this->seed(DatabaseSeeder::class);
        app(PluginManager::class)->sync();
        app(CPanelPluginRepository::class)->setEnabled('reading-time', true);

        $this->assertStringContainsString('min read', $this->get('/posts/post-example')->getContent());
    }
}
```

> If `refreshApplication()` complicates DB state, instead assert via a direct
> `app('hooks')->filter('the_content', $post->content)` after enabling — but the
> HTTP assertion above is preferred as it proves the blade wiring.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ReadingTimeRenderTest`
Expected: FAIL on the enabled case (content not filtered in the view).

- [ ] **Step 3: Write minimal implementation**

In `resources/views/default/posts/post.blade.php` change the body render:

```blade
{!! app('hooks')->filter('the_content', $data->content) !!}
```

Add `@hook('header')` inside the header nav container, `@hook('footer')` in the footer, and `@hook('head')` in the `<head>` (seo-meta partial end).

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ReadingTimeRenderTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/default tests/Feature/Plugins/ReadingTimeRenderTest.php
git commit -m "feat(plugins): wire the_content filter + head/header/footer regions"
```

---

### Task 7: Admin UI (service, controller, routes, view, lang, sidebar)

**Files:**
- Create: `app/Services/CPanel/CPanelPluginService.php`
- Create: `app/Http/Controllers/CPanel/CPanelPluginController.php`
- Create: `resources/views/cpanel/plugins/list.blade.php`
- Create: `resources/lang/en/cpanel/plugins.php`, `resources/lang/ru/cpanel/plugins.php`
- Modify: `routes/web.php` (admin group), admin sidebar partial
- Test: `tests/Feature/Plugins/PluginAdminTest.php`

**Interfaces:**
- Consumes: `PluginManager::discover()`, `CPanelPluginRepository`.
- Produces:
  - `CPanelPluginService` with `listForAdmin():\Illuminate\Support\Collection` (each item: slug, name, description, enabled) and `toggle(string $slug, bool $enabled):void`.
  - Controller `index()` and `toggle(Request)`; routes `cpanel_plugins_list` (GET `/plugins`) and `cpanel_toggle_plugin` (PUT `/plugins/toggle`).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Plugins;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    public function test_admin_can_see_discovered_plugins(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/plugins')
            ->assertOk()
            ->assertSee('Reading time');
    }

    public function test_admin_can_enable_a_plugin(): void
    {
        $this->actingAs($this->admin)
            ->put('/agentic-cms-laravel-admin/plugins/toggle', ['slug' => 'reading-time', 'enabled' => 1])
            ->assertRedirect();

        $this->assertDatabaseHas('plugins', ['slug' => 'reading-time', 'enabled' => true]);
    }

    public function test_guest_cannot_access_plugin_admin(): void
    {
        $this->get('/agentic-cms-laravel-admin/plugins')->assertRedirect();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PluginAdminTest`
Expected: FAIL (route/controller/view missing).

- [ ] **Step 3: Write minimal implementation**

`app/Services/CPanel/CPanelPluginService.php`:

```php
<?php

namespace App\Services\CPanel;

use App\Repositories\CPanelPluginRepository;
use App\Support\PluginManager;
use Illuminate\Support\Collection;

class CPanelPluginService
{
    public function __construct(
        private PluginManager $manager,
        private CPanelPluginRepository $repository,
    ) {}

    /** @return Collection<int, array{slug:string,name:string,description:string,enabled:bool}> */
    public function listForAdmin(): Collection
    {
        $this->manager->sync();
        $rows = $this->repository->allKeyedBySlug();

        return collect($this->manager->discover())
            ->map(fn ($plugin) => [
                'slug' => $plugin->slug(),
                'name' => $plugin->name(),
                'description' => $plugin->description(),
                'enabled' => (bool) ($rows[$plugin->slug()]->enabled ?? false),
            ])
            ->values();
    }

    public function toggle(string $slug, bool $enabled): void
    {
        $this->repository->ensureExists($slug);
        $this->repository->setEnabled($slug, $enabled);
    }
}
```

`app/Http/Controllers/CPanel/CPanelPluginController.php`:

```php
<?php

namespace App\Http\Controllers\CPanel;

use App\Services\CPanel\CPanelPluginService;
use Illuminate\Http\Request;

class CPanelPluginController extends CPanelBaseController
{
    public function __construct(private CPanelPluginService $service) {}

    public function index()
    {
        return view('cpanel.plugins.list', ['plugins' => $this->service->listForAdmin()]);
    }

    public function toggle(Request $request)
    {
        $data = $request->validate([
            'slug' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        $this->service->toggle($data['slug'], (bool) $data['enabled']);

        return redirect()->route('cpanel_plugins_list');
    }
}
```

Routes in `routes/web.php` admin group (gated by `manage_general_settings`):

```php
Route::prefix('plugins')->middleware('manage_general_settings')->group(function () {
    Route::get('/', 'CPanelPluginController@index')->name('cpanel_plugins_list');
    Route::put('/toggle', 'CPanelPluginController@toggle')->name('cpanel_toggle_plugin');
});
```

View `resources/views/cpanel/plugins/list.blade.php` (extends the admin layout; iterate `$plugins`, each row shows name/description + a toggle form POSTing slug+enabled). Lang files with `headline`, `enable`, `disable`, `enabled`, `disabled`. Add a sidebar link under Settings.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PluginAdminTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/CPanel/CPanelPluginService.php app/Http/Controllers/CPanel/CPanelPluginController.php resources/views/cpanel/plugins resources/lang/en/cpanel/plugins.php resources/lang/ru/cpanel/plugins.php routes/web.php resources/views/cpanel
git commit -m "feat(plugins): admin plugin manager (list + enable/disable toggle)"
```

---

### Task 8: Full verification + adversarial review

- [ ] **Step 1:** `php artisan test` → all green (show output).
- [ ] **Step 2:** `composer lint` (Pint) → clean; `composer analyse` (PHPStan) → no errors, no new baseline.
- [ ] **Step 3:** Dispatch 2–3 adversarial skeptics (behavior / security / architecture) to refute; fix any majority finding via TDD.
- [ ] **Step 4:** Update `HANDOFF.md` (mark P9 done, next = coverage/CI or UI redesign) and commit.

---

## Self-review notes

- Spec coverage: engine (T1/T2), plugins+discovery+toggle (T3/T4/T5), injection points + sample (T6), admin UI (T7), verification (T8). All §1–§7 covered.
- Naming consistency: contract `PluginInterface` (avoids collision with Eloquent `Plugin` model); repository methods `ensureExists/enabledSlugs/setEnabled/allKeyedBySlug` used consistently across T3–T7.
- Region output trusted/raw per spec; content filter is `the_content`.
