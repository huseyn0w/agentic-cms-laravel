<?php
/**
 * AgenticCms-Laravel — public services listing grid.
 * DATA: $services  — Collection<stdClass> from ServiceRepository::publishedOrdered().
 *       Each item exposes: id, sort_order, title, slug, icon (nullable),
 *       excerpt (nullable), content, thumbnail (nullable), meta_description,
 *       meta_keywords, canonical_url, meta_noindex, status.
 *       Access as $service->title, $service->slug, etc.  (stdClass, not a model).
 */
?>
@php
    $data = (object) [
        'title'           => __('services.meta_title'),
        'meta_description'=> __('services.meta_description'),
        'meta_keywords'   => null,
        'canonical_url'   => route('services_index'),
        'meta_noindex'    => false,
        'thumbnail'       => null,
    ];
@endphp

@extends(config('app.template_name').'/index')

@section('content')

@php
    // schema.org ItemList of Service nodes, built from the real published
    // records (closes the M1 gap — services are first-class structured data,
    // not just the geo_settings free-text list).
    $serviceLd = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'itemListElement' => $services->values()->map(fn ($s, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'item' => array_filter([
                '@type' => 'Service',
                'name' => $s->title,
                'description' => $s->excerpt,
                'url' => route('services_show', ['slug' => $s->slug]),
            ]),
        ])->all(),
    ];
@endphp
@if($services->isNotEmpty())
    <script type="application/ld+json">{!! json_encode($serviceLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif

@include(config('app.template_name').'.partials.banner', [
    'title' => __('services.index_heading'),
])

<section class="mx-auto max-w-7xl px-5 py-14 sm:px-8 sm:py-16">

    @if($services->isEmpty())
        <x-empty-state :headline="__('services.empty')" icon="inbox" />
    @else
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($services as $service)
                <x-card :interactive="true">
                    @if(!empty($service->icon))
                        <div class="mb-4 text-2xl leading-none" aria-hidden="true">{{ $service->icon }}</div>
                    @endif

                    <h2 class="font-serif text-xl leading-snug text-fg">
                        <a href="{{ route('services_show', ['slug' => $service->slug]) }}"
                           class="hover:text-primary transition-colors">
                            {{ $service->title }}
                        </a>
                    </h2>

                    @if(!empty($service->excerpt))
                        <p class="mt-3 text-sm text-muted line-clamp-3 font-sans">{{ $service->excerpt }}</p>
                    @endif

                    <div class="mt-5">
                        <a href="{{ route('services_show', ['slug' => $service->slug]) }}"
                           class="inline-flex items-center gap-1.5 font-sans text-sm font-medium text-primary hover:underline transition-colors">
                            @lang('services.read_more')
                            <x-icon name="arrow-right" width="14" height="14" />
                        </a>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

</section>

@endsection
