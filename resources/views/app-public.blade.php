{{--
    Public Inertia root template.

    The strangler migration keeps SEO where it already works: this <head> is
    server-rendered by Blade — title, description, canonical, Open Graph,
    Twitter, hreflang and JSON-LD all come from partials/seo-meta.blade.php,
    UNCHANGED. The controller hands that partial its entity via
    ->withViewData(['data' => ...]), so a crawler (and the SSR renderer) reads a
    complete head with zero SEO logic reimplemented in React.

    Only the <body> is React: @inertia mounts the page, and the SSR process
    pre-renders it so the words are in the HTML before any JS runs. The
    theme-default wrapper + reset live inside PublicLayout, mirroring AuthLayout.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="msapplication-TileColor" content="#b0322b">
    <meta name="theme-color" content="#fbfbf9">

    {{-- Apply stored/preferred theme before first paint to avoid FOUC. Shares
         the localStorage key with the admin panel. --}}
    <script>
        (function () {
            try {
                var s = localStorage.getItem('agentic-cms-theme');
                if (s === 'dark' || (!s && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    {{-- Phase 7 SEO/GEO head — driven by $data (via withViewData). Unchanged
         from the Blade era; see partials/seo-meta.blade.php. --}}
    @include(config('app.template_name').'.partials.seo-meta')

    @isset($author)<meta name="author" content="{{ $author }}">@endisset

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Favicon --}}
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('front/'.config('app.template_name').'/img/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('front/'.config('app.template_name').'/img/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('front/'.config('app.template_name').'/img/favicon-16x16.png') }}">

    {{-- Preload the two critical woff2 weights so text does not shift on LCP.
         Vite content-hashes the output; resolve the current name from the
         manifest rather than hardcoding the hash. --}}
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

    {{-- Public theme stylesheet (.theme-default) + the Inertia/React entry.
         Alpine (app.js) is intentionally NOT loaded: migrated public pages own
         their interactivity in React. --}}
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])

    @include(config('app.template_name').'.partials.analytics')

    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
