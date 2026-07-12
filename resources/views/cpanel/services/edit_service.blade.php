<?php
/**
 * AgenticCms-Laravel
 * File: edit_service.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 */
?>

@extends('cpanel.core.index')

@section('content')
    @php
        $lang = request()->route('lang');
        $translation = $entity->translate($lang);
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-fg">@lang('cpanel/services.edit_service_headline')</h1>
            </div>
        </div>

        @include('cpanel.core.flash')
        @if (($update_message = Session::get('message')) !== null)
            <div class="alert {{ $update_message ? 'alert-success' : 'alert-danger' }}"><strong>{{ $update_message ? __('cpanel/services.updated_success') : __('cpanel/services.updated_error') }}</strong></div>
        @endif

        <form action="{{ route('cpanel_update_service', ['id' => $entity->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <x-card>
                        <div class="grid grid-cols-1 gap-x-5 md:grid-cols-2">
                            <x-field label="{{ __('cpanel/services.field_title') }}" name="cpanel_title">
                                <input type="text" id="cpanel_title" required class="form-control w-full" name="title" value="{{ old('title', $translation?->title) }}">
                            </x-field>
                            <x-field label="{{ __('cpanel/services.field_slug') }}" name="cpanel_slug">
                                <input type="text" id="cpanel_slug" required class="form-control w-full" name="slug" value="{{ old('slug', $translation?->slug) }}">
                            </x-field>
                        </div>
                        <x-field label="{{ __('cpanel/services.field_icon') }}" name="cpanel_icon">
                            <input type="text" id="cpanel_icon" class="form-control w-full" name="icon" value="{{ old('icon', $translation?->icon) }}">
                        </x-field>
                        <x-field label="{{ __('cpanel/services.field_excerpt') }}" name="cpanel_excerpt">
                            <input type="text" id="cpanel_excerpt" maxlength="255" class="form-control w-full" name="excerpt" value="{{ old('excerpt', $translation?->excerpt) }}">
                        </x-field>
                        <x-field label="{{ __('cpanel/services.field_content') }}">
                            <textarea name="content" id="editor" class="my-editor form-control w-full">{{ old('content', $translation?->content) }}</textarea>
                        </x-field>
                        <x-field label="{{ __('cpanel/services.field_thumbnail') }}" name="cpanel_thumbnail">
                            <input type="text" id="cpanel_thumbnail" class="form-control w-full" name="thumbnail" value="{{ old('thumbnail', $translation?->thumbnail) }}" placeholder="https://...">
                        </x-field>
                        @include('cpanel.core.seo')
                    </x-card>
                </div>

                <div class="lg:col-span-1">
                    <x-card>
                        @include('cpanel.core.translation')
                        <x-field label="{{ __('cpanel/services.field_sort_order') }}" name="cpanel_sort_order">
                            <input type="number" id="cpanel_sort_order" class="form-control w-full" name="sort_order" value="{{ old('sort_order', $entity->sort_order) }}" min="0">
                        </x-field>
                        <x-field label="{{ __('cpanel/services.field_status') }}">
                            <select name="status" id="service_status" class="form-control">
                                <option value="0" {{ $entity->status === 0 ? 'selected' : null }}>@lang('cpanel/services.status_private')</option>
                                <option value="1" {{ $entity->status === 1 ? 'selected' : null }}>@lang('cpanel/services.status_published')</option>
                            </select>
                        </x-field>
                        <x-slot:footer>
                            <div class="flex justify-end">
                                <x-button type="submit" variant="primary">@lang('cpanel/services.update_button_label')</x-button>
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
    <script src="{{ asset('') }}/vendor/laravel-filemanager/js/lfm.js"></script>
@endpush

@push('finalscripts')
    <script src="{{ asset('admin') }}/js/service.js"></script>
@endpush
