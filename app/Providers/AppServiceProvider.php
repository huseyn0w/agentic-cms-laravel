<?php

namespace App\Providers;

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

        view()->composer('*', function ($view) {
            $view->with('current_user', \Auth::user());
            $view->with('home_page_data', get_data(1, 'page', ['slug', 'title']));
        });
    }
}
