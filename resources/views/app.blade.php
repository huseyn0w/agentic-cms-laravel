<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Inertia + React (Phase 0 of the Blade -> Inertia migration). Coexists with the
         legacy Blade/Alpine stack during the strangler migration; see
         ~/.claude/plans/wild-percolating-allen.md

         Both scoped stylesheets are loaded here because this root serves both
         Inertia surfaces: auth screens under `.theme-default` (app.css) and the
         admin panel under `.theme-admin` (admin.css). Each is a scoped Tailwind
         build, so the one whose wrapper class is absent contributes nothing.
         Loading them in the server-rendered head keeps SSR paints unstyled-free. --}}
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
