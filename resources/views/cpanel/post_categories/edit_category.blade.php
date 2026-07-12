<?php
/**
 * AgenticCms-Laravel
 * File: edit_category.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 31.08.2019
 */
?>

@extends('cpanel.core.index')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/categories.edit_category_headline')</h1>
        </div>

        @include('cpanel.core.flash')
        @if (($update_message = Session::get('message')) !== null)
            <div class="alert {{ $update_message ? 'alert-success' : 'alert-danger' }}"><strong>{{ $update_message ? __('cpanel/categories.updated_success') : __('cpanel/categories.updated_error') }}</strong></div>
        @endif

        <form action="{{ route('cpanel_update_category', ['id' => $entity->id]) }}" method="POST">
            @csrf
            @method('PUT')
            <x-card>
                @include('cpanel.core.translation')

                <div class="grid grid-cols-1 gap-x-5 md:grid-cols-2">
                    <x-field label="{{ __('cpanel/categories.title') }}" name="cpanel_title" help="{{ __('cpanel/categories.title_desc') }}">
                        <input type="text" id="cpanel_title" required class="form-control w-full" name="title" value="{{ old('title', $entity->title) }}">
                    </x-field>
                    <x-field label="{{ __('cpanel/categories.slug') }}" name="cpanel_slug" help="{{ __('cpanel/categories.slug_desc') }}">
                        <input type="text" id="cpanel_slug" required class="form-control w-full" name="slug" value="{{ old('slug', $entity->slug) }}">
                    </x-field>
                </div>

                <x-field label="{{ __('cpanel/categories.parent_category') }}" help="{{ __('cpanel/categories.parent_category_desc') }}">
                    <select name="parent_category_id" class="form-control">
                        <option value="">@lang('cpanel/categories.no_parent_category')</option>
                        @foreach($parent_options as $option)
                            <option value="{{ $option->category_id }}" {{ (int) old('parent_category_id', $entity->parent_category_id) === $option->category_id ? 'selected' : '' }}>{!! str_repeat('&nbsp;&nbsp;&nbsp;', $option->depth) !!}{{ $option->title }}</option>
                        @endforeach
                    </select>
                </x-field>

                <x-field label="{{ __('cpanel/categories.description') }}" help="{{ __('cpanel/categories.description_content') }}">
                    <textarea name="description" class="form-control w-full">{{ old('description', $entity->description) }}</textarea>
                </x-field>

                @include('cpanel.core.seo')
                <x-slot:footer>
                    <div class="flex justify-end">
                        <x-button type="submit" variant="primary">@lang('cpanel/categories.update_button_label')</x-button>
                    </div>
                </x-slot:footer>
            </x-card>
        </form>
    </div>
@endsection
