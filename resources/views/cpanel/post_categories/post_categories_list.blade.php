<?php
/**
 * AgenticCms-Laravel
 * File: post_categories_list.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 31.08.2019
 */
?>

@extends('cpanel.core.index')
@push('extrastyles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-serif text-2xl text-fg">@lang('cpanel/categories.list_headline')</h1>
            <x-button as="a" :href="route('cpanel_add_new_category')" variant="primary" size="sm">
                @lang('cpanel/categories.add_new_category')
            </x-button>
        </div>

        @include('cpanel.core.flash')
        @if (($update_message = Session::get('message')) !== null)
            <x-alert :variant="$update_message ? 'success' : 'error'" class="mb-4">{{ $update_message ? __('cpanel/categories.bulky_deleted_message') : __('cpanel/categories.bulky_deleted_error_message') }}</x-alert>
        @endif
        @if (Session::get('category_added'))
            <x-alert variant="success" class="mb-4">@lang('cpanel/categories.category_added')</x-alert>
        @endif

        <div class="overflow-hidden rounded-lg border border-border bg-surface">
            <form method="POST" action="{{route('cpanel_category_bulk_delete')}}"
                  x-data="{ selected: 0 }"
                  x-on:change="selected = $el.querySelectorAll('.categories-checkbox-input:checked').length">
                @csrf
                @method('DELETE')

                {{-- Bulk-action bar (DESIGN_SYSTEM §5). Retains the .select-cover hook. --}}
                <div class="select-cover flex flex-wrap items-center gap-3 border-b border-border bg-surface-2 px-5 py-3" aria-live="polite">
                    <span class="font-mono text-xs uppercase tracking-[0.08em] text-muted" x-text="selected > 0 ? selected + ' selected' : '@lang('cpanel/categories.bulk_action_label')'">@lang('cpanel/categories.bulk_action_label')</span>
                    <select id="inputState" name="categories_action" class="h-9 rounded-sm border border-strong bg-surface px-3 text-sm text-fg focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-1">
                        <option selected="selected">@lang('cpanel/categories.bulk_action_label')</option>
                        <option value="delete">@lang('cpanel/categories.bulk_action_delete_label')</option>
                    </select>
                    <x-button type="submit" variant="secondary" size="sm">@lang('cpanel/categories.bulk_action_apply')</x-button>
                </div>

                <div class="overflow-x-auto">
                    <table class="data-table users-table w-full text-left text-sm">
                        <thead class="bg-surface-2">
                            <tr>
                                <th class="w-10 px-4 py-3"><input class="form-check-input" id="selectAll" name="allcategories" type="checkbox" aria-label="Select all"></th>
                                <th class="w-12 px-4 py-3"><x-eyebrow>№</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/categories.table_name')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/categories.table_action')</x-eyebrow></th>
                            </tr>
                        </thead>
                        <tbody>
                        @php($category_count = 0)
                        @forelse($categories_list as $category)
                            @php($category_count++)
                            <tr class="border-b border-border transition-colors last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 align-middle">
                                    @if($category->id !== 1)
                                        <input class="form-check-input categories-checkbox-input" id="category_{{$category->id}}" name="categories[]" type="checkbox" value="{{$category->id}}" aria-label="Select category">
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-middle text-subtle">{{$category_count}}</td>
                                <td class="px-4 py-3 align-middle font-medium text-fg">{{$category->title}}</td>
                                <td class="px-4 py-3 align-middle">
                                    <span class="user_actions">
                                        <a href="{{route('cpanel_edit_category', ['id' => $category->id, 'lang' => get_current_lang()])}}" target="_blank">@lang('cpanel/categories.edit_category')</a>
                                        @if($category->id !== 1)
                                            <input type="hidden" class="deleted_category_id" value="{{$category->id}}" name="deleted_category_id">
                                            <button type="button" class="delete_category">@lang('cpanel/categories.delete_category')</button>
                                        @endif
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty-state :headline="__('cpanel/categories.not_found')" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
            <div class="border-t border-border px-5 py-4">
                <x-pagination :paginator="$categories_list" />
            </div>
        </div>
    </div>
@endsection

@push('finalscripts')
    <script>
        var delete_confirmation = '@lang('cpanel/categories.js_delete_confirmation')',
            delete_success = '@lang('cpanel/categories.js_delete_success')',
            error_message = '@lang('cpanel/categories.js_error')';
    </script>
    <script src="{{asset('admin')}}/js/category.js"></script>
@endpush
