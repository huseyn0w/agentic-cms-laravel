<?php
/**
 * AgenticCms-Laravel
 * File: edit_post.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 01.08.2019
 */
?>

@extends('cpanel.core.index')

@push('extrastyles')
    <link rel="stylesheet" href="{{asset('admin')}}/css/datepicker.min.css">
@endpush

@section('content')
    @php
        $categories_ids = [];
        foreach($entity->categories as $category) $categories_ids[] = $category->id;
        $tags_value = $entity->tags->pluck('name')->implode(', ');
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/posts.edit_headline')</h1>
            <p class="mt-1 text-sm text-muted">
                @lang('cpanel/posts.url_preview')
                <a href="{{config('app.url')}}/posts/{{ old('slug',$entity->slug) }}" class="font-medium text-primary hover:text-primary-hover">{{config('app.url')}}/posts/{{ old('slug',$entity->slug) }}</a>
            </p>
            </div>
            <x-button variant="ghost" href="{{ route('cpanel_post_revisions', ['id' => $entity->id, 'lang' => get_current_lang()]) }}">@lang('cpanel/revisions.revisions_link')</x-button>
        </div>

        @include('cpanel.core.flash')
        @if (($update_message = Session::get('message')) !== null)
            <div class="alert {{ $update_message ? 'alert-success' : 'alert-danger' }}"><strong>{{ $update_message ? __('cpanel/posts.updated_success') : __('cpanel/posts.updated_error') }}</strong></div>
        @endif

        <form action="{{ route('cpanel_update_post', ['id' => $entity->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method("PUT")
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <x-card>
                        <div class="grid grid-cols-1 gap-x-5 md:grid-cols-2">
                            <x-field label="{{ __('cpanel/posts.title') }}" name="cpanel_title">
                                <input type="text" id="cpanel_title" required class="form-control w-full" name="title" value="{{ old('title', $entity->title) }}">
                            </x-field>
                            <x-field label="{{ __('cpanel/posts.slug') }}" name="cpanel_slug">
                                <input type="text" id="cpanel_slug" required class="form-control w-full" name="slug" value="{{ old('slug',$entity->slug) }}">
                            </x-field>
                        </div>
                        <x-field label="{{ __('cpanel/posts.preview') }}">
                            <textarea name="preview" id="editor" class="my-editor form-control w-full">{{old('preview',$entity->preview)}}</textarea>
                        </x-field>
                        <x-field label="{{ __('cpanel/posts.content') }}">
                            <textarea name="content" id="editor" class="my-editor form-control w-full">{{old('content',$entity->content)}}</textarea>
                        </x-field>
                        @include('cpanel.core.seo')
                    </x-card>
                </div>

                <div class="lg:col-span-1 space-y-5">
                    <x-card>
                        @include('cpanel.core.translation')
                        <x-field label="{{ __('cpanel/posts.category') }}">
                            <select name="category[]" multiple class="form-control category_list multiple_list" id="post_category">
                                @foreach($categories_list as $category)
                                    <option value="{{$category->category_id}}" {{ in_array($category->category_id, $categories_ids) ? 'selected': null}}>{{$category->title}}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field label="{{ __('cpanel/posts.tags') }}">
                            <input type="text" name="tags" class="form-control w-full" id="post_tags" value="{{ old('tags', $tags_value) }}" placeholder="{{ __('cpanel/posts.tags_hint') }}">
                        </x-field>
                        <x-field label="{{ __('cpanel/posts.author') }}">
                            <select name="author_id" id="author_id" class="form-control">
                                @foreach($users_list as $user)
                                    <option value="{{$user->id}}" {{$user->id === $entity->author_id ? 'selected' : ''}}>{{$user->username}}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field label="{{ __('cpanel/posts.publish_date') }}">
                            <input class="form-control w-full" value="{{old('updated_at', $entity->updated_at)}}" autocomplete="off" name="updated_at" required id="date_time_picker" type="text" />
                        </x-field>
                        <x-field label="{{ __('cpanel/posts.status') }}">
                            <select name="status" id="user_role" class="form-control">
                                <option value="0" {{$entity->status === 0 ? 'selected' :null}}>@lang('cpanel/posts.status_private')</option>
                                <option value="1" {{$entity->status === 1 ? 'selected' :null}}>@lang('cpanel/posts.status_published')</option>
                            </select>
                        </x-field>
                        <x-field label="{{ __('cpanel/posts.schedule') }}" help="{{ __('cpanel/posts.schedule_hint') }}">
                            <input class="form-control w-full" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', optional($entity->scheduled_at)->format('Y-m-d\TH:i')) }}">
                        </x-field>
                        <x-field label="{{ __('cpanel/posts.thumbnail') }}" name="custom_input_image">
                            <span class="input-group-btn">
                                <a id="lfm" data-input="thumbnail" data-preview="holder" class="choose-image">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 16-5-5L5 19"/></svg>
                                    @lang('cpanel/posts.thumbnail_label')
                                </a>
                            </span>
                            <input id="thumbnail" class="form-control mt-2" type="hidden" name="thumbnail" value="{{ old('thumbnail', $entity->thumbnail) }}">
                            <div class="post-thumbnail relative mt-3 inline-block" {{ empty($entity->thumbnail) ? "style=display:none;" : null}}>
                                <button type="button" class="remove_thumbnail">X</button>
                                <img src="{{ old('logo_url', $entity->thumbnail) }}" id="post-thumbnail" class="max-h-40 rounded-lg border border-border" alt="Post Thumbnail">
                            </div>
                        </x-field>
                        <x-slot:footer>
                            <div class="flex justify-end">
                                <x-button type="submit" variant="primary">@lang('cpanel/posts.update_button_label')</x-button>
                            </div>
                        </x-slot:footer>
                    </x-card>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('extrascripts')
    {{-- TinyMCE self-hosted (DESIGN_SYSTEM §7: no CDN). Copied to public/admin/js/vendor/tinymce; TinyMCE auto-derives its baseURL from this src so themes/plugins/skins load locally. --}}
    <script src="{{asset('admin')}}/js/vendor/tinymce/tinymce.min.js"></script>
    <script src="{{asset('admin')}}/js/datepicker.min.js"></script>
    <script src="{{asset('admin')}}/js/i18n/datepicker.en.js"></script>
    <script src="{{asset('')}}/vendor/laravel-filemanager/js/lfm.js"></script>
@endpush

@push('finalscripts')
    <script src="{{asset('admin')}}/js/post.js"></script>
    <script>
        var site_url = "<?php echo config('app.url'); ?>/";
    </script>
    <script src="{{asset('admin')}}/js/thumbnail.js"></script>
@endpush
