<?php
/**
 * Cmstack-Laravel
 * File: search.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 15.11.2019
 * Template Name: "Search Page";
 * Phase 5: redesigned to DESIGN_SYSTEM §3/§4/§5 — x-field, x-empty-state, x-card.post.
 */

$current_lang = get_current_lang_prefix();

?>
@extends(config('app.template_name').'/index')

@section('content')

{{-- ═══════════════════════════════════════════
     SEARCH FORM
     ═══════════════════════════════════════════ --}}
<section class="border-b border-[var(--border)] bg-[var(--surface-2)]">
    <div class="mx-auto max-w-[720px] px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
        <h1 class="mb-8 text-center font-serif text-[clamp(1.875rem,3vw,2.441rem)] font-medium leading-[1.15] tracking-[-0.01em] text-[var(--text)]">
            @lang('default/header.searchpage_title')
        </h1>

        <form action="{{ route('get_search_result') }}" method="POST" class="space-y-5">
            @csrf

            {{-- Query field --}}
            <x-field name="query" :error="$errors->first('query')">
                <div class="relative">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[var(--text-subtle)]" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="9" cy="9" r="6"/><path d="m18 18-4-4" stroke-linecap="round"/></svg>
                    </span>
                    <input
                        type="text"
                        name="query"
                        id="query"
                        required
                        value="{{ isset($searchData['query']) ? $searchData['query'] : '' }}"
                        placeholder="{{ __('default/page.search_placeholder') }}"
                        @if($errors->has('query')) aria-invalid="true" aria-describedby="query-error" @endif
                        class="field-input !rounded-full !py-4 pl-12 pr-36 text-lg"
                    >
                    <x-button type="submit" variant="primary" size="md" class="absolute right-1.5 top-1/2 -translate-y-1/2 !rounded-full !px-5 !py-2.5">
                        @lang('default/header.search')
                    </x-button>
                </div>
            </x-field>

            {{-- Filter --}}
            <x-field name="filter" :label="__('default/page.filter_by')" :error="$errors->first('filter')" class="flex flex-col items-center gap-2 sm:flex-row sm:justify-center">
                <select
                    name="filter"
                    id="filter"
                    @if($errors->has('filter')) aria-invalid="true" aria-describedby="filter-error" @endif
                    class="rounded-full border border-[var(--border-strong)] bg-[var(--surface)] py-2 pl-4 pr-9 font-sans text-sm text-[var(--text)] shadow-sm transition focus:border-[var(--ring)] focus:outline-none focus:ring-2 focus:ring-[var(--ring)]/30"
                >
                    <option value="post">@lang('default/page.filter_post')</option>
                    <option value="page">@lang('default/page.filter_page')</option>
                    <option value="user">@lang('default/page.filter_user')</option>
                    <option value="category">@lang('default/page.filter_category')</option>
                    <option value="tag">@lang('default/page.filter_tag')</option>
                </select>
            </x-field>

            @if(isset($searchData) && count($searchData) > 0)
                @php
                    $results_word = $searchData['result']->total() > 1 ? trans('default/page.results') : trans('default/page.result');
                @endphp
                <p class="text-center font-mono text-xs text-[var(--text-muted)]">
                    {{ $searchData['result']->total() }} {{ $results_word }} @lang('default/page.found_for') &ldquo;{{ $searchData['query'] }}&rdquo;
                </p>
            @endif

            {{-- Captcha --}}
            {!! app('captcha')->render(); !!}
        </form>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     RESULTS
     ═══════════════════════════════════════════ --}}
<section class="mx-auto max-w-[1080px] px-4 py-14 sm:px-6 sm:py-16 lg:px-8">

    {{-- Validation errors --}}
    @if (count($errors) > 0)
        <x-alert variant="error" class="mb-6">
            <ul class="list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    @if(isset($searchData) && $searchData['result']->total() > 0)
        <h2 class="mb-8 font-serif text-[clamp(1.563rem,2vw,1.953rem)] font-medium leading-[1.2] text-[var(--text)]">
            @lang('default/page.search_result_headline'): &ldquo;{{ $searchData['query'] }}&rdquo;
        </h2>

        @php
            $is_post_search = $searchData['type'] === 'post';
        @endphp

        @if($is_post_search)
            {{-- Post results as card grid --}}
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($searchData['result'] as $item)
                    @php
                        $result_url = config('app.url').'/'.$current_lang.'posts/'.$item->slug;
                    @endphp
                    <x-card.post
                        :title="$item->title"
                        :url="$result_url"
                        :excerpt="strip_tags($item->preview ?? '')"
                    />
                @endforeach
            </div>
        @else
            {{-- Non-post results as a styled list --}}
            <ul class="divide-y divide-[var(--border)]">
                @foreach($searchData['result'] as $item)
                    @php
                        if($searchData['type'] === "post") {
                            $result_url = config('app.url').'/'.$current_lang.'posts/'.$item->slug;
                            $result_label = $item->title;
                        } elseif($searchData['type'] === "page") {
                            $result_url = config('app.url').'/'.$item->slug;
                            $result_label = $item->title;
                        } elseif($searchData['type'] === "user") {
                            $result_url = config('app.url').$current_lang.'users/'.$item->username;
                            $result_label = $item->username;
                        } elseif($searchData['type'] === "tag") {
                            $result_url = config('app.url').'/'.$current_lang.'tag/'.$item->slug;
                            $result_label = $item->name;
                        } else {
                            $result_url = config('app.url').'/'.$current_lang.'category/'.$item->slug;
                            $result_label = $item->title;
                        }
                    @endphp
                    <li>
                        <a href="{{ $result_url }}" class="group flex items-center justify-between gap-4 py-5 transition-colors duration-[var(--dur-fast)]">
                            <h3 class="font-serif text-xl font-medium text-[var(--text)] transition-colors duration-[var(--dur-fast)] group-hover:text-[var(--primary)]">
                                {{ $result_label }}
                            </h3>
                            <svg class="h-5 w-5 shrink-0 text-[var(--text-subtle)] transition-transform duration-[var(--dur-fast)] group-hover:translate-x-1 group-hover:text-[var(--primary)]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 10h11M11 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- Pagination --}}
        <div class="mt-10">
            @php
                $links = $searchData['result']->links();
                echo pretty_search_url($links, $searchData['type'], $searchData['query']);
            @endphp
        </div>

    @elseif(isset($searchData) && $searchData['result']->total() === 0)
        <x-empty-state icon="search" headline="{{ __('default/page.search_nothing_found') }}" />
    @endif

</section>

@endsection
