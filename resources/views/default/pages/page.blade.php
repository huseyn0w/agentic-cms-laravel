<?php
/**
 * Cmstack-Laravel
 * File: page.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 25.10.2019
 * Template Name: "Standart";
 * Phase 5: redesigned to DESIGN_SYSTEM §3/§4/§5 — prose layout with x-breadcrumb.
 */
?>


@extends(config('app.template_name').'/index')

@section('content')

{{-- Page header / title banner --}}
<header class="border-b border-[var(--border)] bg-[var(--surface-2)]">
    <div class="mx-auto max-w-[1080px] px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
        {{-- Breadcrumb --}}
        @if(!empty($home_page_data))
            <x-breadcrumb class="mb-5">
                <x-breadcrumb.item :href="config('app.url')">{{ $home_page_data->title }}</x-breadcrumb.item>
                <x-breadcrumb.item :current="true">{{ $data->title }}</x-breadcrumb.item>
            </x-breadcrumb>
        @endif

        <h1 class="font-serif text-[clamp(2.25rem,4vw,3.052rem)] font-medium leading-[1.08] tracking-[-0.01em] text-[var(--text)] [text-wrap:balance]">
            {{ $data->title }}
        </h1>
        @if(!empty($data->meta_description))
            <p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-[var(--text-muted)]">
                {{ $data->meta_description }}
            </p>
        @endif
    </div>
</header>

{{-- Prose body --}}
<article class="mx-auto max-w-[720px] px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
    @if(!empty($data->content))
        <div class="article-prose">
            {!! $data->content !!}
        </div>
    @else
        <x-empty-state icon="file-text" headline="{{ __('default/page.no_content') }}" />
    @endif
</article>

@endsection
