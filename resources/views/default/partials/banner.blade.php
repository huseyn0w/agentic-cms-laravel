<?php
/**
 * AgenticCms-Laravel — shared page banner / breadcrumb partial (Phase 4).
 *
 * @param string      $title        Heading text.
 * @param array|null  $crumbs       Optional breadcrumb items: [['label' => .., 'url' => ..|null], ...]
 *                                  The last item with url === null renders as current page.
 *                                  Mirrors the BreadcrumbList JSON-LD emitted by seo-meta.
 */
?>
<header class="border-b border-[var(--border)] bg-[var(--surface-2)]">
    <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 sm:py-16">
        @if(!empty($crumbs))
            <x-breadcrumb class="mb-4">
                @foreach($crumbs as $crumb)
                    @if(!empty($crumb['url']))
                        <x-breadcrumb.item href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</x-breadcrumb.item>
                    @else
                        <x-breadcrumb.item :current="true">{{ $crumb['label'] }}</x-breadcrumb.item>
                    @endif
                @endforeach
            </x-breadcrumb>
        @endif
        <h1 class="text-3xl font-medium tracking-tight text-[var(--text)] sm:text-4xl lg:text-5xl [text-wrap:balance] font-serif">{{$title}}</h1>
    </div>
</header>
