<?php

namespace App\Services\Front;

use App\Http\Models\Menu;
use Illuminate\Support\Facades\Auth;

/**
 * Builds the data the public header and footer need, shaped for the React
 * PublicLayout. This is the shell every migrated public page shares: the
 * navigation menu, language switcher, site options, and the current visitor's
 * auth state. It replaces what header.blade.php / footer.blade.php read from
 * global helpers, so the React shell stays a pure function of props.
 */
class PublicShell
{
    /** @return array<string, mixed> */
    public function build(): array
    {
        $siteOptions = get_site_options();
        $currentLang = get_current_lang();

        return [
            'wordmark' => 'AgenticCms-Laravel',
            'homeUrl' => rtrim(config('app.url'), '/'),
            'logoUrl' => get_site_options('logo_url') ?: null,
            'searchUrl' => route('get_search_page'),
            'currentLang' => strtoupper($currentLang),
            'menu' => $this->menu(),
            'languages' => $this->languages(),
            'general' => [
                'websiteName' => get_general_settings('website_name') ?: config('app.name'),
                'membership' => (bool) get_general_settings('membership'),
            ],
            'site' => [
                'copyright' => $siteOptions?->copyright,
                'linkedinUrl' => $siteOptions?->linkedin_url,
                'githubUrl' => $siteOptions?->github_url,
            ],
            'auth' => $this->auth(),
        ];
    }

    /**
     * Header menu as a nested tree of {title, url, children}. Mirrors the URL
     * rules render_menu() uses: a locale prefix for non-default locales, a
     * posts/ or category/ segment by item type, external links left as-is.
     *
     * @return array<int, array<string, mixed>>
     */
    private function menu(): array
    {
        $locale = get_current_lang();

        $menu = Menu::join('menu_translations', 'menus.id', '=', 'menu_translations.menu_id')
            ->where('menu_translations.locale', $locale)
            ->where('menus.slug', 'header_menu')
            ->value('menu_translations.content');

        if (! $menu) {
            return [];
        }

        $items = json_decode($menu, true);

        return is_array($items) ? $this->mapMenuItems($items) : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function mapMenuItems(array $items): array
    {
        return array_map(function (array $item): array {
            $children = (isset($item['children']) && is_array($item['children']))
                ? $this->mapMenuItems($item['children'])
                : [];

            $type = (string) ($item['type'] ?? '');

            return [
                'title' => $item['title'] ?? '',
                'url' => $this->menuItemUrl($type, (string) ($item['slug'] ?? '/')),
                // The client uses Inertia navigation only for our own migrated
                // page types; custom links may be external or point at a page
                // still on Blade, so they stay full-page loads.
                'internal' => in_array($type, ['pages', 'posts', 'categories'], true),
                'children' => $children,
            ];
        }, array_values($items));
    }

    private function menuItemUrl(string $type, string $slug): string
    {
        // An absolute URL (external link) is used verbatim.
        if (str_contains($slug, 'http')) {
            return $slug;
        }

        $base = rtrim(config('app.url'), '/');

        $localePrefix = get_current_lang() === config('app.locale')
            ? ''
            : get_current_lang().'/';

        $typeSegment = match ($type) {
            'posts' => 'posts/',
            'categories' => 'category/',
            default => '',
        };

        $slug = $slug === '/' ? '' : ltrim($slug, '/');

        return $base.'/'.$localePrefix.$typeSegment.$slug;
    }

    /**
     * Language switcher entries keyed by code: {url, title, icon, current}.
     *
     * @return array<int, array<string, mixed>>
     */
    private function languages(): array
    {
        try {
            $links = get_translation_links();
        } catch (\Throwable) {
            return [];
        }

        if (! is_array($links)) {
            return [];
        }

        $current = get_current_lang();
        $out = [];

        foreach ($links as $code => $info) {
            $out[] = [
                'code' => strtoupper((string) $code),
                'url' => $info['url'] ?? '#',
                'title' => $info['title'] ?? strtoupper((string) $code),
                'icon' => $info['icon'] ?? null,
                'current' => $code === $current,
            ];
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private function auth(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [
                'user' => null,
                'canSeeAdmin' => false,
                'loginUrl' => route('login'),
                'registerUrl' => route('register'),
            ];
        }

        return [
            'user' => [
                'name' => trim(($user->name ?? '').' '.($user->surname ?? '')) ?: $user->username,
            ],
            'canSeeAdmin' => $user->can('see_admin_panel', 'App\Http\Models\UserRoles'),
            'profileUrl' => route('get_user_info'),
            'adminUrl' => route('cpanel_home'),
            'logoutUrl' => route('logout'),
        ];
    }
}
