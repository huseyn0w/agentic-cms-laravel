<?php

namespace App\Http\Middleware;

use App\Support\I18n\TranslationDictionary;
use Illuminate\Http\Request;
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
        'manage_general_settings',
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
            'messages' => fn (): array => app(TranslationDictionary::class)
                ->forLocale(get_current_lang()),
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
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

        foreach (self::ABILITIES as $ability) {
            $can[$ability] = $user !== null && $user->can($ability);
        }

        return $can;
    }
}
