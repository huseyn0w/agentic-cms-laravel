<?php
/**
 * Cmstack-Laravel
 * File: new_page.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 16.08.2019
 */
?>

@extends('cpanel.core.index')

@push('extrastyles')
    <link rel="stylesheet" href="{{asset('admin')}}/css/datepicker.min.css">
@endpush

@php
    $form_action = route('cpanel_save_new_page');
    if(!empty(request()->route('id')))  $form_action = route('cpanel_save_new_page', ['id' => request()->route('id')]);
@endphp

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/pages.new_page_headline')</h1>
        </div>

        @include('cpanel.core.flash')

        <form action="{{$form_action}}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <x-card>
                        <div class="grid grid-cols-1 gap-x-5 md:grid-cols-2">
                            <x-field label="{{ __('cpanel/pages.title') }}" name="cpanel_title">
                                <input type="text" id="cpanel_title" required class="form-control w-full" name="title" value="{{ old('title') }}">
                            </x-field>
                            <x-field label="{{ __('cpanel/pages.slug') }}" name="cpanel_slug">
                                <input type="text" id="cpanel_slug" required class="form-control w-full" name="slug" value="{{ old('slug') }}">
                            </x-field>
                        </div>
                        <x-field label="{{ __('cpanel/pages.content') }}">
                            <textarea name="content" id="editor" class="my-editor form-control w-full">{{old('content')}}</textarea>
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
                                    <option value="{{$user->id}}" {{$user->username === Auth::user()->username ? 'selected' : ''}}>{{$user->username}}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field label="{{ __('cpanel/pages.publish_date') }}">
                            <input class="form-control w-full" autocomplete="off" name="created_at" value="{{ \Carbon\Carbon::now() }}" required id="date_time_picker" type="text" />
                        </x-field>
                        <x-field label="{{ __('cpanel/pages.status') }}">
                            <select name="status" id="user_role" class="form-control">
                                <option value="0">@lang('cpanel/pages.status_private')</option>
                                <option value="1" selected>@lang('cpanel/pages.status_published')</option>
                            </select>
                        </x-field>
                        @if(!empty($page_templates) && $page_templates)
                            <x-field label="{{ __('cpanel/pages.page_template') }}">
                                <select name="template" class="form-control">
                                    @foreach($page_templates as $file_name => $template_header)
                                        <option value="{{$file_name}}" {{$file_name === 'page' ? 'selected' : null}}>{{$template_header}}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        @endif
                        <x-slot:footer>
                            <div class="flex justify-end">
                                <x-button type="submit" variant="primary">@lang('cpanel/pages.publish_button_label')</x-button>
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
