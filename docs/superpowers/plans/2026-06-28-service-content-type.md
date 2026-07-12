# Service Content Type Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a first-class, translatable **Service** content type (model → migration → repository → service → controller → admin CRUD → front index/show → JSON-LD → MCP tools) so the §1/§9 matrix claim is backed by real CRUD, replacing the `geo_settings` textarea as the source of service data.

**Architecture:** Mirror the existing **Post** content type but *simpler* — no categories/tags/likes/comments/scheduling/revisions. A Service is a translatable marketing entity with soft-delete (trash/restore), a language-neutral `sort_order` for grid ordering, an optional `icon`, full SEO fields, a public `/services` listing + `/services/{slug}` detail, and schema.org `Service` JSON-LD fed from real records (with a `geo_settings` fallback). Strict controller→service→repository→model layering is enforced by `tests/Arch/LayeringTest.php` — never let a controller or service touch Eloquent/DB directly.

**Tech Stack:** Laravel 11.54 / PHP 8.3, astrotomic/laravel-translatable, Pest 4 (`php artisan test` in the interactive shell — **NOT** `./vendor/bin/pest`, which throws "OutputInterface cannot be resolved" here; subagents/CI run the binary fine), genealabs/laravel-model-caching, `mews/purifier` (`clean()`).

## Global Constraints

- PHP 8.3 / Laravel 11.54. Models live in **`app/Http/Models/`** (NOT `app/Models/`).
- Translatable models: implement `Astrotomic\Translatable\Contracts\Translatable as TranslatableContract`, `use Translatable;`, declare `$translatedAttributes`; companion `*Translation` model + `*_translations` table. Most editable fields live on the translation table.
- All DB access goes through a repository extending `App\Repositories\BaseRepository`. Front family = `ServiceRepository`; admin family = `CPanelServiceRepository`. Controllers stay thin and delegate to a service extending `App\Services\BaseCrudService`.
- Permission flags are explicit methods on `App\Policies\UserPolicy`; `can('manage_services', App\Http\Models\UserRoles::class)` resolves to `UserPolicy::manage_services()`. The admin role auto-gets every permission listed in `UserPermissionsSeeder` (see `UserRolesSeeder`).
- Front routes are registered in `routes/web.php` and **must precede** the catch-all `/{locale?}/{slug?}` → `PageController@languageIndex` (last route).
- Commit messages: plain, imperative, **no Co-Authored-By / Claude attribution trailer**.
- Lint/static analysis must stay green: `composer lint` (Pint) + `composer analyse` (Larastan level 5). Run the full suite with `php artisan test` after each task.
- **Template files to clone from** (read them; the Service files are structural clones with the deltas named per task):
  - Model: `app/Http/Models/Page.php` + `app/Http/Models/PageTranslation.php` (Page is the closest simple analogue — translatable + soft-delete, no tags/likes). Cross-check `Post.php` for the `applyFrontReadScope`/status pattern.
  - Front repo: `app/Repositories/PageRepository.php` (has `applyFrontReadScope()`); admin repo: `app/Repositories/CPanelPageRepository.php`.
  - Services: `app/Services/Front/PostViewService.php`, `app/Services/CPanel/CPanelPostService.php`, base `app/Services/BaseCrudService.php`.
  - Requests: `app/Http/Requests/ValidatePostData.php`, `app/Http/Requests/PostListRequest.php`, base `app/Http/Requests/AgenticCmsLaravelRequest.php`.
  - Controllers: front `app/Http/Controllers/PageController.php` + `app/Http/Controllers/PostController.php`; admin `app/Http/Controllers/CPanel/CPanelPostController.php`, base `CPanelBaseController.php`.
  - Observers: `app/Observers/PostObserver.php`, `app/Observers/PostTranslationObserver.php`, base `app/Observers/AgenticCmsLaravelObserver.php`; registration in `app/Providers/ObserverServiceProvider.php`.
  - Admin views: `resources/views/cpanel/pages/{pages_list,new_page,edit_page}.blade.php`.
  - Front views: `resources/views/default/posts/post.blade.php` for the detail shell; `resources/views/default/partials/banner.blade.php` (h1 + breadcrumbs) and `seo-meta.blade.php` for JSON-LD.
  - MCP: `app/Mcp/Tools/Posts/*` + `app/Mcp/Servers/AgenticCmsLaravelServer.php`; concerns `AuthorizesAccess`, `HydratesRequest`, `ResolvesLocale`.
  - Tests: `tests/Feature/Admin/PostCrudTest.php` (admin user bootstrap pattern), `tests/Feature/Front/PostViewServiceTest.php`, `tests/Feature/Mcp/*`, arch `tests/Arch/LayeringTest.php`.
  - Lang: `resources/lang/{en,ru}/cpanel/posts.php`, `resources/lang/{en,ru}/cpanel/nav/left.php`.

**Service field model (authoritative — use everywhere):**

| Field | Where | Type | Notes |
|---|---|---|---|
| `id` | `services` | bigIncrements | |
| `sort_order` | `services` | unsignedInteger default 0, index | language-neutral grid order |
| `service_id` | `service_translations` | unsignedBigInteger | FK → services.id cascade |
| `locale` | `service_translations` | string, index | |
| `title` | `service_translations` | string(120) | |
| `slug` | `service_translations` | string(160) | |
| `icon` | `service_translations` | string nullable | optional icon name / URL |
| `excerpt` | `service_translations` | string(255) nullable | short summary (used in grid + JSON-LD description) |
| `content` | `service_translations` | mediumText nullable | rich body (sanitized via `clean()`) |
| `thumbnail` | `service_translations` | string nullable | |
| `meta_description` | `service_translations` | string nullable | |
| `meta_keywords` | `service_translations` | string nullable | |
| `canonical_url` | `service_translations` | string nullable | |
| `meta_noindex` | `service_translations` | boolean default 0 | |
| `status` | `service_translations` | integer | 1 = published, 0 = private |
| `timestamps` | `service_translations` | timestamps | |

Unique: `['service_id','locale']`, `['locale','slug']`. No scheduling, no `likes`.

---

### Task 1: Database schema + Eloquent models

**Files:**
- Create: `database/migrations/2026_06_28_000100_create_services_table.php`
- Create: `database/migrations/2026_06_28_000200_create_service_translations_table.php`
- Create: `app/Http/Models/Service.php`
- Create: `app/Http/Models/ServiceTranslation.php`
- Test: `tests/Feature/Services/ServiceModelTest.php`

**Interfaces:**
- Produces: `App\Http\Models\Service` (translatable, soft-deletes, `public const STATUS_PUBLISHED = 1;`, `$translatedAttributes`, `scopeOrdered`); `App\Http\Models\ServiceTranslation` (`$fillable`, `$casts['meta_noindex'=>'boolean']`). Tables `services`, `service_translations`.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Services/ServiceModelTest.php

use App\Http\Models\Service;

it('persists a translatable service with a translation row', function () {
    $service = Service::create(['sort_order' => 5]);
    $service->translateOrNew('en')->title = 'Web Design';
    $service->translateOrNew('en')->slug = 'web-design';
    $service->translateOrNew('en')->status = Service::STATUS_PUBLISHED;
    $service->save();

    $fresh = Service::with('translations')->find($service->id);
    expect($fresh->sort_order)->toBe(5)
        ->and($fresh->translate('en')->title)->toBe('Web Design')
        ->and($fresh->translate('en')->slug)->toBe('web-design')
        ->and((int) $fresh->translate('en')->status)->toBe(1);
});

it('soft-deletes a service', function () {
    $service = Service::create(['sort_order' => 0]);
    $service->delete();
    expect(Service::find($service->id))->toBeNull()
        ->and(Service::withTrashed()->find($service->id))->not->toBeNull();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ServiceModelTest`
Expected: FAIL — `Class "App\Http\Models\Service" not found`.

- [ ] **Step 3: Write the migrations**

```php
<?php // database/migrations/2026_06_28_000100_create_services_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('services'); }
};
```

```php
<?php // database/migrations/2026_06_28_000200_create_service_translations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_translations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('service_id');
            $table->string('locale')->index();
            $table->string('title', 120);
            $table->string('slug', 160);
            $table->string('icon')->nullable();
            $table->string('excerpt', 255)->nullable();
            $table->mediumText('content')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('meta_noindex')->default(false);
            $table->integer('status')->default(0);
            $table->timestamps();

            $table->unique(['service_id', 'locale']);
            $table->unique(['locale', 'slug']);
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }
    public function down(): void { Schema::dropIfExists('service_translations'); }
};
```

- [ ] **Step 4: Write the models** (clone `app/Http/Models/Page.php` shape; Service has `sort_order` + `scopeOrdered`, no tags/likes)

```php
<?php // app/Http/Models/Service.php
namespace App\Http\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model implements TranslatableContract
{
    use Cachable;
    use SoftDeletes;
    use Translatable;

    public const STATUS_PUBLISHED = 1;

    public $timestamps = false;

    protected $fillable = ['sort_order'];

    public $translatedAttributes = [
        'service_id', 'locale', 'title', 'slug', 'icon', 'excerpt', 'content',
        'thumbnail', 'meta_description', 'meta_keywords', 'canonical_url',
        'meta_noindex', 'status', 'created_at', 'updated_at',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
```

```php
<?php // app/Http/Models/ServiceTranslation.php
namespace App\Http\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

class ServiceTranslation extends Model
{
    use Cachable;

    protected $fillable = [
        'service_id', 'locale', 'title', 'slug', 'icon', 'excerpt', 'content',
        'thumbnail', 'meta_description', 'meta_keywords', 'canonical_url',
        'meta_noindex', 'status', 'created_at', 'updated_at',
    ];

    protected $casts = [
        'meta_noindex' => 'boolean',
    ];
}
```

> Note: confirm the exact `Translatable`/`TranslatableContract` import paths against `app/Http/Models/Page.php` and the `Cachable` FQCN — copy them verbatim from there if they differ.

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=ServiceModelTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_28_000100_create_services_table.php database/migrations/2026_06_28_000200_create_service_translations_table.php app/Http/Models/Service.php app/Http/Models/ServiceTranslation.php tests/Feature/Services/ServiceModelTest.php
git commit -m "feat(services): add Service + ServiceTranslation models and migrations"
```

---

### Task 2: Permission plumbing (`manage_services`)

**Files:**
- Modify: `database/seeds/UserPermissionsSeeder.php` (add `['name' => 'manage_services']`)
- Modify: `app/Policies/UserPolicy.php` (add `manage_services(): bool`)
- Create: `app/Http/Middleware/ManageServices.php`
- Modify: `app/Http/Kernel.php` (alias `'manage_services' => ManageServices::class`)
- Test: `tests/Feature/Services/ServicePermissionTest.php`

**Interfaces:**
- Produces: middleware alias `manage_services`; policy ability `manage_services`. Consumed by Task 8 (admin routes) and Task 13 (MCP `AuthorizesAccess`).

- [ ] **Step 1: Write the failing test** (mirror how `tests/Feature/Admin/PostCrudTest.php` builds an admin user — reuse its helper/seeding so the role JSON contains `manage_services`)

```php
<?php // tests/Feature/Services/ServicePermissionTest.php

use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Illuminate\Support\Facades\Auth;

it('grants manage_services to the seeded administrator role', function () {
    $this->seed();
    $admin = User::whereHas('role', fn ($q) => $q->where('id', 1))->first();
    Auth::login($admin);

    expect($admin->can('manage_services', UserRoles::class))->toBeTrue();
});
```

> If `PostCrudTest` uses a different admin bootstrap (factory/helper) than `$this->seed()`, copy that exact mechanism here instead.

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ServicePermissionTest`
Expected: FAIL — ability returns false (permission not seeded / policy method missing).

- [ ] **Step 3: Add the permission to the seeder**

In `database/seeds/UserPermissionsSeeder.php`, add to the insert array (before `see_admin_panel` is fine):

```php
['name' => 'manage_services'],
```

- [ ] **Step 4: Add the policy method**

In `app/Policies/UserPolicy.php`, alongside `manage_posts()`:

```php
public function manage_services(): bool
{
    return $this->has('manage_services');
}
```

- [ ] **Step 5: Create the middleware** (clone `app/Http/Middleware/ManagePosts.php`)

```php
<?php // app/Http/Middleware/ManageServices.php
namespace App\Http\Middleware;

use App\Http\Models\UserRoles;
use Closure;
use Illuminate\Support\Facades\Auth;

class ManageServices
{
    public function handle($request, Closure $next)
    {
        if (Auth::user()->cannot('manage_services', UserRoles::class)) {
            abort(401);
        }

        return $next($request);
    }
}
```

- [ ] **Step 6: Register the alias** in `app/Http/Kernel.php` next to `'manage_posts'`:

```php
'manage_services' => \App\Http\Middleware\ManageServices::class,
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `php artisan test --filter=ServicePermissionTest`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add database/seeds/UserPermissionsSeeder.php app/Policies/UserPolicy.php app/Http/Middleware/ManageServices.php app/Http/Kernel.php tests/Feature/Services/ServicePermissionTest.php
git commit -m "feat(services): add manage_services permission, policy ability and middleware"
```

---

### Task 3: Front repository (`ServiceRepository`)

**Files:**
- Create: `app/Repositories/ServiceRepository.php`
- Test: `tests/Feature/Services/ServiceRepositoryTest.php`

**Interfaces:**
- Consumes: `BaseRepository` (`$main_table`, `$translated_table`, `$translated_table_join_column`, `$select_fields`, `$translated_table_model`). Read `app/Repositories/PageRepository.php` for the exact protected property names and the `applyFrontReadScope` hook signature — copy them verbatim.
- Produces: `ServiceRepository::publishedOrdered(?string $locale = null): \Illuminate\Support\Collection` (published services, ordered by `sort_order`), `resolveBySlug($slug)` (inherited/overridden to return a single published service translation), `sitemapEntries(): \Illuminate\Support\Collection`.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Services/ServiceRepositoryTest.php

use App\Http\Models\Service;
use App\Repositories\ServiceRepository;

beforeEach(function () {
    $this->repo = app(ServiceRepository::class);
});

function makeService(int $order, string $slug, int $status): Service {
    $s = Service::create(['sort_order' => $order]);
    $t = $s->translateOrNew('en');
    $t->title = ucfirst($slug);
    $t->slug = $slug;
    $t->status = $status;
    $s->save();
    return $s;
}

it('returns only published services ordered by sort_order', function () {
    makeService(2, 'second', Service::STATUS_PUBLISHED);
    makeService(1, 'first', Service::STATUS_PUBLISHED);
    makeService(0, 'draft', 0);

    $result = $this->repo->publishedOrdered('en');

    expect($result)->toHaveCount(2)
        ->and($result->pluck('slug')->all())->toBe(['first', 'second']);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ServiceRepositoryTest`
Expected: FAIL — `Class "App\Repositories\ServiceRepository" not found`.

- [ ] **Step 3: Implement the repository** (clone `PageRepository`; the protected property names below MUST match `BaseRepository`'s expectations — verify against `PageRepository`)

```php
<?php // app/Repositories/ServiceRepository.php
namespace App\Repositories;

use App\Http\Models\Service;
use App\Http\Models\ServiceTranslation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServiceRepository extends BaseRepository
{
    protected $main_table = 'services';
    protected $translated_table = 'service_translations';
    protected $translated_table_join_column = 'service_id';
    protected $select_fields = [
        'id', 'sort_order', 'title', 'slug', 'icon', 'excerpt', 'content',
        'thumbnail', 'meta_description', 'meta_keywords', 'canonical_url',
        'meta_noindex', 'status',
    ];

    public function __construct(Service $model)
    {
        parent::__construct();
        $this->model = $model;
        $this->translated_table_model = new ServiceTranslation;
    }

    protected function applyFrontReadScope($query)
    {
        return $query->where('service_translations.status', '=', Service::STATUS_PUBLISHED);
    }

    public function publishedOrdered(?string $locale = null): Collection
    {
        $locale = $locale ?: app()->getLocale();

        return DB::table('services')
            ->join('service_translations', 'services.id', '=', 'service_translations.service_id')
            ->where('service_translations.locale', $locale)
            ->where('service_translations.status', Service::STATUS_PUBLISHED)
            ->whereNull('services.deleted_at')
            ->orderBy('services.sort_order')
            ->orderBy('services.id')
            ->select(array_map(fn ($f) => $f === 'id' ? 'services.id' : "service_translations.$f", $this->select_fields))
            ->get();
    }

    public function sitemapEntries(): Collection
    {
        return DB::table('service_translations')
            ->join('services', 'services.id', '=', 'service_translations.service_id')
            ->whereNull('services.deleted_at')
            ->where('service_translations.status', Service::STATUS_PUBLISHED)
            ->select('service_translations.slug', 'service_translations.locale', 'service_translations.updated_at')
            ->get();
    }
}
```

> Verify the `BaseRepository` constructor signature (some repos call `parent::__construct()` with no args, some bind the model differently) and the `$translated_table_model` vs `$translated_model` property name against `PageRepository` — the front family uses `$translated_table_model`. Match exactly or the base read methods break.

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=ServiceRepositoryTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Repositories/ServiceRepository.php tests/Feature/Services/ServiceRepositoryTest.php
git commit -m "feat(services): add front ServiceRepository (published-ordered + sitemap scopes)"
```

---

### Task 4: Admin repository (`CPanelServiceRepository`)

**Files:**
- Create: `app/Repositories/CPanelServiceRepository.php`
- Test: `tests/Feature/Services/CPanelServiceRepositoryTest.php`

**Interfaces:**
- Consumes: `BaseRepository` write methods. Read `app/Repositories/CPanelPageRepository.php` for `delete/destroy/restore` shapes and the `$non_persisted_fields`/`$translated_model` property names.
- Produces: `CPanelServiceRepository` with `delete($id)`, `destroy($id)`, `restore($id)`, `trashed($count)` plus inherited create/update.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Services/CPanelServiceRepositoryTest.php

use App\Http\Models\Service;
use App\Repositories\CPanelServiceRepository;

it('soft-deletes then restores a service', function () {
    $repo = app(CPanelServiceRepository::class);
    $service = Service::create(['sort_order' => 0]);

    $repo->delete($service->id);
    expect(Service::find($service->id))->toBeNull();

    $repo->restore($service->id);
    expect(Service::find($service->id))->not->toBeNull();
});

it('permanently destroys a trashed service', function () {
    $repo = app(CPanelServiceRepository::class);
    $service = Service::create(['sort_order' => 0]);

    $repo->delete($service->id);
    $repo->destroy($service->id);

    expect(Service::withTrashed()->find($service->id))->toBeNull();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=CPanelServiceRepositoryTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement the repository** (clone `CPanelPageRepository`)

```php
<?php // app/Repositories/CPanelServiceRepository.php
namespace App\Repositories;

use App\Http\Models\Service;
use App\Http\Models\ServiceTranslation;

class CPanelServiceRepository extends BaseRepository
{
    protected $main_table = 'services';
    protected $translated_table = 'service_translations';
    protected $translated_table_join_column = 'service_id';
    protected $select_fields = ['id', 'sort_order', 'slug', 'status', 'created_at', 'updated_at'];

    public function __construct(Service $model)
    {
        parent::__construct();
        $this->model = $model;
        $this->translated_model = new ServiceTranslation;
    }

    public function trashed($count)
    {
        return $this->model->onlyTrashed()->ordered()->paginate($count);
    }

    public function delete($id)
    {
        $ids = is_array($id) ? $id : [$id];

        return $this->model->whereIn('id', $ids)->delete();
    }

    public function destroy($id)
    {
        $ids = is_array($id) ? $id : [$id];

        return $this->model->withTrashed()->whereIn('id', $ids)->forceDelete();
    }

    public function restore($id)
    {
        $ids = is_array($id) ? $id : [$id];

        return $this->model->withTrashed()->whereIn('id', $ids)->restore();
    }
}
```

> Match `delete/destroy/restore` signatures and the `$translated_model` (admin family) vs `$translated_table_model` (front family) property name to `CPanelPageRepository` exactly. If `CPanelPageRepository` already implements these in `BaseRepository`, drop the overrides and only keep what differs.

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=CPanelServiceRepositoryTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Repositories/CPanelServiceRepository.php tests/Feature/Services/CPanelServiceRepositoryTest.php
git commit -m "feat(services): add CPanelServiceRepository (trash/restore/destroy)"
```

---

### Task 5: Service layer (front + admin)

**Files:**
- Create: `app/Services/Front/ServiceViewService.php`
- Create: `app/Services/CPanel/CPanelServiceService.php`
- Test: `tests/Feature/Services/CPanelServiceServiceTest.php`

**Interfaces:**
- Consumes: `App\Services\BaseCrudService` (`list`, `getById`, `resolveBySlug`, `create`, `update`, `delete`, `destroy`, `restore`), `ServiceRepository`, `CPanelServiceRepository`.
- Produces: `ServiceViewService::publishedOrdered(?string $locale = null)` (delegates to repo); `CPanelServiceService::trashed($count)`, `CPanelServiceService::runBulkAction(string $action, array $ids)` (`'restore'|'destroy'|'delete'`).

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Services/CPanelServiceServiceTest.php

use App\Http\Models\Service;
use App\Services\CPanel\CPanelServiceService;

it('runs a restore bulk action through the service', function () {
    $svc = app(CPanelServiceService::class);
    $service = Service::create(['sort_order' => 0]);
    $service->delete();

    $svc->runBulkAction('restore', [$service->id]);

    expect(Service::find($service->id))->not->toBeNull();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=CPanelServiceServiceTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement the services** (clone `PostViewService` / `CPanelPostService`, minus revisions/tags)

```php
<?php // app/Services/Front/ServiceViewService.php
namespace App\Services\Front;

use App\Repositories\ServiceRepository;
use App\Services\BaseCrudService;
use Illuminate\Support\Collection;

class ServiceViewService extends BaseCrudService
{
    public function __construct(private ServiceRepository $repo)
    {
        parent::__construct($repo);
    }

    public function publishedOrdered(?string $locale = null): Collection
    {
        return $this->repo->publishedOrdered($locale);
    }
}
```

```php
<?php // app/Services/CPanel/CPanelServiceService.php
namespace App\Services\CPanel;

use App\Repositories\CPanelServiceRepository;
use App\Services\BaseCrudService;

class CPanelServiceService extends BaseCrudService
{
    public function __construct(private CPanelServiceRepository $repo)
    {
        parent::__construct($repo);
    }

    public function trashed($count)
    {
        return $this->repo->trashed($count);
    }

    public function runBulkAction(string $action, array $ids): void
    {
        match ($action) {
            'restore' => $this->repo->restore($ids),
            'destroy' => $this->repo->destroy($ids),
            'delete' => $this->repo->delete($ids),
            default => null,
        };
    }
}
```

> Confirm `BaseCrudService::__construct(protected BaseRepository $repository)` signature and that `create($request)`/`update($id,$data)` exist (they do per `PostViewService`). Service classes must NOT call Eloquent directly (arch rule 2) — only the repo.

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=CPanelServiceServiceTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Front/ServiceViewService.php app/Services/CPanel/CPanelServiceService.php tests/Feature/Services/CPanelServiceServiceTest.php
git commit -m "feat(services): add front + admin Service services over the repositories"
```

---

### Task 6: Observers (slug generation + content sanitization)

**Files:**
- Create: `app/Observers/ServiceObserver.php`
- Create: `app/Observers/ServiceTranslationObserver.php`
- Modify: `app/Providers/ObserverServiceProvider.php` (register both)
- Test: `tests/Feature/Services/ServiceObserverTest.php`

**Interfaces:**
- Consumes: `App\Observers\AgenticCmsLaravelObserver` base, `mews/purifier` `clean()`.
- Produces: auto-slug from title when slug empty; `content`/`excerpt` sanitized on save.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Services/ServiceObserverTest.php

use App\Http\Models\Service;

it('auto-generates a slug from the title when none is given', function () {
    $service = Service::create(['sort_order' => 0]);
    $t = $service->translateOrNew('en');
    $t->title = 'Cloud Migration Services';
    $t->status = Service::STATUS_PUBLISHED;
    $service->save();

    expect($service->fresh()->translate('en')->slug)->toBe('cloud-migration-services');
});

it('sanitizes script tags out of content on save', function () {
    $service = Service::create(['sort_order' => 0]);
    $t = $service->translateOrNew('en');
    $t->title = 'XSS';
    $t->slug = 'xss';
    $t->content = '<p>ok</p><script>alert(1)</script>';
    $t->status = Service::STATUS_PUBLISHED;
    $service->save();

    expect($service->fresh()->translate('en')->content)->not->toContain('<script>');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ServiceObserverTest`
Expected: FAIL — observers not registered (slug null / script retained).

- [ ] **Step 3: Implement the observers** (clone `PostObserver` slug logic + `PostTranslationObserver` sanitize; drop the categories/tags/revision parts)

```php
<?php // app/Observers/ServiceObserver.php
namespace App\Observers;

use App\Http\Models\Service;
use Illuminate\Support\Str;

class ServiceObserver extends AgenticCmsLaravelObserver
{
    public function saving(Service $service): void
    {
        // No category/tag sync for services. Slug is handled on the translation.
    }
}
```

```php
<?php // app/Observers/ServiceTranslationObserver.php
namespace App\Observers;

use App\Http\Models\ServiceTranslation;
use Illuminate\Support\Str;

class ServiceTranslationObserver extends AgenticCmsLaravelObserver
{
    public function saving(ServiceTranslation $translation): void
    {
        if (empty($translation->slug) && ! empty($translation->title)) {
            $translation->slug = Str::slug($translation->title);
        }

        if (! empty($translation->content)) {
            $translation->content = clean($translation->content);
        }
        if (! empty($translation->excerpt)) {
            $translation->excerpt = clean($translation->excerpt);
        }
    }
}
```

> Check whether `PostObserver` generates the slug in the parent `saving` or the translation observer — replicate wherever Post does it. The test asserts the translation slug, so the `ServiceTranslationObserver::saving` placement above is correct. Confirm `clean()` is the global helper used by `PostTranslationObserver`.

- [ ] **Step 4: Register the observers** in `app/Providers/ObserverServiceProvider.php` `boot()`:

```php
\App\Http\Models\Service::observe(\App\Observers\ServiceObserver::class);
\App\Http\Models\ServiceTranslation::observe(\App\Observers\ServiceTranslationObserver::class);
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=ServiceObserverTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Observers/ServiceObserver.php app/Observers/ServiceTranslationObserver.php app/Providers/ObserverServiceProvider.php tests/Feature/Services/ServiceObserverTest.php
git commit -m "feat(services): add Service observers (auto-slug + content sanitization)"
```

---

### Task 7: Form requests (validation)

**Files:**
- Create: `app/Http/Requests/ValidateServiceData.php`
- Create: `app/Http/Requests/ServiceListRequest.php`
- Test: covered indirectly by Task 8 (controller) — no standalone test file required, but add `tests/Feature/Services/ValidateServiceDataTest.php` if `AgenticCmsLaravelRequest` is unit-testable in isolation (it is for Post — see `tests/` if a `ValidatePostDataTest` exists; mirror it).

**Interfaces:**
- Consumes: `App\Http\Requests\AgenticCmsLaravelRequest` (`$table`, `$ignore_column`, `newRecordRule()`/`updateRecordRule()` — read `ValidatePostData.php`).
- Produces: `ValidateServiceData` (`rules()` for the Service fields), `ServiceListRequest` (`services_action` in `['delete','destroy','restore']`, `services` array).

- [ ] **Step 1: Implement `ValidateServiceData`** (clone `ValidatePostData`; swap fields, drop `category`/`author_id`/`scheduled_at`)

```php
<?php // app/Http/Requests/ValidateServiceData.php
namespace App\Http\Requests;

class ValidateServiceData extends AgenticCmsLaravelRequest
{
    protected $table = 'service_translations';
    protected $ignore_column = 'service_id';

    public function authorize()
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'meta_noindex' => $this->boolean('meta_noindex'),
        ]);
    }

    public function rules()
    {
        return [
            'title' => ['string', 'required', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160'],
            'icon' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'url'],
            'meta_keywords' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'meta_noindex' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'status' => ['required', 'numeric'],
        ];
    }
}
```

> If `ValidatePostData` adds a uniqueness rule for `slug` via `newRecordRule()`/`updateRecordRule()`, replicate that here keyed on `slug` (the Service unique key is `['locale','slug']`). Copy the exact mechanism from `ValidatePostData`.

- [ ] **Step 2: Implement `ServiceListRequest`** (clone `PostListRequest`)

```php
<?php // app/Http/Requests/ServiceListRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ServiceListRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'services_action' => ['required', 'string', Rule::in(['delete', 'destroy', 'restore'])],
            'services' => ['required', 'array'],
        ];
    }
}
```

- [ ] **Step 3: Verify it loads** (lint + analyse)

Run: `composer lint && composer analyse`
Expected: no errors on the two new files.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Requests/ValidateServiceData.php app/Http/Requests/ServiceListRequest.php
git commit -m "feat(services): add Service form requests (validate + bulk list)"
```

---

### Task 8: Admin controller, routes, navigation

**Files:**
- Create: `app/Http/Controllers/CPanel/CPanelServiceController.php`
- Modify: `routes/web.php` (admin `services` group under `manage_services`)
- Modify: `resources/views/cpanel/nav/left-nav.blade.php` (Services link + active-route detection)
- Modify: `resources/lang/{en,ru}/cpanel/nav/left.php` (`'services' => ...`)
- Test: `tests/Feature/Services/ServiceAdminCrudTest.php`

**Interfaces:**
- Consumes: `CPanelServiceService`, `ValidateServiceData`, `ServiceListRequest`, `CPanelBaseController`.
- Produces: named routes `cpanel_services_list`, `cpanel_trashed_services_list`, `cpanel_add_new_service`, `cpanel_save_new_service`, `cpanel_edit_service`, `cpanel_update_service`, `cpanel_services_bulk_action`, `cpanel_ajax_soft_delete_service`, `cpanel_destroy_service`, `cpanel_restore_service`.

- [ ] **Step 1: Write the failing test** (mirror `tests/Feature/Admin/PostCrudTest.php` admin bootstrap)

```php
<?php // tests/Feature/Services/ServiceAdminCrudTest.php

use App\Http\Models\Service;
use App\Http\Models\User;

beforeEach(function () {
    $this->seed();
    $this->admin = User::whereHas('role', fn ($q) => $q->where('id', 1))->first();
});

it('creates a service via the admin endpoint', function () {
    $this->actingAs($this->admin)->post(route('cpanel_save_new_service'), [
        'title' => 'SEO Audit',
        'slug' => 'seo-audit',
        'content' => '<p>We audit your site.</p>',
        'excerpt' => 'Full technical SEO audit.',
        'meta_keywords' => 'seo, audit',
        'meta_description' => 'SEO audit service',
        'status' => 1,
        'sort_order' => 1,
        'lang' => 'en',
    ])->assertRedirect();

    expect(Service::count())->toBe(1)
        ->and(Service::first()->translate('en')->slug)->toBe('seo-audit');
});

it('lists services for an admin', function () {
    $this->actingAs($this->admin)->get(route('cpanel_services_list'))->assertOk();
});
```

> Copy the exact request payload keys and the `lang`/locale param convention from a passing `PostCrudTest` create case — the admin create flow reads `app('request')` in observers, so the field names must match what `CPanelServiceController::createService` merges.

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ServiceAdminCrudTest`
Expected: FAIL — route `cpanel_services_list` not defined.

- [ ] **Step 3: Implement the controller** (clone `CPanelPostController`; drop revisions, authors, categories, tags, scheduling)

```php
<?php // app/Http/Controllers/CPanel/CPanelServiceController.php
namespace App\Http\Controllers\CPanel;

use App\Http\Requests\ServiceListRequest;
use App\Http\Requests\ValidateServiceData;
use App\Services\CPanel\CPanelServiceService;

class CPanelServiceController extends CPanelBaseController
{
    public function __construct(CPanelServiceService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index()
    {
        return view('cpanel.services.services_list', [
            'services_list' => $this->service->list(20),
            'is_trash' => false,
        ]);
    }

    public function trashedServices()
    {
        return view('cpanel.services.services_list', [
            'services_list' => $this->service->trashed(20),
            'is_trash' => true,
        ]);
    }

    public function addService()
    {
        return view('cpanel.services.new_service');
    }

    public function createService(ValidateServiceData $request, $id = null)
    {
        $this->service->create($request);

        return redirect()->route('cpanel_services_list')
            ->with('success', __('cpanel/services.service_added'));
    }

    public function editService($id, $lang)
    {
        return view('cpanel.services.edit_service', [
            'service' => $this->service->getById($id),
            'lang' => $lang,
        ]);
    }

    public function updateService($id, ValidateServiceData $request)
    {
        $this->service->update($id, $request->validated());

        return redirect()->route('cpanel_services_list')
            ->with('success', __('cpanel/services.updated_success'));
    }

    public function deleteAjax($id)
    {
        $this->service->delete($id);

        return response()->json(['status' => 'ok']);
    }

    public function destroyAjax($id)
    {
        $this->service->destroy($id);

        return response()->json(['status' => 'ok']);
    }

    public function restore($id)
    {
        $this->service->restore($id);

        return redirect()->route('cpanel_trashed_services_list');
    }

    public function multipleActions(ServiceListRequest $request)
    {
        $this->service->runBulkAction($request->services_action, $request->services);

        return back();
    }
}
```

> Align method signatures (`create($request)` vs `create($request->validated())`), redirect/route names, and the edit/update locale param exactly with `CPanelPostController` + `BaseCrudService::create()` so the observers receive the request. If Post's `create` reads `app('request')`, keep passing the `$request` object (not `->validated()`).

- [ ] **Step 4: Add the admin routes** in `routes/web.php` inside the `agentic-cms-laravel-admin` / `CPanel` group, mirroring the posts block:

```php
Route::prefix('services')->middleware('manage_services')->group(function () {
    Route::get('/', 'CPanelServiceController@index')->name('cpanel_services_list');
    Route::get('/trashed', 'CPanelServiceController@trashedServices')->name('cpanel_trashed_services_list');
    Route::get('/{id}/restore', 'CPanelServiceController@restore')->name('cpanel_restore_service');
    Route::get('/new', 'CPanelServiceController@addService')->name('cpanel_add_new_service');
    Route::post('/new/{id?}', 'CPanelServiceController@createService')->name('cpanel_save_new_service');
    Route::get('/{id}/{lang}', 'CPanelServiceController@editService')->name('cpanel_edit_service');
    Route::put('/{id}/update', 'CPanelServiceController@updateService')->name('cpanel_update_service');
    Route::post('/multiple', 'CPanelServiceController@multipleActions')->name('cpanel_services_bulk_action');
    Route::delete('/{id}/destroy', 'CPanelServiceController@destroyAjax')->name('cpanel_destroy_service');
    Route::delete('/{id}/delete', 'CPanelServiceController@deleteAjax')->name('cpanel_ajax_soft_delete_service');
});
```

> Place `/{id}/{lang}` AFTER the static `/new`, `/trashed` routes (as above) so they aren't swallowed. Confirm the admin group's namespace/prefix by copying the posts block's exact location.

- [ ] **Step 5: Add the nav entry** in `resources/views/cpanel/nav/left-nav.blade.php` — add to the active-route array and render a permission-gated link:

```php
// in the $...Active detection block near the top:
$servicesActive = in_array($current_route, ['cpanel_services_list', 'cpanel_trashed_services_list']);
```

```blade
{{-- Services (top-level link) --}}
@if (Auth::user()->can('manage_services', 'App\Http\Models\UserRoles'))
    <a href="{{ route('cpanel_services_list') }}"
       class="{{ $linkBase }} {{ $servicesActive ? $linkActive : $linkIdle }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
        <span>@lang('cpanel/nav/left.services')</span>
    </a>
@endif
```

Add `'services' => 'Services',` to `resources/lang/en/cpanel/nav/left.php` and `'services' => 'Услуги',` to `resources/lang/ru/cpanel/nav/left.php`.

- [ ] **Step 6: Run the test to verify it passes**

Run: `php artisan test --filter=ServiceAdminCrudTest`
Expected: PASS (2 tests). Then `composer lint && composer analyse`.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/CPanel/CPanelServiceController.php routes/web.php resources/views/cpanel/nav/left-nav.blade.php resources/lang/en/cpanel/nav/left.php resources/lang/ru/cpanel/nav/left.php tests/Feature/Services/ServiceAdminCrudTest.php
git commit -m "feat(services): admin CRUD controller, routes and nav entry"
```

---

### Task 9: Admin views (list / new / edit)

**Files:**
- Create: `resources/views/cpanel/services/services_list.blade.php`
- Create: `resources/views/cpanel/services/new_service.blade.php`
- Create: `resources/views/cpanel/services/edit_service.blade.php`
- Create: `resources/lang/en/cpanel/services.php`
- Create: `resources/lang/ru/cpanel/services.php`

**Interfaces:**
- Consumes: `$services_list`, `$is_trash`, `$service`, `$lang` from the controller; `<x-field>`, `<x-button>`, `<x-badge>`, `<x-pagination>`, `<x-empty-state>` components.

- [ ] **Step 1: Build `services_list.blade.php`** by cloning `resources/views/cpanel/pages/pages_list.blade.php`. Keep the `delete_*`/`destroy_*`/`restore_*` hook-class pattern in the `.user_actions` span (see CLAUDE.md asset note). Columns: order (`sort_order`), title, status badge, edit/delete actions. Route names: `cpanel_edit_service`, `cpanel_services_bulk_action`. NOTE: the row-delete AJAX in Pages relies on `public/admin/js/page.js` — Services have no such JS yet; either (a) add a minimal `public/admin/js/service.js` cloned from `page.js` with the selectors `.delete_service`/`.destroy_service` and the `cpanel_ajax_soft_delete_service`/`cpanel_destroy_service` routes, **or** (b) use a plain `<form method="POST">` with `@method('DELETE')` submit buttons (no JS) for delete/destroy. Prefer (b) for a no-new-jQuery footprint; document the choice in the commit.

- [ ] **Step 2: Build `new_service.blade.php`** by cloning `resources/views/cpanel/pages/new_page.blade.php`. Fields via `<x-field>`: title, slug, icon, excerpt, content (rich editor as Pages use), thumbnail, sort_order (number), status (select published/private), SEO fields (meta_description, meta_keywords, canonical_url, meta_noindex). Form posts to `cpanel_save_new_service` with a hidden `lang` input = current lang.

- [ ] **Step 3: Build `edit_service.blade.php`** by cloning `resources/views/cpanel/pages/edit_page.blade.php`; same fields prefilled from `$service->translate($lang)`, language switcher tabs as Pages have, form PUTs to `cpanel_update_service`.

- [ ] **Step 4: Create the lang files** `resources/lang/{en,ru}/cpanel/services.php` with the keys the three blades reference (`list_headline`, `new_service_headline`, `edit_service_headline`, `service_added`, `updated_success`, `table_order`, `table_status`, `status_published`, `status_private`, `field_title`, `field_slug`, `field_icon`, `field_excerpt`, `field_content`, `field_sort_order`, `delete`, `js_delete_confirmation`, etc.). Mirror the key set of `cpanel/posts.php`, trimmed to the service fields.

- [ ] **Step 5: Smoke-test the views**

Run: `php artisan test --filter=ServiceAdminCrudTest`
Expected: still PASS (the list/create endpoints now render the real blades). Manually confirm no `@lang` key is missing by checking the rendered list/new pages don't throw.

- [ ] **Step 6: Commit**

```bash
git add resources/views/cpanel/services/ resources/lang/en/cpanel/services.php resources/lang/ru/cpanel/services.php public/admin/js/service.js
git commit -m "feat(services): admin list/new/edit views + lang strings"
```

---

### Task 10: Front controller + routes (index + show)

**Files:**
- Create: `app/Http/Controllers/ServiceController.php`
- Modify: `routes/web.php` (front `/services` + `/services/{slug}` + localized variants, BEFORE the catch-all)
- Test: `tests/Feature/Services/ServiceFrontTest.php`

**Interfaces:**
- Consumes: `ServiceViewService`, `BaseController`.
- Produces: named routes `services_index`, `services_show`, `services_index_localized`, `services_show_localized`; views `default.services.index`, `default.services.show`.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Services/ServiceFrontTest.php

use App\Http\Models\Service;

function publishService(string $slug, int $status = 1): Service {
    $s = Service::create(['sort_order' => 0]);
    $t = $s->translateOrNew('en');
    $t->title = ucfirst($slug);
    $t->slug = $slug;
    $t->excerpt = 'Summary';
    $t->content = '<p>Body</p>';
    $t->status = $status;
    $s->save();
    return $s;
}

it('shows the services index with published services only', function () {
    publishService('alpha', 1);
    publishService('hidden', 0);

    $this->get('/services')
        ->assertOk()
        ->assertSee('Alpha')
        ->assertDontSee('Hidden');
});

it('shows a single published service by slug', function () {
    publishService('beta', 1);

    $this->get('/services/beta')->assertOk()->assertSee('Beta');
});

it('404s on a draft service detail', function () {
    publishService('secret', 0);

    $this->get('/services/secret')->assertNotFound();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ServiceFrontTest`
Expected: FAIL — `/services` 404 (route missing).

- [ ] **Step 3: Implement the controller** (clone `PostController` index/show + `PageController` for the `$this->data` convention)

```php
<?php // app/Http/Controllers/ServiceController.php
namespace App\Http\Controllers;

use App\Services\Front\ServiceViewService;

class ServiceController extends BaseController
{
    public function __construct(ServiceViewService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index(?string $locale = null)
    {
        if ($locale) {
            app()->setLocale($locale);
            session(['locale' => $locale]);
        }

        return view('default.services.index', [
            'services' => $this->service->publishedOrdered($locale),
        ]);
    }

    public function show(string $slug, ?string $locale = null)
    {
        if ($locale) {
            app()->setLocale($locale);
            session(['locale' => $locale]);
        }

        $service = $this->service->resolveBySlug($slug);

        abort_if($service === null, 404);

        return view('default.services.show', ['service' => $service]);
    }
}
```

> Confirm how `PostController`/`PageController` set the locale and resolve a slug (`resolveBySlug` lives on `BaseCrudService`). If `resolveBySlug` returns unpublished records, add a published guard in `ServiceViewService::resolveBySlug` override (filter `status = STATUS_PUBLISHED`) so the draft-404 test passes. Mirror the front locale-handling exactly from `PostController::index`/`languageIndex`.

- [ ] **Step 4: Register the front routes** in `routes/web.php` — BEFORE the catch-all `/{locale?}/{slug?}`:

```php
Route::get('/services', 'ServiceController@index')->name('services_index');
Route::get('/services/{slug}', 'ServiceController@show')->name('services_show');
Route::get('/{locale}/services', 'ServiceController@index')->name('services_index_localized');
Route::get('/{locale}/services/{slug}', 'ServiceController@show')->name('services_show_localized');
```

> The localized variants pass `$locale` as the first arg; ensure the controller signatures accept it (index `?string $locale`, show `string $slug, ?string $locale`). For the localized routes, bind params so `{slug}` maps to `$slug` and `{locale}` to `$locale` — match the `posts_localized` registration style (it may use `->where('locale', ...)` constraints). Verify against the existing `posts_localized` route.

- [ ] **Step 5: Run the test to verify it fails on views (expected) then proceed to Task 11**

Run: `php artisan test --filter=ServiceFrontTest`
Expected: FAIL — `View [default.services.index] not found`. (Routes resolve; views come in Task 11.) This is the handoff boundary to Task 11.

- [ ] **Step 6: Commit the controller + routes**

```bash
git add app/Http/Controllers/ServiceController.php routes/web.php tests/Feature/Services/ServiceFrontTest.php
git commit -m "feat(services): front ServiceController + routes (index/show, localized)"
```

---

### Task 11: Front views (index grid + detail)

**Files:**
- Create: `resources/views/default/services/index.blade.php`
- Create: `resources/views/default/services/show.blade.php`

**Interfaces:**
- Consumes: `$services` (Collection of stdClass rows from `publishedOrdered`) in index; `$service` (model/translation) in show. Layout `default.layouts.app` (confirm the front layout name from `posts/post.blade.php`), `<x-banner>`/partial banner, `<x-card>` components.

- [ ] **Step 1: Build `index.blade.php`** — extends the front layout used by `default/posts/post.blade.php`. Include the banner partial (`@include('default.partials.banner', ['title' => __('...services...')])`), then a responsive grid (`<x-card>` per service) rendering `icon`, `title` (link to `route('services_show', $s->slug)`), `excerpt`. Empty state via `<x-empty-state>` when `$services` is empty. Pull title/SEO from a services lang file or `geo_settings`.

- [ ] **Step 2: Build `show.blade.php`** — extends the same layout; banner with the service title + breadcrumbs (`Home / Services / {title}`), then the sanitized `content`. Wire `seo-meta` per-entity SEO (title/description from the translation's `meta_*`, `canonical_url`, `meta_noindex`) the same way `posts/post.blade.php` does.

- [ ] **Step 3: Run the front tests**

Run: `php artisan test --filter=ServiceFrontTest`
Expected: PASS (3 tests).

- [ ] **Step 4: Commit**

```bash
git add resources/views/default/services/
git commit -m "feat(services): front index grid + service detail views"
```

---

### Task 12: schema.org Service JSON-LD + sitemap/llms (closes M1)

**Files:**
- Modify: `resources/views/default/partials/seo-meta.blade.php` (homepage `Service` JSON-LD from real records, `geo_settings` fallback)
- Modify: `app/Http/Controllers/SeoController.php` (sitemap: add `ServiceRepository::sitemapEntries()`; `/llms.txt`: list services)
- Create/Modify: a view-composer or helper to expose published services to `seo-meta` on the homepage (e.g. `get_services_for_jsonld()` helper in `bootstrap/agentic-cms-laravel-helpers.php`, or share via the existing SEO composer).
- Test: `tests/Feature/Services/ServiceJsonLdTest.php`

**Interfaces:**
- Consumes: `ServiceRepository::publishedOrdered()`, `sitemapEntries()`.
- Produces: homepage emits `"@type":"Service"` JSON-LD entries derived from real Service rows when present.

- [ ] **Step 1: Write the failing test**

```php
<?php // tests/Feature/Services/ServiceJsonLdTest.php

use App\Http\Models\Service;

it('emits Service JSON-LD on the homepage from real service records', function () {
    $s = Service::create(['sort_order' => 0]);
    $t = $s->translateOrNew('en');
    $t->title = 'Managed Hosting';
    $t->slug = 'managed-hosting';
    $t->excerpt = 'We host and manage your app.';
    $t->status = Service::STATUS_PUBLISHED;
    $s->save();

    $this->get('/')
        ->assertOk()
        ->assertSee('"@type":"Service"', false)
        ->assertSee('Managed Hosting', false);
});

it('lists services in sitemap.xml', function () {
    $s = Service::create(['sort_order' => 0]);
    $t = $s->translateOrNew('en');
    $t->title = 'Managed Hosting'; $t->slug = 'managed-hosting';
    $t->status = Service::STATUS_PUBLISHED; $s->save();

    $this->get('/sitemap.xml')->assertOk()->assertSee('managed-hosting', false);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ServiceJsonLdTest`
Expected: FAIL — homepage JSON-LD still sourced from `geo_settings` textarea only; sitemap lacks the service.

- [ ] **Step 3: Wire the JSON-LD source.** In `seo-meta.blade.php`, find the existing GEO `Service`/JSON-LD block (driven by `get_geo_settings()`). Add: when published Service records exist, emit one `Service` node per record (`name` = title, `description` = excerpt|meta_description, `url` = `route('services_show', $slug)`, `provider` = the Organization node already emitted). Fall back to the `geo_settings` services list only when no Service records exist. Keep it gated by the existing `emit_jsonld` toggle.

- [ ] **Step 4: Wire the sitemap + llms.** In `SeoController`, where post/page sitemap entries are gathered, append `app(ServiceRepository::class)->sitemapEntries()` mapped to `route('services_show', ['slug' => $row->slug])` (or localized) with `lastmod` = `updated_at`. In the `/llms.txt` builder, add a "Services" section listing each published service title + URL.

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=ServiceJsonLdTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add resources/views/default/partials/seo-meta.blade.php app/Http/Controllers/SeoController.php bootstrap/agentic-cms-laravel-helpers.php tests/Feature/Services/ServiceJsonLdTest.php
git commit -m "feat(services): drive Service JSON-LD/sitemap/llms from real records (closes M1 source gap)"
```

---

### Task 13: MCP tools (List / Get / Create / Update / Delete)

**Files:**
- Create: `app/Mcp/Tools/Services/ListServicesTool.php`, `GetServiceTool.php`, `CreateServiceTool.php`, `UpdateServiceTool.php`, `DeleteServiceTool.php`
- Modify: `app/Mcp/Servers/AgenticCmsLaravelServer.php` (use-imports + `$tools` array — append a `// Services` block)
- Test: `tests/Feature/Services/ServiceMcpTest.php`

**Interfaces:**
- Consumes: `CPanelServiceRepository`, concerns `AuthorizesAccess` (gate on `manage_services`), `HydratesRequest`, `ResolvesLocale`. Read `app/Mcp/Tools/Posts/CreatePostTool.php` + `ListPostsTool.php` for the exact `schema()`/`handle()` shape and `Response|ResponseFactory` return type.
- Produces: 5 registered tools → server surface 44 → **49**.

- [ ] **Step 1: Write the failing test** (mirror `tests/Feature/Mcp/*` — they instantiate the tool and call `handle()` with an authorized user)

```php
<?php // tests/Feature/Services/ServiceMcpTest.php

use App\Http\Models\Service;
use App\Http\Models\User;
use App\Mcp\Tools\Services\CreateServiceTool;
// ... mirror the harness a passing Posts MCP test uses (auth + Request building)

it('creates a service through the MCP tool', function () {
    $this->seed();
    $admin = User::whereHas('role', fn ($q) => $q->where('id', 1))->first();
    $this->actingAs($admin);

    // Build the tool Request exactly as tests/Feature/Mcp/PostPublishToolsTest.php does,
    // with payload: title, slug, excerpt, content, status, locale=en
    // assert Service::count() === 1 and the slug persisted.
})->todo('fill harness from the Posts MCP test before implementing');
```

> Replace the `todo()` with the real harness copied from the closest passing Posts MCP test. The exact `Request`/`Response` construction is non-trivial — clone it, don't invent it.

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ServiceMcpTest`
Expected: FAIL — tool classes not found.

- [ ] **Step 3: Implement the 5 tools** by cloning the corresponding `app/Mcp/Tools/Posts/*Tool.php`, swapping `CPanelPostRepository`→`CPanelServiceRepository`, the `manage_posts`→`manage_services` `deny()` gate, and the schema fields to the Service field set (title, slug, icon, excerpt, content, thumbnail, meta_*, sort_order, status, locale). Drop category/tag/author/schedule fields.

- [ ] **Step 4: Register the tools** in `AgenticCmsLaravelServer.php`: add the five `use App\Mcp\Tools\Services\...Tool;` imports (keep alphabetical order in the import block) and a `// Services` group in the `$tools` array.

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=ServiceMcpTest`
Expected: PASS. Update the server's `#[Instructions]` block to mention services require `manage_services`.

- [ ] **Step 6: Commit**

```bash
git add app/Mcp/Tools/Services/ app/Mcp/Servers/AgenticCmsLaravelServer.php tests/Feature/Services/ServiceMcpTest.php
git commit -m "feat(services): MCP tools (list/get/create/update/delete), surface 44->49"
```

---

### Task 14: Seed sample services + finalize

**Files:**
- Create: `database/seeds/CPanelServicesSeeder.php`
- Modify: `database/seeds/DatabaseSeeder.php` (call `CPanelServicesSeeder` after roles/users seed)
- Test: full suite + arch + lint + analyse

**Interfaces:**
- Consumes: `Service`, `ServiceTranslation`.
- Produces: 3 sample services (en + ru), published, ordered.

- [ ] **Step 1: Write the seeder** (clone `database/seeds/CPanelPostsSeeder.php`) — insert 3 `services` rows + en/ru `service_translations` (title, slug, icon, excerpt, content, status=1, sort_order 1..3, timestamps).

- [ ] **Step 2: Register it** in `DatabaseSeeder::run()` after `UserRolesSeeder`/users:

```php
$this->call(CPanelServicesSeeder::class);
```

- [ ] **Step 3: Verify the seed runs**

Run: `php artisan migrate:fresh --seed --env=testing` (or the project's seed test) — expect no errors, 3 services present.

- [ ] **Step 4: Full green gate**

Run: `php artisan test` then `composer lint && composer analyse`
Expected: entire suite PASS (≈ 554 + the new Service tests), arch `LayeringTest` green (no controller/service touches Eloquent), Pint + Larastan clean.

- [ ] **Step 5: Commit**

```bash
git add database/seeds/CPanelServicesSeeder.php database/seeds/DatabaseSeeder.php
git commit -m "feat(services): seed sample services + register seeder"
```

- [ ] **Step 6: Update the convergence matrix + docs.** Mark §1/§9 Service as genuinely first-class (now backed by CRUD). Add a "Service content type" subsection to `CLAUDE.md` (or `docs/`) describing the model/routes/permission/MCP. Update `HANDOFF.md`: M1 resolved. Commit:

```bash
git add CLAUDE.md HANDOFF.md docs/
git commit -m "docs: mark Service content type first-class (M1 resolved); document it"
```

---

## Self-Review

**1. Spec coverage** — the user's decision was "build the first-class Service content type." Coverage: model+migration (T1), permission (T2), front repo (T3), admin repo (T4), services (T5), observers (T6), requests (T7), admin controller/routes/nav (T8), admin views (T9), front controller/routes (T10), front views (T11), JSON-LD/sitemap/llms = the actual M1 source gap (T12), MCP parity (T13), seed + final gate + docs (T14). The matrix's "first-class Service ✅" is now real end-to-end.

**2. Placeholder scan** — net-new code (migrations, models, repository scopes, permission method, middleware, form requests, controllers, routes, observers, JSON-LD test) is given in full. Where a file is a structural clone of an existing one, the plan names the exact template path + the exact deltas (renames, field list, dropped concerns) rather than guessing internal base-class mechanics — this is deliberate (DRY) since the implementer reads the template. The two genuinely uncertain harnesses (MCP tool `Request`/`Response` construction in T13; admin-user bootstrap in T2/T8) are explicitly flagged "clone from the passing Posts test, don't invent."

**3. Type consistency** — field names are fixed by the authoritative table in Global Constraints and reused verbatim across migration, model `$translatedAttributes`, repository `$select_fields`, form request `rules()`, and tools. Route names are consistent (`cpanel_services_list`, `services_index`, `services_show`, etc.). Property-name caveats (`$translated_table_model` front vs `$translated_model` admin) are called out where they bite.

**Known risks to watch during execution:**
- `BaseRepository` constructor/property contract — the front vs admin `$translated_*` property names differ; verify against `PageRepository`/`CPanelPageRepository` before trusting the clones (T3/T4).
- The front catch-all route ordering — `/services*` MUST be registered before `/{locale?}/{slug?}` (T10).
- Observer slug placement — confirm Post generates the slug on the translation, not the parent (T6).
- MCP test harness + admin bootstrap — clone from passing tests (T2, T8, T13).
