<?php
/**
 * Cmstack-Laravel — post detail view.
 * Phase 5: redesigned to DESIGN_SYSTEM §5 (prose, avatar, badges, pagination, field).
 * Functional wiring preserved: postLike, commentThread, editCommentDialog, comment form.
 */
?>

@php
    $category_title = $data->categories[0]->title;
    $category_slug  = $data->categories[0]->slug;
    $author         = $data->author->name . ' ' . $data->author->surname;
    $post_liked     = check_if_post_liked_by_current_user($data->id);
    $post_comments_count = $data->comments->total();
    if (is_logged_in()) $user_id = \Auth()->user()->id;
    $current_lang = get_current_lang_prefix();
@endphp

@extends(config('app.template_name').'/index')

@section('content')

@include(config('app.template_name').'.partials.banner', [
    'title'  => $data->title,
    'crumbs' => [
        ['label' => $home_page_data->title, 'url' => config('app.url')],
        ['label' => $category_title, 'url' => config('app.url').'/'.$current_lang.'category/'.$category_slug],
        ['label' => $data->title, 'url' => null],
    ],
])

<article class="mx-auto max-w-[720px] px-5 py-14 sm:px-8 sm:py-16">

    {{-- Category eyebrow --}}
    <a href="{{ config('app.url') }}/{{ $current_lang }}category/{{ $category_slug }}" class="hover:text-primary transition-colors">
        <x-eyebrow class="mb-2">{{ $category_title }}</x-eyebrow>
    </a>

    {{-- Post title --}}
    <h1 class="mt-3 font-serif text-fg leading-[1.08] tracking-[-0.01em]" style="font-size: clamp(2.25rem,4vw,3.052rem)">
        {{ $data->title }}
    </h1>

    {{-- Byline --}}
    <div class="mt-6 flex items-center gap-3 border-b border-border pb-6">
        <x-avatar :user="$data->author" size="md" />
        <div class="min-w-0">
            <a href="{{ route('show_user', ['username' => $data->author->username]) }}"
               class="font-serif text-base font-medium text-fg hover:text-primary transition-colors">{{ $author }}</a>
            <div class="text-xs text-muted font-mono mt-0.5">
                <time datetime="{{ $data->updated_at->toIso8601String() }}">
                    {{ Carbon\Carbon::parse($data->updated_at)->format('d.m.Y') }}
                </time>
            </div>
        </div>
    </div>

    {{-- Hero image --}}
    @if(!empty($data->thumbnail))
        <figure class="mt-10 overflow-hidden rounded-xl bg-surface-2">
            <img src="{{ $data->thumbnail }}" {!! image_fallback() !!} alt="{{ $data->title }}"
                 width="1280" height="720" loading="eager" fetchpriority="high"
                 decoding="async"
                 class="aspect-[16/9] w-full object-cover">
        </figure>
    @endif

    {{-- Post body (passed through the `the_content` plugin filter) --}}
    <div class="article-prose mt-10">
        {!! app('hooks')->filter('the_content', $data->content) !!}
    </div>

    {{-- Tags --}}
    @if(!empty($data->tags) && count($data->tags) > 0)
        <div class="mt-10 flex flex-wrap items-center gap-2">
            <span class="mr-1 font-mono text-xs uppercase tracking-[0.08em] text-muted">@lang('default/post.tags')</span>
            @foreach($data->tags as $tag)
                <x-badge variant="neutral">
                    <a href="{{ config('app.url') }}/{{ $current_lang }}tag/{{ $tag->slug }}"
                       class="hover:text-fg transition-colors">{{ $tag->name }}</a>
                </x-badge>
            @endforeach
        </div>
    @endif

    {{-- Like bar --}}
    <div class="mt-12 flex flex-wrap items-center justify-between gap-4 rounded-lg border border-border bg-surface px-6 py-5">
        @if(is_logged_in())
            <div
                x-data="postLike({
                    url: '{{ route('handle_post_likes', ['id' => $data->id]) }}',
                    postId: {{ $data->id }},
                    userId: {{ \Auth::user()->id }},
                    liked: {{ $post_liked ? 'true' : 'false' }},
                    likes: {{ (int) $data->likes }},
                    lang: {
                        like: @json(__('default/post.like')),
                        dislike: @json(__('default/post.dislike')),
                        likeAdded: @json(__('default/post.like_added')),
                        likeDeleted: @json(__('default/post.like_deleted')),
                        youOnly: @json(__('default/post.you_only_liked')),
                        youAndPre: @json(__('default/post.you_and_multiple_like_pre')),
                        youAndAfter: @json(__('default/post.you_and_multiple_like_after')),
                        othersAfter: @json(__('default/post.multiple_like_after')),
                        nobody: @json(__('default/post.nobody_likes'))
                    }
                })"
                class="flex flex-wrap items-center gap-4"
            >
                <button
                    type="button"
                    @click="toggle()"
                    :disabled="busy"
                    :class="liked ? 'bg-primary text-primary-contrast border-primary' : 'border-border text-fg hover:border-border-strong'"
                    class="inline-flex items-center gap-2 rounded-full border px-5 py-2.5 text-sm font-medium font-sans transition duration-[var(--dur-fast)] ease-[var(--ease-out)] active:scale-[0.98] disabled:opacity-60 motion-reduce:active:scale-100"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 17.5 3.5 11a4 4 0 1 1 6.5-4.6A4 4 0 1 1 16.5 11z"/></svg>
                    <span x-text="label">@lang($post_liked ? 'default/post.dislike' : 'default/post.like')</span>
                </button>
                <p class="text-sm text-muted font-sans" x-text="summary"></p>
            </div>
        @else
            <div class="flex items-center gap-2.5 text-sm text-muted font-sans">
                <svg class="h-5 w-5 text-primary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 17.5 3.5 11a4 4 0 1 1 6.5-4.6A4 4 0 1 1 16.5 11z"/></svg>
                @if($data->likes > 0)
                    <span><span class="font-semibold text-fg">{{ $data->likes }}</span> @lang('default/post.multiple_like_after')</span>
                @else
                    <span>@lang('default/post.nobody_likes')</span>
                @endif
            </div>
        @endif
    </div>

    {{-- Related posts (FEATURE_MATRIX §1) — other published posts sharing a category/tag --}}
    @if(!empty($related) && count($related) > 0)
        <section class="mt-16 border-t border-border pt-12">
            <h2 class="font-serif text-2xl text-fg">@lang('default/post.related_posts')</h2>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($related as $related_post)
                    <x-card.post
                        :title="$related_post->title"
                        :url="config('app.url').'/'.$current_lang.'posts/'.$related_post->slug"
                        :excerpt="strip_tags($related_post->preview)"
                        :date="Carbon\Carbon::parse($related_post->updated_at)->format('Y-m-d')"
                        :image="image_src($related_post->thumbnail) ?: null"
                    />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Comments section --}}
    <section class="mt-16" x-data="commentThread({
        deleteUrl: '{{ route('delete_post_comments', ['id' => $data->id]) }}',
        confirmText: @json(__('default/post.delete') . '?')
    })">
        <h2 class="font-serif text-2xl text-fg">{{ $post_comments_count }} @lang('default/post.comments')</h2>

        @if($post_comments_count > 0)
            <div class="mt-8 space-y-8">
                @foreach($data->comments as $comment)
                    @include(config('app.template_name').'.partials.comment', ['comment' => $comment, 'user_id' => $user_id ?? null, 'is_reply' => false])

                    @if(count($comment->replies) > 0)
                        <div data-comment-replies class="ml-6 space-y-8 border-l border-border pl-6 sm:ml-10 sm:pl-8">
                            @foreach($comment->replies as $child_comment)
                                @include(config('app.template_name').'.partials.comment', ['comment' => $child_comment, 'user_id' => $user_id ?? null, 'is_reply' => true])
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-10">
                <x-pagination :paginator="$data->comments" />
            </div>
        @else
            <p class="mt-6 text-muted font-sans">@lang('default/post.no_comments')</p>
        @endif
    </section>

    {{-- Comment form --}}
    <section class="mt-16 scroll-mt-24" id="comment-area">
        @auth
            <h2 class="font-serif text-2xl text-fg">@lang('default/post.leave_reply')</h2>

            @if ($errors->any())
                <div class="mt-6">
                    <x-alert variant="error">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                </div>
            @endif

            @if ($update_message = Session::get('comment_added'))
                <div class="mt-6">
                    <x-alert variant="success">
                        @if (Auth::user()->can('manage_comments', 'App\Http\Models\UserRoles'))
                            @lang('default/post.comment_added')
                        @else
                            @lang('default/post.comment_send_to_approve')
                        @endif
                    </x-alert>
                </div>
            @endif

            <form action="{{ route('store_post_comments', ['id' => $data->id]) }}" method="POST" class="mt-8">
                @csrf
                <x-field name="comment" label="{{ __('default/post.comment') }}" :error="$errors->first('comment')">
                    <textarea
                        id="comment"
                        name="comment"
                        rows="5"
                        required
                        placeholder="{{ __('default/post.comment') }}"
                        class="w-full resize-y bg-surface border border-border-strong rounded-sm px-3 py-2.5 text-fg placeholder:text-subtle focus:outline-none focus:border-ring focus:ring-2 focus:ring-ring/30 font-sans text-base"
                        @if($errors->has('comment')) aria-invalid="true" aria-describedby="comment-error" @endif
                    ></textarea>
                </x-field>
                <input type="hidden" name="parent_id" id="comment_parent_id" value="">
                <input type="hidden" name="post_id" value="{{ $data->id }}">
                <div class="mt-4">
                    <x-button type="submit" variant="primary">@lang('default/post.comment')</x-button>
                </div>
            </form>
        @else
            <div class="rounded-lg border border-border bg-surface px-6 py-8 text-center">
                <p class="text-muted font-sans">@lang('default/post.comment_auth')</p>
                <div class="mt-4">
                    <x-button href="{{ route('login') }}">@lang('default/header.login')</x-button>
                </div>
            </div>
        @endauth
    </section>
</article>

@auth
    @include('default.posts.modal')
@endauth

@endsection
