<?php
/**
 * AgenticCms-Laravel
 * File: home.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 21.07.2019
 * Template Name: "Home Page";
 * Phase 5: redesigned to DESIGN_SYSTEM §3/§4/§5.
 */
?>

@extends(config('app.template_name').'/index')

@section('content')

    @php
        $headline = get_field('headline', $custom_fields);
        $headline_background = get_field('headline-image', $custom_fields);

        $posts_from_category_headline = get_field('posts-from-category-headline', $custom_fields);
        $posts_from_category_description = get_field('posts-from-category-description', $custom_fields);
        $posts_from_category_category_id = get_field('posts-from-category-cat-id', $custom_fields);

        $about_headline = get_field('about-headline', $custom_fields);
        $about_description = get_field('about-description', $custom_fields);
        $about_big_description = get_field('about-big-text', $custom_fields);
        $authors = get_field('authors', $custom_fields);
    @endphp

{{-- ═══════════════════════════════════════════
     HERO SECTION
     ═══════════════════════════════════════════ --}}
<section class="relative overflow-hidden" id="home">
    @if($headline_background)
        <div class="absolute inset-0 -z-10 bg-[var(--text)]">
            <img
                src="{{ $headline_background }}"
                {!! image_fallback() !!}
                alt=""
                width="1920"
                height="1080"
                fetchpriority="high"
                loading="eager"
                class="h-full w-full object-cover opacity-55"
            >
            <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-black/30 to-black/75"></div>
        </div>
    @else
        <div class="absolute inset-0 -z-10 bg-[var(--text)]"></div>
    @endif

    <div class="mx-auto flex min-h-[72vh] max-w-[1280px] items-center px-4 py-28 sm:px-6 sm:py-32 lg:px-8">
        <div class="max-w-3xl">
            <p class="mb-6 inline-flex items-center gap-3 font-mono text-xs uppercase tracking-[0.08em] text-white/60">
                <span class="h-px w-8 bg-[var(--accent)]" aria-hidden="true"></span>
                {{ $home_page_data->title ?? 'AgenticCms-Laravel' }}
            </p>
            <h1 class="font-serif text-5xl font-medium leading-[1.05] tracking-[-0.01em] text-white [text-wrap:balance] sm:text-6xl lg:text-[clamp(2.5rem,5vw,3.815rem)]">
                {!! $headline !!}
            </h1>
        </div>
    </div>
</section>
{{-- End Hero --}}


@php
    $fields = ['post_translations.title', 'post_translations.slug', 'post_translations.preview','post_translations.updated_at','post_translations.thumbnail','post_translations.likes'];
    $args = ['fields' => $fields, 'category_id' => $posts_from_category_category_id, 'count' => 4];
    $category_posts = get_category_posts($args);
@endphp

@if(!empty($category_posts))
{{-- ═══════════════════════════════════════════
     POSTS-FROM-CATEGORY SECTION
     ═══════════════════════════════════════════ --}}
<section class="mx-auto max-w-[1080px] px-4 py-24 sm:px-6 sm:py-[96px] lg:px-8" id="travel">
    <div class="mb-12 max-w-2xl">
        @if($posts_from_category_headline)
            <h2 class="font-serif text-[clamp(1.875rem,3vw,2.441rem)] font-medium leading-[1.15] tracking-[-0.01em] text-[var(--text)]">
                {{ $posts_from_category_headline }}
            </h2>
        @endif
        @if($posts_from_category_description)
            <p class="mt-4 text-lg leading-relaxed text-[var(--text-muted)]">{{ $posts_from_category_description }}</p>
        @endif
    </div>

    <div class="grid gap-8 sm:grid-cols-2">
        @foreach($category_posts as $post)
            @php
                $post_thumbnail = image_src($post->thumbnail);
                $post_date = \Carbon\Carbon::parse($post->updated_at)->toIso8601String();
                $post_date_display = \Carbon\Carbon::parse($post->updated_at)->format('d M Y');
            @endphp
            <div x-data="reveal({{ $loop->index * 60 }})" class="reveal-init">
                <x-card.post
                    :title="$post->title"
                    :url="config('app.url').'/posts/'.$post->slug"
                    :image="$post_thumbnail"
                    :excerpt="strip_tags($post->preview)"
                    :date="$post_date_display"
                    data-testid="post-link"
                />
            </div>
        @endforeach
    </div>
</section>
{{-- End Posts-from-category --}}
@endif

{{-- ═══════════════════════════════════════════
     ABOUT / TEAM SECTION
     ═══════════════════════════════════════════ --}}
<section class="border-t border-[var(--border)] bg-[var(--surface-2)]" id="team">
    <div class="mx-auto max-w-[1080px] px-4 py-24 sm:px-6 sm:py-[96px] lg:px-8">
        <div class="mb-14 max-w-2xl">
            @if($about_headline)
                <h2 class="font-serif text-[clamp(1.875rem,3vw,2.441rem)] font-medium leading-[1.15] tracking-[-0.01em] text-[var(--text)]">
                    {{ $about_headline }}
                </h2>
            @endif
            @if($about_description)
                <p class="mt-4 text-lg leading-relaxed text-[var(--text-muted)]">{{ $about_description }}</p>
            @endif
        </div>

        <div class="grid gap-12 lg:grid-cols-[1.1fr_1fr] lg:items-start lg:gap-16">
            {{-- Prose body --}}
            <div class="article-prose max-w-prose">
                {!! $about_big_description !!}
            </div>

            {{-- Team cards --}}
            @if(!empty($authors))
                <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-2">
                    @foreach($authors as $author)
                        @php
                            $author_name     = get_field('author-name', $author);
                            $author_image    = get_field('author-image', $author);
                            $author_position = get_field('author-position', $author);
                            $author_linkedin = get_field('author-linkedin', $author);
                        @endphp
                        <x-card :interactive="!empty($author_linkedin['url'])" class="group p-4 text-center">
                            {{-- Avatar --}}
                            <div class="relative mx-auto mb-4 aspect-square w-full max-w-[140px] overflow-hidden rounded-lg bg-[var(--surface-2)]">
                                <img
                                    src="{{ image_src($author_image, true) }}"
                                    {!! image_fallback(true) !!}
                                    alt="{{ $author_name }}"
                                    width="280"
                                    height="280"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition duration-[320ms] ease-[var(--ease-out)] group-hover:scale-105 motion-reduce:transition-none"
                                >
                                @if(!empty($author_linkedin['url']))
                                    <a
                                        href="{{ $author_linkedin['url'] }}"
                                        target="{{ $author_linkedin['target'] === '1' ? '_blank' : '_self' }}"
                                        rel="noopener noreferrer"
                                        aria-label="{{ $author_name }} on LinkedIn"
                                        class="absolute bottom-2 right-2 inline-flex h-8 w-8 translate-y-1 items-center justify-center rounded-full bg-[var(--surface)]/90 text-[var(--text-muted)] opacity-0 shadow-sm backdrop-blur-sm transition duration-[200ms] ease-[var(--ease-out)] hover:text-[var(--primary)] group-hover:translate-y-0 group-hover:opacity-100 motion-reduce:transition-none"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4.98 3.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM3 9h4v12H3zM10 9h3.8v1.7h.05c.53-1 1.83-2.05 3.77-2.05C21.4 8.65 22 11 22 14.1V21h-4v-6.1c0-1.45-.03-3.3-2-3.3s-2.3 1.57-2.3 3.2V21h-4z"/></svg>
                                    </a>
                                @endif
                            </div>

                            {{-- Name & position --}}
                            <p class="font-serif text-base font-medium text-[var(--text)]">{{ $author_name }}</p>
                            @if($author_position)
                                <p class="mt-0.5 font-mono text-xs text-[var(--text-muted)]">{{ $author_position }}</p>
                            @endif
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
{{-- End About / Team --}}

@endsection
