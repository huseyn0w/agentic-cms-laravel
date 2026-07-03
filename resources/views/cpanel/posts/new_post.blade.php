<?php
/**
 * Cmstack-Laravel
 * File: new_post.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 16.08.2019
 */
?>

@extends('cpanel.core.index')

@push('extrastyles')
    <link rel="stylesheet" href="{{asset('admin')}}/css/datepicker.min.css">
@endpush

@php
    $form_action = route('cpanel_save_new_post');
    if(!empty(request()->route('id')))  $form_action = route('cpanel_save_new_post', ['id' => request()->route('id')]);
@endphp

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/posts.new_post_headline')</h1>
        </div>

        @if (Session::get('post_added'))
            <div class="alert alert-success"><strong>@lang('cpanel/posts.post_added')</strong></div>
        @endif
        @include('cpanel.core.flash')

        <form action="{{ $form_action }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {{-- Main column --}}
                <div class="lg:col-span-2">
                    <x-card>
                        <div class="grid grid-cols-1 gap-x-5 md:grid-cols-2">
                            <x-field label="@lang('cpanel/posts.title')" name="cpanel_title">
                                <input type="text" id="cpanel_title" required class="form-control w-full" name="title" value="{{ old('title') }}">
                            </x-field>
                            <x-field label="@lang('cpanel/posts.slug')" name="cpanel_slug">
                                <input type="text" id="cpanel_slug" required class="form-control w-full" name="slug" value="{{ old('slug') }}">
                            </x-field>
                        </div>
                        <x-field label="@lang('cpanel/posts.preview')">
                            <textarea name="preview" id="editor" class="my-editor form-control w-full">{{old('preview')}}</textarea>
                        </x-field>
                        <x-field label="@lang('cpanel/posts.content')">
                            <textarea name="content" id="editor" class="my-editor form-control w-full">{{old('content')}}</textarea>
                        </x-field>
                        @include('cpanel.core.seo')
                    </x-card>
                </div>

                {{-- Sidebar column --}}
                <div class="lg:col-span-1 space-y-5">
                    <x-card>
                        @include('cpanel.core.translation')
                        <x-field label="@lang('cpanel/posts.category')">
                            <select name="category[]" multiple class="form-control multiple_list" id="post_category">
                                @foreach($categories_list as $category)
                                    <option value="{{$category->category_id}}">{{$category->title}}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field label="@lang('cpanel/posts.author')">
                            <select name="author_id" id="author_id" class="form-control">
                                @foreach($users_list as $user)
                                    <option value="{{$user->id}}" {{$user->username === Auth::user()->username ? 'selected' : ''}}>{{$user->username}}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field label="@lang('cpanel/posts.publish_date')">
                            <input class="form-control w-full" autocomplete="off" name="created_at" value="{{ \Carbon\Carbon::now() }}" required id="date_time_picker" type="text" />
                        </x-field>
                        <x-field label="@lang('cpanel/posts.status')">
                            <select name="status" id="user_role" class="form-control">
                                <option value="0">@lang('cpanel/posts.status_private')</option>
                                <option value="1" selected>@lang('cpanel/posts.status_published')</option>
                            </select>
                        </x-field>
                        <x-field label="@lang('cpanel/posts.schedule')" help="@lang('cpanel/posts.schedule_hint')">
                            <input class="form-control w-full" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}">
                        </x-field>
                        <x-field label="@lang('cpanel/posts.thumbnail')" name="custom_input_image">
                            <span class="input-group-btn">
                                <a id="lfm" data-input="thumbnail" data-preview="holder" class="choose-image">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 16-5-5L5 19"/></svg>
                                    @lang('cpanel/posts.thumbnail_label')
                                </a>
                            </span>
                            <input id="thumbnail" class="form-control mt-2" type="hidden" name="thumbnail" value="{{ old('thumbnail') }}">
                            <div class="post-thumbnail relative mt-3 inline-block" style="display:none;">
                                <button type="button" class="remove_thumbnail">X</button>
                                <img src="{{ old('logo_url') }}" id="post-thumbnail" class="max-h-40 rounded-lg border border-border" alt="Post Thumbnail">
                            </div>
                        </x-field>
                        <x-slot:footer>
                            <div class="flex justify-end">
                                <x-button type="submit" variant="primary">@lang('cpanel/posts.publish_button_label')</x-button>
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
