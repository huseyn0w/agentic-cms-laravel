<?php
/**
 * Cmstack-Laravel
 * File: header.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 19.07.2019
 * Phase 4: rebuilt to DESIGN_SYSTEM §5 — sticky header, skip-link,
 *   focus-trapped mobile drawer, public dark/light toggle, x-dropdown
 *   locale switcher, data-testid hooks for browser tests.
 */

// Phase 7: page-level SEO meta is now produced by partials/seo-meta.blade.php
// (title, description, canonical, robots, Open Graph, Twitter, hreflang,
// JSON-LD). The legacy ad-hoc title/description block here was removed.
$author = null;
if(isset($data) && isset($data->author)):
    $author = $data->author->name. ' '.$data->author->surname;
endif;

$logo_url = get_site_options('logo_url');

$current_lang = get_current_lang_prefix();

$languages = get_translation_links();

?>
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="no-js">
<head>
    <meta charset="UTF-8">
    <!-- Mobile Specific Meta -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="msapplication-TileColor" content="#b0322b">
    <meta name="theme-color" content="#fbfbf9">

    {{-- Tiny inline script: apply stored/preferred theme BEFORE first paint to avoid FOUC.
         Reads localStorage (key: cmstack-theme, shared with admin), falls back to
         prefers-color-scheme. Runs before any CSS so .dark is on <html> at parse time. --}}
    <script>
        (function () {
            try {
                var s = localStorage.getItem('cmstack-theme');
                if (s === 'dark' || (!s && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    {{-- Phase 7 SEO/GEO head: title, description, canonical, robots, Open Graph,
         Twitter Card, hreflang alternates, verification tags and JSON-LD. --}}
    @include(config('app.template_name').'.partials.seo-meta')

    @if($author)<meta name="author" content="{{$author}}">@endif

    {{-- CSRF token for AJAX (like / comment). Needed for all visitors, not just auth. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{asset('front/'.config('app.template_name').'/img/apple-touch-icon.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{asset('front/'.config('app.template_name').'/img/favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{asset('front/'.config('app.template_name').'/img/favicon-16x16.png')}}">

    {{-- Fonts are self-hosted via @fontsource-variable (DESIGN_SYSTEM §3/§7).
         Newsreader Variable + Inter Variable + Geist Mono Variable are bundled
         through Vite (resources/css/fonts.css → public/build/assets/*.woff2).
         No Google Fonts / CDN requests; font-display: swap via fontsource.

         DESIGN_SYSTEM §3/§7: preload the two critical woff2 weights so the
         browser fetches them before it parses the CSS @font-face rules, killing
         layout shift on the LCP text. Vite content-hashes the woff2 output, so
         we resolve the current filename from the build manifest via Vite::asset()
         (keyed on the fontsource source path) rather than hardcoding the hash. --}}
    @php
        $criticalFonts = [
            'node_modules/@fontsource-variable/newsreader/files/newsreader-latin-wght-normal.woff2',
            'node_modules/@fontsource-variable/inter/files/inter-latin-wght-normal.woff2',
        ];
    @endphp
    @foreach($criticalFonts as $fontSrc)
        @php
            try { $fontUrl = \Illuminate\Support\Facades\Vite::asset($fontSrc); } catch (\Throwable $e) { $fontUrl = null; }
        @endphp
        @if($fontUrl)
    <link rel="preload" href="{{ $fontUrl }}" as="font" type="font/woff2" crossorigin>
        @endif
    @endforeach

    @stack('extrastyles')

    {{-- Tailwind + Alpine (Vite). Preflight is off globally; the public theme's
         scoped reset lives under .theme-default (see resources/css/app.css).
         This is the ONLY bundle the public theme loads — no jQuery, no legacy
         plugin JS/CSS from public/front/**. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Optional analytics (GA4 / GTM) — rendered ONLY when configured in the
         admin SEO settings, loaded async/deferred. Default = no script at all. --}}
    @include(config('app.template_name').'.partials.analytics')
</head>
<body class="theme-default min-h-[100dvh] bg-[var(--bg)] text-[var(--text)] antialiased">

{{-- Skip to main content — first focusable element (§8 a11y) --}}
<a
    href="#main"
    data-testid="skip-link"
    class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-md focus:bg-[var(--surface)] focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-[var(--text)] focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-[var(--ring)] focus:ring-offset-2"
>@lang('default/header.skip_to_content', [], null) Skip to content</a>

@php
    $menu_params = [
        'menu_type'                    => "list",
        'menu_class'                   => "nav-menu",
        'item_class'                   => "",
        'link_class'                   => "nav-link",
        "item_class_with_submenu"      => 'dropdown',
        "item_link_class_with_submenu" => 'dropdown-toggle inline-flex items-center gap-1',
        "submenu_class"                => "dropdown-menu",
        "sublink_class"                => "dropdown-item",
    ];
    $header_menu = get_menu_data("header_menu", $menu_params);
@endphp

<!-- Start Header Area -->
<header
    x-data="mobileDrawer()"
    :class="scrolled ? 'border-[var(--border)] bg-[var(--bg)]/85 shadow-card backdrop-blur-md' : 'border-transparent bg-[var(--bg)]/60 backdrop-blur-sm'"
    class="sticky top-0 z-40 border-b transition-colors duration-[var(--dur-base)]"
    data-testid="public-header"
>
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-5 sm:px-8">

        {{-- Wordmark (left) --}}
        <a
            href="{{config('app.url')}}"
            class="flex shrink-0 items-center gap-2.5"
            aria-label="@lang('default/header.homepage_title')"
            data-testid="header-wordmark"
        >
            @if($logo_url)
                <img src="{{$logo_url}}" alt="" width="auto" height="36" decoding="async" class="h-9 w-auto">
            @else
                <span class="font-serif text-xl font-semibold tracking-tight text-[var(--text)]">Cmstack-Laravel</span>
            @endif
        </a>

        {{-- Desktop: primary navigation (center/right) --}}
        <nav class="hidden flex-1 items-center justify-center gap-1 lg:flex" aria-label="Primary" data-testid="primary-nav">
            {!! $header_menu !!}
        </nav>

        {{-- Desktop: right utility cluster --}}
        <div class="hidden items-center gap-1 lg:flex">
            {{-- Search --}}
            <a
                href="{{route('get_search_page')}}"
                class="nav-link inline-flex h-9 w-9 items-center justify-center rounded-md text-[var(--text-muted)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--text)]"
                aria-label="@lang('default/header.search')"
                data-testid="header-search"
            >
                <x-icon name="search" width="18" height="18" />
            </a>

            {{-- Locale / language switcher (x-dropdown, mono labels) --}}
            @if(!empty($languages))
            <x-dropdown align="right" data-testid="locale-switcher">
                <x-slot:trigger>
                    <button
                        type="button"
                        aria-haspopup="menu"
                        class="inline-flex h-9 items-center gap-1.5 rounded-md px-2.5 font-mono text-xs font-medium uppercase tracking-[0.06em] text-[var(--text-muted)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--text)]"
                        data-testid="locale-trigger"
                    >
                        {{ strtoupper(get_current_lang()) }}
                        <x-icon name="chevron-down" width="14" height="14" />
                    </button>
                </x-slot:trigger>
                @foreach($languages as $code => $language)
                    <x-dropdown.item
                        href="{{ $language['url'] }}"
                        data-testid="lang-{{ $code }}"
                    >
                        <img src="{{$language['icon']}}" alt="" width="16" height="16" decoding="async" class="h-4 w-4 rounded-sm">
                        <span class="font-mono text-xs uppercase tracking-[0.06em]">{{ strtoupper($code) }}</span>
                        <span class="ml-1 text-xs text-[var(--text-muted)]">{{ $language['title'] }}</span>
                    </x-dropdown.item>
                @endforeach
            </x-dropdown>
            @endif

            {{-- Dark / light toggle --}}
            <button
                type="button"
                data-dark-toggle
                :aria-pressed="isDark ? 'true' : 'false'"
                aria-label="Toggle dark mode"
                @click="handleDarkToggle()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-md text-[var(--text-muted)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--text)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--ring)] focus-visible:ring-offset-2"
                data-testid="dark-toggle"
            >
                <x-icon name="sun" width="18" height="18" x-show="isDark" />
                <x-icon name="moon" width="18" height="18" x-show="!isDark" />
            </button>

            {{-- Auth / dashboard links --}}
            @auth
                @if (Auth::user()->can('see_admin_panel', 'App\Http\Models\UserRoles'))
                    <a href="{{route('cpanel_home')}}" class="nav-link">@lang('default/header.cpanel')</a>
                @endif
                <a href="{{route('get_user_info')}}" class="nav-link">@lang('default/header.profile')</a>
                <a href="{{route('logout')}}" class="btn-ghost ml-1 px-4 py-2">@lang('default/header.logout')</a>
            @else
                <a href="{{route('login')}}" class="nav-link">@lang('default/header.login')</a>
                @if(get_general_settings('membership'))
                <x-button href="{{ route('register') }}" variant="primary" size="sm" class="ml-1">@lang('default/header.register')</x-button>
                @endif
            @endauth
        </div>

        {{-- Mobile: hamburger toggle --}}
        <button
            type="button"
            @click="toggle()"
            :aria-expanded="open.toString()"
            aria-controls="mobile-drawer"
            aria-label="Toggle navigation"
            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[var(--border)] text-[var(--text-muted)] transition active:scale-95 lg:hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--ring)] focus-visible:ring-offset-2"
            data-testid="mobile-menu-button"
        >
            <x-icon name="menu" width="20" height="20" x-show="!open" />
            <x-icon name="close" width="20" height="20" x-show="open" x-cloak />
        </button>
    </div>

    {{-- Mobile drawer — full-height, focus-trapped, Esc closes --}}
    <div
        id="mobile-drawer"
        data-drawer
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-[var(--ease-out)] duration-[var(--dur-slow)] motion-reduce:duration-[0ms]"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-[var(--dur-fast)] motion-reduce:duration-[0ms]"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="border-t border-[var(--border)] bg-[var(--bg)] px-5 pb-6 pt-2 sm:px-8 lg:hidden"
        role="dialog"
        aria-label="Navigation menu"
        aria-modal="true"
    >
        <nav class="flex flex-col gap-0.5 py-2" aria-label="Mobile primary" data-testid="mobile-nav">
            {!! $header_menu !!}
        </nav>

        <div class="mt-2 flex flex-col gap-0.5 border-t border-[var(--border)] pt-3">
            <a href="{{route('get_search_page')}}" class="nav-link">@lang('default/header.search')</a>

            @auth
                @if (Auth::user()->can('see_admin_panel', 'App\Http\Models\UserRoles'))
                    <a href="{{route('cpanel_home')}}" class="nav-link">@lang('default/header.cpanel')</a>
                @endif
                <a href="{{route('get_user_info')}}" class="nav-link">@lang('default/header.profile')</a>
                <a href="{{route('logout')}}" class="nav-link">@lang('default/header.logout')</a>
            @else
                <a href="{{route('login')}}" class="nav-link">@lang('default/header.login')</a>
                @if(get_general_settings('membership'))
                <a href="{{route('register')}}" class="nav-link text-[var(--primary)]">@lang('default/header.register')</a>
                @endif
            @endauth

            {{-- Mobile locale switcher --}}
            @if(!empty($languages))
            <div class="mt-3 border-t border-[var(--border)] pt-3">
                <span class="font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-subtle)]">Language</span>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach($languages as $code => $language)
                        <li>
                            @if($code === get_current_lang())
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-[var(--primary)] bg-[var(--surface-2)] px-3 py-1 font-mono text-xs font-medium uppercase tracking-[0.06em] text-[var(--primary)]">
                                    <img src="{{$language['icon']}}" alt="" width="14" height="14" decoding="async" class="h-3.5 w-3.5 rounded-sm">
                                    {{ strtoupper($code) }}
                                </span>
                            @else
                                <a
                                    href="{{$language['url']}}"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-[var(--border)] px-3 py-1 font-mono text-xs uppercase tracking-[0.06em] text-[var(--text-muted)] transition hover:border-[var(--border-strong)] hover:bg-[var(--surface-2)] hover:text-[var(--text)] active:scale-95"
                                    data-testid="lang-{{ $code }}"
                                >
                                    <img src="{{$language['icon']}}" alt="{{$language['title']}}" width="14" height="14" decoding="async" class="h-3.5 w-3.5 rounded-sm">
                                    {{ strtoupper($code) }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Mobile dark/light toggle --}}
            <button
                type="button"
                @click="handleDarkToggle()"
                :aria-pressed="isDark ? 'true' : 'false'"
                aria-label="Toggle dark mode"
                class="mt-2 inline-flex items-center gap-2 rounded-md px-2 py-2 font-sans text-sm text-[var(--text-muted)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--text)]"
                data-testid="mobile-dark-toggle"
            >
                <x-icon name="sun" width="16" height="16" x-show="isDark" />
                <x-icon name="moon" width="16" height="16" x-show="!isDark" />
                <span x-text="isDark ? 'Light mode' : 'Dark mode'"></span>
            </button>
        </div>
    </div>

    @hook('header')
</header>
<!-- End Header Area -->

<main id="main">
