<?php
/**
 * Cmstack-Laravel
 * File: edit_page.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 16.08.2019
 */
?>

@extends('cpanel.core.index')

@push('extrastyles')
    <link rel="stylesheet" href="{{asset('admin')}}/css/datepicker.min.css">
@endpush

@section('content')
    @php
        $page_slug = $entity->slug === "/" ? '' : $entity->slug;
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-fg">@lang('cpanel/pages.edit_page_headline')</h1>
                <p class="mt-1 text-sm text-muted">
                    @lang('cpanel/pages.url_preview')
                    <a href="{{config('app.url')}}/{{ old('slug',$page_slug) }}" class="font-medium text-primary hover:text-primary-hover">{{config('app.url')}}/{{ old('slug',$page_slug) }}</a>
                </p>
            </div>
            <x-button variant="ghost" href="{{ route('cpanel_page_revisions', ['id' => $entity->id, 'lang' => get_current_lang()]) }}">@lang('cpanel/revisions.revisions_link')</x-button>
        </div>

        @include('cpanel.core.flash')
        @if (($update_message = Session::get('message')) !== null)
            <div class="alert {{ $update_message ? 'alert-success' : 'alert-danger' }}"><strong>{{ $update_message ? __('cpanel/pages.updated_success') : __('cpanel/pages.updated_error') }}</strong></div>
        @endif

        <form action="{{ route('cpanel_update_page', ['id' => $entity->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method("PUT")
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <x-card>
                        <div class="grid grid-cols-1 gap-x-5 md:grid-cols-2">
                            <x-field label="{{ __('cpanel/pages.title') }}" name="cpanel_title">
                                <input type="text" id="cpanel_title" required class="form-control w-full" name="title" value="{{ old('title', $entity->title) }}">
                            </x-field>
                            <x-field label="{{ __('cpanel/pages.slug') }}" name="cpanel_slug">
                                <input type="text" id="cpanel_slug" required class="form-control w-full" name="slug" value="{{ old('slug',$entity->slug) }}">
                            </x-field>
                        </div>
                        <x-field label="{{ __('cpanel/pages.content') }}">
                            <textarea name="content" id="editor" class="my-editor form-control w-full">{{old('content',$entity->content)}}</textarea>
                        </x-field>
                        @include('cpanel.core.seo')
                        @include('cpanel.core.custom-fields')
                    </x-card>
                </div>

                <div class="lg:col-span-1">
                    <x-card>
                        @include('cpanel.core.translation')
                        <x-field label="{{ __('cpanel/pages.author') }}">
                            <select name="author_id" id="author_id" class="form-control">
                                @foreach($users_list as $user)
                                    <option value="{{$user->id}}" {{$user->id === $entity->author_id ? 'selected' : ''}}>{{$user->username}}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field label="{{ __('cpanel/pages.publish_date') }}">
                            <input class="form-control w-full" value="{{old('updated_at', $entity->updated_at)}}" autocomplete="off" name="updated_at" required id="date_time_picker" type="text" />
                        </x-field>
                        <x-field label="{{ __('cpanel/pages.status') }}">
                            <select name="status" id="user_role" class="form-control">
                                <option value="0" {{$entity->status === 0 ? 'selected' :null}}>@lang('cpanel/pages.status_private')</option>
                                <option value="1" {{$entity->status === 1 ? 'selected' :null}}>@lang('cpanel/pages.status_published')</option>
                            </select>
                        </x-field>
                        @if(!empty($page_templates) && $page_templates)
                            <x-field label="{{ __('cpanel/pages.page_template') }}">
                                <select name="template" class="form-control">
                                    @foreach($page_templates as $file_name => $template_header)
                                        <option value="{{$file_name}}" {{$entity->template === $file_name ? 'selected' : null}}>{{$template_header}}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        @endif
                        <x-slot:footer>
                            <div class="flex justify-end">
                                <x-button type="submit" variant="primary">@lang('cpanel/pages.update_button_label')</x-button>
                            </div>
                        </x-slot:footer>
                    </x-card>
                </div>
            </div>
        </form>
    </div>
    @include('cpanel.core.modals')
@endsection

@push('extrascripts')
    {{-- TinyMCE self-hosted (DESIGN_SYSTEM §7: no CDN). Copied to public/admin/js/vendor/tinymce; TinyMCE auto-derives its baseURL from this src so themes/plugins/skins load locally. --}}
    <script src="{{asset('admin')}}/js/vendor/tinymce/tinymce.min.js"></script>
    <script src="{{asset('admin')}}/js/datepicker.min.js"></script>
    <script src="{{asset('admin')}}/js/i18n/datepicker.en.js"></script>
    <script src="{{asset('')}}/vendor/laravel-filemanager/js/lfm.js"></script>
@endpush

@push('finalscripts')
    @include('cpanel.core.custom-fields-variables')
    <script src="{{asset('admin')}}/js/page.js"></script>
    <script src="{{asset('admin')}}/js/custom-fields/custom-text.js"></script>
    <script src="{{asset('admin')}}/js/custom-fields/custom-textarea.js"></script>
    <script src="{{asset('admin')}}/js/custom-fields/custom-image.js"></script>
    <script src="{{asset('admin')}}/js/custom-fields/custom-link.js"></script>
    <script src="{{asset('admin')}}/js/custom-fields/custom-category.js"></script>
    <script src="{{asset('admin')}}/js/custom-fields/custom-repeater.js"></script>
@endpush
