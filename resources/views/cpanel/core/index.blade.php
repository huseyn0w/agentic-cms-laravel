<?php
/**
 * AgenticCms-Laravel
 * File: index.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 19.07.2019
 */
?>
@include('cpanel.core.header')
<body class="theme-admin">
<div
    class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]"
    x-data="{ sidebarOpen: false }"
    @keydown.escape.window="sidebarOpen = false"
>
    {{-- Mobile backdrop --}}
    <div
        x-cloak
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 z-backdrop lg:hidden admin-backdrop"
    ></div>

    {{-- Sidebar: off-canvas drawer below lg, static rail at lg+.
         Uses .admin-sidebar which is token-driven so .dark flips correctly (§5). --}}
    <aside
        id="admin-sidebar"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="admin-sidebar fixed inset-y-0 left-0 z-sidebar w-[260px] transform overflow-y-auto transition-transform duration-300 ease-out-expo lg:static lg:transform-none"
        aria-label="Main navigation"
        x-ref="sidebar"
    >
        @include('cpanel.nav.left-nav')
    </aside>

    {{-- Main column --}}
    <div class="flex min-h-screen flex-col">
        @include('cpanel.nav.top-nav')
        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8" id="main-content">
            @yield('content')
        </main>
        @include('cpanel.core.footer')
    </div>
</div>
