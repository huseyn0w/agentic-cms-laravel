<?php
/**
 * AgenticCms-Laravel
 * File: pages_list.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 16.08.2019
 */
?>

@extends('cpanel.core.index')
@push('extrastyles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@php
$route_name = Route::current()->getName();
$is_trash = $route_name == "cpanel_trashed_pages_list";
@endphp

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-serif text-2xl text-fg">@lang('cpanel/pages.list_headline')</h1>
            <x-button as="a" :href="route('cpanel_add_new_page')" variant="primary" size="sm">
                @lang('cpanel/pages.add_new_page')
            </x-button>
        </div>

        @include('cpanel.core.flash')
        @if (Session::get('page_added'))
            <x-alert variant="success" class="mb-4">@lang('cpanel/pages.page_added')</x-alert>
        @endif
        @if (($update_message = Session::get('deleted')) !== null)
            <x-alert :variant="$update_message ? 'success' : 'error'" class="mb-4">{{ $update_message ? __('cpanel/pages.bulky_deleted_message') : __('cpanel/pages.bulky_error_message') }}</x-alert>
        @endif
        @if (($update_message = Session::get('restored')) !== null)
            <x-alert :variant="$update_message ? 'success' : 'error'" class="mb-4">{{ $update_message ? __('cpanel/pages.bulky_restored_message') : __('cpanel/pages.bulky_error_message') }}</x-alert>
        @endif
        @if (($update_message = Session::get('destroyed')) !== null)
            <x-alert :variant="$update_message ? 'success' : 'error'" class="mb-4">{{ $update_message ? __('cpanel/pages.bulky_destroyed_message') : __('cpanel/pages.bulky_error_message') }}</x-alert>
        @endif

        {{-- Status filter tabs: published vs trashed (DESIGN_SYSTEM §5 / Tabs) --}}
        <nav aria-label="{{ __('cpanel/pages.general_pages') }}" class="mb-4 flex gap-1 border-b border-border">
            <a href="{{route('cpanel_pages_list')}}" @if(!$is_trash) aria-current="page" @endif class="-mb-px border-b-2 px-4 py-2.5 text-sm font-sans transition-colors duration-[var(--dur-fast)] {{ !$is_trash ? 'border-primary font-medium text-fg' : 'border-transparent text-muted hover:text-fg' }}">@lang('cpanel/pages.general_pages')</a>
            <a href="{{route('cpanel_trashed_pages_list')}}" @if($is_trash) aria-current="page" @endif class="-mb-px border-b-2 px-4 py-2.5 text-sm font-sans transition-colors duration-[var(--dur-fast)] {{ $is_trash ? 'border-primary font-medium text-fg' : 'border-transparent text-muted hover:text-fg' }}">@lang('cpanel/pages.trashed_pages')</a>
        </nav>

        <div class="overflow-hidden rounded-lg border border-border bg-surface">
            <form method="POST" action="{{ $is_trash ? route('cpanel_pages_bulk_action') : route('cpanel_pages_bulk_delete') }}"
                  x-data="{ selected: 0 }"
                  x-on:change="selected = $el.querySelectorAll('.pages-checkbox-input:checked').length">
                @csrf
                @if($is_trash) @method('POST') @else @method('DELETE') @endif

                {{-- Bulk-action bar (DESIGN_SYSTEM §5). Retains the .select-cover hook. --}}
                <div class="select-cover flex flex-wrap items-center gap-3 border-b border-border bg-surface-2 px-5 py-3" aria-live="polite">
                    <span class="font-mono text-xs uppercase tracking-[0.08em] text-muted" x-text="selected > 0 ? selected + ' selected' : '@lang('cpanel/pages.bulk_action_label')'">@lang('cpanel/pages.bulk_action_label')</span>
                    <select id="inputState" name="pages_action" required class="h-9 rounded-sm border border-strong bg-surface px-3 text-sm text-fg focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-1">
                        <option selected="selected">@lang('cpanel/pages.bulk_action_label')</option>
                        @if($is_trash)
                            <option value="destroy">@lang('cpanel/pages.bulk_action_destroy_label')</option>
                            <option value="restore">@lang('cpanel/pages.bulk_action_restore_label')</option>
                        @else
                            <option value="delete">@lang('cpanel/pages.bulk_action_delete_label')</option>
                        @endif
                    </select>
                    <x-button type="submit" variant="secondary" size="sm">@lang('cpanel/pages.bulk_action_apply')</x-button>
                </div>

                <div class="overflow-x-auto">
                    <table class="data-table users-table w-full text-left text-sm">
                        <thead class="bg-surface-2">
                            <tr>
                                <th class="w-10 px-4 py-3"><input class="form-check-input" id="selectAll" name="allusers" type="checkbox" aria-label="Select all"></th>
                                <th class="w-12 px-4 py-3"><x-eyebrow>№</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/pages.table_name')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/pages.table_author')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/pages.table_publish_date')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/pages.table_status')</x-eyebrow></th>
                            </tr>
                        </thead>
                        <tbody>
                        @php($pages_count = 0)
                        @forelse($pages_list as $page)
                            @php($pages_count++)
                            <tr class="border-b border-border transition-colors last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 align-middle"><input class="form-check-input pages-checkbox-input" id="page_{{$page->id}}" name="pages[]" type="checkbox" value="{{$page->id}}" aria-label="Select page"></td>
                                <td class="px-4 py-3 align-middle text-subtle">{{$pages_count}}</td>
                                <td class="px-4 py-3 align-middle">
                                    <span class="font-medium text-fg">{{$page->title}}</span>
                                    <span class="user_actions">
                                        @if (Auth::user()->can('manage_pages', 'App\Http\Models\UserRoles'))
                                            <a href="{{route('cpanel_edit_page', ['id' => $page->id, 'lang' => get_current_lang()])}}" target="_blank">@lang('cpanel/pages.edit_page')</a>
                                            <input type="hidden" class="deleted_page_id" value="{{$page->id}}" name="deleted_page_id">
                                            @if(!$is_trash)
                                                <button type="button" class="delete_page">@lang('cpanel/pages.delete_page')</button>
                                            @else
                                                <button type="button" class="destroy_page">@lang('cpanel/pages.destroy_page')</button>
                                                <a href="{{route('cpanel_restore_page', $page->id)}}" class="restore_page">@lang('cpanel/pages.restore_page')</a>
                                            @endif
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle text-muted">{{$page->author->username}}</td>
                                <td class="whitespace-nowrap px-4 py-3 align-middle text-muted">{{ Carbon\Carbon::parse($page->created_at)->format('d.m.Y')}}</td>
                                <td class="px-4 py-3 align-middle">
                                    @if($page->status == 1)
                                        <x-badge variant="success">@lang('cpanel/pages.page_published')</x-badge>
                                    @else
                                        <x-badge variant="neutral">@lang('cpanel/pages.page_pending')</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty-state :headline="__('cpanel/pages.not_found')" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
            <div class="border-t border-border px-5 py-4">
                <x-pagination :paginator="$pages_list" />
            </div>
        </div>
    </div>
@endsection

@push('finalscripts')
    <script>
        var delete_confirmation = '@lang('cpanel/pages.js_delete_confirmation')',
            delete_success = '@lang('cpanel/pages.js_delete_success')',
            destroy_confirmation = '@lang('cpanel/pages.js_destroy_confirmation')',
            destroy_success = '@lang('cpanel/pages.js_destroy_success')',
            error_message = '@lang('cpanel/pages.js_delete_error')';
    </script>
    <script src="{{asset('admin')}}/js/page.js"></script>
@endpush
