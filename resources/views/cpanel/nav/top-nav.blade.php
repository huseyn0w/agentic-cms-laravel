<?php
/**
 * AgenticCms-Laravel
 * File: top-nav.blade.php — DESIGN_SYSTEM §5 (Phase 6)
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 21.07.2019
 *
 * Topbar: sticky 56px, bg-surface + 1px border-border bottom (token-driven → dark flips).
 * Left: mobile menu button + current section h4.
 * Right: dark/light toggle (agentic-cms-theme localStorage key), "View site ↗",
 *        language switcher (original URLs/logic preserved),
 *        user <x-avatar> → <x-dropdown> (role badge, profile, sign out).
 * logout: preserved as route('logout') link (GET logout is fine here — no POST form needed).
 */

$languages = get_languages();
?>
<header class="admin-topbar sticky top-0 z-sticky flex h-14 items-center gap-3 px-4 sm:px-6 lg:px-8">
    {{-- Mobile sidebar toggle — controls Alpine sidebarOpen state in core/index --}}
    <button
        type="button"
        @click="sidebarOpen = true"
        class="admin-topbar-btn -ml-1 inline-flex h-9 w-9 items-center justify-center rounded-lg lg:hidden"
        aria-label="{{ __('cpanel/nav/top.open_nav') }}"
        aria-controls="admin-sidebar"
    >
        <x-icon name="menu" width="22" height="22" />
    </button>

    {{-- Current section label (h4 per §5) --}}
    <h4 class="hidden text-sm font-semibold sm:block" style="color:var(--text)">
        @lang('cpanel/nav/top.header_title')
    </h4>

    <div class="ml-auto flex items-center gap-1">

        {{-- ── Dark / light toggle (§5 — admin dark toggle) ──────────── --}}
        {{-- Backed by admin.js adminDarkToggle(); localStorage key = agentic-cms-theme --}}
        <button
            type="button"
            id="admin-dark-toggle"
            data-dark-toggle
            aria-label="Toggle dark mode"
            aria-pressed="false"
            class="admin-topbar-btn inline-flex h-9 w-9 items-center justify-center rounded-lg"
            title="Toggle dark / light"
        >
            {{-- Sun icon shown in dark mode (click to go light); moon shown in light mode --}}
            <x-icon name="sun"  width="18" height="18" class="admin-dark-icon-sun"  aria-hidden="true" />
            <x-icon name="moon" width="18" height="18" class="admin-dark-icon-moon" aria-hidden="true" />
        </button>

        {{-- ── View site ↗ ─────────────────────────────────────────────── --}}
        <a
            href="{{route('front_pages',['slug' => '/'])}}"
            target="_blank"
            rel="noopener"
            class="admin-topbar-link hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium sm:inline-flex"
        >
            <x-icon name="external-link" width="15" height="15" aria-hidden="true" />
            @lang('cpanel/nav/top.homepage')
        </a>

        {{-- ── Language switcher (URLs/logic preserved) ───────────────── --}}
        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
            <button
                type="button"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-haspopup="menu"
                class="admin-topbar-btn inline-flex h-9 items-center gap-1.5 rounded-lg px-2.5 text-sm font-medium"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
                </svg>
                <span class="hidden sm:inline">@lang('cpanel/nav/top.change_language')</span>
                <x-icon name="chevron-down" width="14" height="14" class="transition-transform" ::class="open && 'rotate-180'" aria-hidden="true" />
            </button>
            <div
                x-cloak
                x-show="open"
                x-transition:enter="transition ease-[var(--ease-out)] duration-[var(--dur-base)]"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-[var(--dur-fast)]"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                role="menu"
                aria-orientation="vertical"
                class="admin-dropdown absolute right-0 top-full z-dropdown mt-1.5 w-48 origin-top-right"
            >
                @foreach($languages as $code => $language)
                    <a href="{{route('lang_route', ['locale' => $code])}}"
                       role="menuitem"
                       class="admin-dropdown-item flex items-center gap-2.5 px-3 py-2 text-sm">
                        <img src="{{$language['icon']}}" alt="{{$language['title']}}" class="h-4 w-auto rounded-sm" />
                        <span>{{$language['title']}}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <span class="admin-divider mx-0.5 hidden h-5 w-px sm:block" aria-hidden="true"></span>

        {{-- ── User avatar → dropdown (role badge, profile, sign out) ─── --}}
        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
            <button
                type="button"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-haspopup="menu"
                class="admin-topbar-btn inline-flex h-9 w-9 items-center justify-center rounded-full"
                aria-label="{{ $current_user->username }}"
            >
                <x-avatar :user="$current_user" size="sm" />
            </button>

            <div
                x-cloak
                x-show="open"
                x-transition:enter="transition ease-[var(--ease-out)] duration-[var(--dur-base)]"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-[var(--dur-fast)]"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                role="menu"
                aria-orientation="vertical"
                class="admin-dropdown absolute right-0 top-full z-dropdown mt-1.5 w-52 origin-top-right"
            >
                {{-- User info + role badge --}}
                <div class="admin-dropdown-header px-3 pb-2 pt-3">
                    <p class="text-sm font-semibold" style="color:var(--text)">{{ $current_user->username }}</p>
                    @if($current_user->role)
                        <x-badge variant="neutral" class="mt-1">{{ $current_user->role->title ?? $current_user->role }}</x-badge>
                    @endif
                </div>
                <div class="admin-dropdown-sep" role="separator" aria-hidden="true"></div>
                <a href="{{route('cpanel_myprofile')}}"
                   role="menuitem"
                   class="admin-dropdown-item flex items-center gap-2.5 px-3 py-2 text-sm">
                    <x-icon name="user" width="15" height="15" aria-hidden="true" />
                    @lang('cpanel/nav/top.edit_profile')
                </a>
                <div class="admin-dropdown-sep" role="separator" aria-hidden="true"></div>
                {{-- Sign out — preserved route('logout'); method is GET in this app --}}
                <a href="{{ route('logout') }}"
                   role="menuitem"
                   class="admin-dropdown-item admin-dropdown-item--danger flex items-center gap-2.5 px-3 py-2 text-sm">
                    <svg class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                    </svg>
                    @lang('cpanel/nav/top.log_out')
                </a>
            </div>
        </div>
    </div>
</header>
