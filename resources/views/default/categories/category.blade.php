<?php
/**
 * Cmstack-Laravel — category archive.
 * Phase 5: redesigned to DESIGN_SYSTEM §5 (card grid, pagination, empty-state).
 */
?>

@php
    $category_posts = $data->posts;
    $current_lang   = get_current_lang_prefix();
@endphp

@extends(config('app.template_name').'/index')

@section('content')

@include(config('app.template_name').'.partials.banner', [
    'title'  => $data->title,
    'crumbs' => [
        ['label' => $home_page_data->title, 'url' => config('app.url')],
        ['label' => $data->title, 'url' => null],
    ],
])

<section class="mx-auto max-w-[1080px] px-5 py-16 sm:px-8 sm:py-20">
    @if(!empty($category_posts) && count($category_posts) > 0)
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($category_posts as $post)
                @php
                    $post_thumbnail = image_src($post->thumbnail);
                    $post_url       = config('app.url').'/'.$current_lang.'posts/'.$post->slug;
                @endphp
                <x-card.post
                    :title="$post->title"
                    :url="$post_url"
                    :excerpt="strip_tags($post->preview)"
                    :category="$data->title"
                    :date="Carbon\Carbon::parse($post->updated_at)->format('Y-m-d')"
                    :image="$post_thumbnail ?: null"
                />
            @endforeach
        </div>

        <div class="mt-12">
            <x-pagination :paginator="$category_posts" />
        </div>
    @else
        <x-empty-state headline="{{ __('default/category.not_found') }}" icon="search">
            @lang('default/category.not_found')
        </x-empty-state>
    @endif
</section>

@endsection
