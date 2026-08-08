<?php

namespace App\Http\Middleware;

use App\Http\Models\UserRoles;
use App\Services\Front\PublicShell;
use App\Support\I18n\TranslationDictionary;
use App\Support\Updater\UpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Admin abilities gated by UserPolicy. Shared as an `auth.can` map so React
     * screens can hide/show controls the same way Blade did with `@can(...)`.
     *
     * @var list<string>
     */
    private const ABILITIES = [
        'see_admin_panel',
        'manage_users',
        'manage_user_roles',
        'manage_posts',
        'manage_post_categories',
        'manage_pages',
        'manage_services',
        'manage_menus',
        'manage_comments',
        'manage_media',
        'manage_newsletter',
        'manage_messages',
        'manage_general_settings',
        'manage_updates',
    ];

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * Note: `messages` is the flat UI-string dictionary for the current locale,
     * built from resources/lang by TranslationDictionary and consumed by
     * react-i18next on the client. See docs/superpowers/plans/2026-07-25-frontend-i18n.md
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $this->sharedUser($request),
                'can' => $this->sharedAbilities($request),
            ],
            'locale' => [
                'current' => get_current_lang(),
                'available' => get_languages(),
            ],
            // Core version identity, shared to every page so the admin can show
            // it and compare it against the release feed.
            'cms' => [
                'version' => cms_version(),
                // Version string of an available update (from the cached
                // background check), or null. Only exposed to admins who can
                // manage updates — it drives the "update available" banner.
                'updateAvailable' => fn () => $this->sharedUpdateAvailable($request),
            ],
            'messages' => fn (): array => app(TranslationDictionary::class)
                ->forLocale(get_current_lang()),
            // Public site chrome (header menu, languages, footer, auth links).
            // Shared so the public pages don't each rebuild it; skipped on admin
            // routes, which use their own AdminLayout and never read it.
            'shell' => fn () => $this->sharedShell($request),
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'newsletter_status' => fn () => $request->session()->get('newsletter_status'),
            ],
        ];
    }

    /**
     * The public site chrome, built once per request and shared to every public
     * Inertia page. Returns null on admin routes (they render AdminLayout and
     * never read it) so the menu/settings queries are skipped there.
     *
     * The two admin-gated *preview* routes (cpanel_preview_post /
     * cpanel_preview_page) are the exception: they sit under the admin prefix but
     * render the public post/page in PublicLayout, which reads the shell. Without
     * it PublicLayout destructures a null shell and the preview crashes, so build
     * the shell for those routes even though they are admin-prefixed.
     *
     * @return array<string, mixed>|null
     */
    private function sharedShell(Request $request): ?array
    {
        $isAdmin = $request->is('agentic-cms-laravel-admin', 'agentic-cms-laravel-admin/*');
        $isPreview = $request->routeIs('cpanel_preview_post', 'cpanel_preview_page');

        if ($isAdmin && ! $isPreview) {
            return null;
        }

        return app(PublicShell::class)->build();
    }

    /**
     * The available-update version string for admins who can manage updates,
     * read from the cached background check. Null for everyone else. Drives the
     * admin "update available" banner without a live feed call per request.
     */
    private function sharedUpdateAvailable(Request $request): ?string
    {
        $user = $request->user();

        if ($user === null || ! $user->can('manage_updates', UserRoles::class)) {
            return null;
        }

        $cached = Cache::get(UpdateService::CACHE_KEY);

        return is_array($cached) && isset($cached['version']) ? (string) $cached['version'] : null;
    }

    /**
     * Minimal, safe projection of the authenticated user (never the whole model).
     *
     * @return array{id: int, name: string, email: string}|null
     */
    private function sharedUser(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    /**
     * Map of admin ability => bool for the current user (all false when guest).
     *
     * @return array<string, bool>
     */
    private function sharedAbilities(Request $request): array
    {
        $user = $request->user();

        $can = [];

        // These abilities are authorized through UserPolicy, which is registered
        // against the UserRoles model — so the model MUST be passed, otherwise
        // the Gate has no policy to resolve and every ability comes back false
        // (which silently emptied the admin sidebar).
        foreach (self::ABILITIES as $ability) {
            $can[$ability] = $user !== null && $user->can($ability, UserRoles::class);
        }

        return $can;
    }
}
