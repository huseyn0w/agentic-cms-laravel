<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Inertia + React (Phase 0 of the Blade -> Inertia migration). Coexists with the
         legacy Blade/Alpine stack during the strangler migration; see
         ~/.claude/plans/wild-percolating-allen.md --}}
    @viteReactRefresh
    @vite(['resources/js/app.tsx'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
