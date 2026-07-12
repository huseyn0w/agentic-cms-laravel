<?php
/**
 * AgenticCms-Laravel — public service detail page.
 * DATA: $data — single Service model instance (Astrotomic Translatable).
 *       Translated columns are accessible flat: $data->title, $data->content,
 *       $data->excerpt, $data->slug, $data->thumbnail, $data->meta_description,
 *       $data->meta_keywords, $data->canonical_url, $data->meta_noindex.
 *       Pattern mirrors resources/views/default/posts/post.blade.php.
 */
?>
@extends(config('app.template_name').'/index')

@section('content')

@include(config('app.template_name').'.partials.banner', [
    'title'  => $data->title,
    'crumbs' => [
        ['label' => config('app.name'), 'url' => config('app.url')],
        ['label' => __('services.index_heading'), 'url' => route('services_index')],
        ['label' => $data->title, 'url' => null],
    ],
])

<article class="mx-auto max-w-[720px] px-5 py-14 sm:px-8 sm:py-16">

    {{-- Hero image --}}
    @if(!empty($data->thumbnail))
        <figure class="mb-10 overflow-hidden rounded-xl bg-surface-2">
            <img src="{{ $data->thumbnail }}" {!! image_fallback() !!} alt="{{ $data->title }}"
                 width="1280" height="720" loading="eager" fetchpriority="high"
                 decoding="async"
                 class="aspect-[16/9] w-full object-cover">
        </figure>
    @endif

    {{-- Service excerpt / lead --}}
    @if(!empty($data->excerpt))
        <p class="mb-8 text-lg leading-relaxed text-muted font-sans">{{ $data->excerpt }}</p>
    @endif

    {{-- Service body --}}
    <div class="article-prose">
        {!! app('hooks')->filter('the_content', $data->content) !!}
    </div>

    {{-- Back to services --}}
    <div class="mt-12 border-t border-border pt-8">
        <a href="{{ route('services_index') }}"
           class="inline-flex items-center gap-2 font-sans text-sm font-medium text-muted hover:text-primary transition-colors">
            <x-icon name="arrow-left" width="14" height="14" />
            @lang('services.back_to_services')
        </a>
    </div>

</article>

@endsection
