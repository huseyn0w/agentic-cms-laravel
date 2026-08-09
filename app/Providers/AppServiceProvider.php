<?php

namespace App\Providers;

use App\Support\Content\ContentTypeRegistry;
use App\Support\Hooks;
use App\Support\I18n\TranslationDictionary;
use App\Support\Updater\PathManifest;
use App\Support\Updater\SiteBootstrap;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // P9: the hook engine is a process-wide singleton wrapping the event
        // dispatcher, also aliased as 'hooks' for the @hook Blade directive.
        $this->app->singleton(Hooks::class, fn ($app) => new Hooks($app['events']));
        $this->app->alias(Hooks::class, 'hooks');
        $this->app->singleton(TranslationDictionary::class);

        // The updater's path-ownership manifest, seeded from config/cms.php, so
        // ReleaseBuilder and the updater services can type-hint it.
        $this->app->singleton(
            PathManifest::class,
            fn () => new PathManifest(config('cms.paths', []))
        );

        // Registry of content types contributed by enabled plugins (booted lazily
        // once per request). One instance so the boot happens once.
        $this->app->singleton(ContentTypeRegistry::class);

        // Request-scoped cache for the settings singletons (see
        // cms_settings_singleton()): one ArrayObject per fresh container, so the
        // header/footer/seo-meta don't re-query the same singleton row per call.
        // Writes clear their entry via the FlushesSettingsSingletonCache trait.
        $this->app->scoped('cms.settings.singletons', fn () => new \ArrayObject);

        // Register a fork's site zone provider when present (never present on a
        // stock install). The site zone survives core updates.
        SiteBootstrap::register($this->app);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Phase 4: the public theme is Tailwind. Use Laravel's Tailwind
        // paginator so ->links() (and the pretty_url()/pretty_search_url()
        // helpers that wrap it) emit Tailwind markup instead of Bootstrap.
        Paginator::useTailwind();

        // Content-type plugins ship their table migration in their own dir; load
        // every plugin's migrations/ folder so `migrate` creates the tables. The
        // plugin's content type only appears in the admin when it's enabled.
        foreach (array_merge(
            glob(app_path('Plugins/*/migrations'), GLOB_ONLYDIR) ?: [],
            glob(app_path('Site/Plugins/*/migrations'), GLOB_ONLYDIR) ?: [],
        ) as $migrationPath) {
            $this->loadMigrationsFrom($migrationPath);
        }

        // P9: render a named plugin render-region, e.g. @hook('footer').
        Blade::directive('hook', fn ($expression) => "<?php echo app('hooks')->region({$expression}); ?>");

        // The MCP server authenticates AI clients (e.g. Claude) over OAuth 2.1
        // via Passport. This is the consent screen shown to a logged-in admin
        // when an MCP client requests authorization — see resources/views/mcp/.
        Passport::authorizationView(fn ($parameters) => view('mcp.authorize', $parameters));

        // Access tokens issued to MCP clients are long-lived enough to be
        // practical but still expire; refresh tokens keep the connection alive.
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}
