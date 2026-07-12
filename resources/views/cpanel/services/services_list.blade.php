<?php
/**
 * AgenticCms-Laravel
 * File: services_list.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 */
?>

@extends('cpanel.core.index')
@push('extrastyles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@php
$route_name = Route::current()->getName();
$is_trash = $route_name == "cpanel_trashed_services_list";
@endphp

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-serif text-2xl text-fg">@lang('cpanel/services.list_headline')</h1>
            <x-button as="a" :href="route('cpanel_add_new_service')" variant="primary" size="sm">
                @lang('cpanel/services.add_new_service')
            </x-button>
        </div>

        @include('cpanel.core.flash')
        @if (Session::get('service_added'))
            <x-alert variant="success" class="mb-4">@lang('cpanel/services.service_added')</x-alert>
        @endif
        @if (($update_message = Session::get('deleted')) !== null)
            <x-alert :variant="$update_message ? 'success' : 'error'" class="mb-4">{{ $update_message ? __('cpanel/services.bulky_deleted_message') : __('cpanel/services.bulky_error_message') }}</x-alert>
        @endif
        @if (($update_message = Session::get('restored')) !== null)
            <x-alert :variant="$update_message ? 'success' : 'error'" class="mb-4">{{ $update_message ? __('cpanel/services.bulky_restored_message') : __('cpanel/services.bulky_error_message') }}</x-alert>
        @endif
        @if (($update_message = Session::get('destroyed')) !== null)
            <x-alert :variant="$update_message ? 'success' : 'error'" class="mb-4">{{ $update_message ? __('cpanel/services.bulky_destroyed_message') : __('cpanel/services.bulky_error_message') }}</x-alert>
        @endif

        {{-- Status filter tabs: all vs trashed (DESIGN_SYSTEM §5 / Tabs) --}}
        <nav aria-label="{{ __('cpanel/services.general_services') }}" class="mb-4 flex gap-1 border-b border-border">
            <a href="{{route('cpanel_services_list')}}" @if(!$is_trash) aria-current="page" @endif class="-mb-px border-b-2 px-4 py-2.5 text-sm font-sans transition-colors duration-[var(--dur-fast)] {{ !$is_trash ? 'border-primary font-medium text-fg' : 'border-transparent text-muted hover:text-fg' }}">@lang('cpanel/services.general_services')</a>
            <a href="{{route('cpanel_trashed_services_list')}}" @if($is_trash) aria-current="page" @endif class="-mb-px border-b-2 px-4 py-2.5 text-sm font-sans transition-colors duration-[var(--dur-fast)] {{ $is_trash ? 'border-primary font-medium text-fg' : 'border-transparent text-muted hover:text-fg' }}">@lang('cpanel/services.trashed_services')</a>
        </nav>

        <div class="overflow-hidden rounded-lg border border-border bg-surface">
            <form method="POST" action="{{ route('cpanel_services_bulk_action') }}"
                  x-data="{ selected: 0 }"
                  x-on:change="selected = $el.querySelectorAll('.services-checkbox-input:checked').length">
                @csrf

                {{-- Bulk-action bar (DESIGN_SYSTEM §5). --}}
                <div class="select-cover flex flex-wrap items-center gap-3 border-b border-border bg-surface-2 px-5 py-3" aria-live="polite">
                    <span class="font-mono text-xs uppercase tracking-[0.08em] text-muted" x-text="selected > 0 ? selected + ' selected' : '@lang('cpanel/services.bulk_action_label')'">@lang('cpanel/services.bulk_action_label')</span>
                    <select id="inputState" name="services_action" required class="h-9 rounded-sm border border-strong bg-surface px-3 text-sm text-fg focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-1">
                        <option selected="selected">@lang('cpanel/services.bulk_action_label')</option>
                        @if($is_trash)
                            <option value="destroy">@lang('cpanel/services.bulk_action_destroy_label')</option>
                            <option value="restore">@lang('cpanel/services.bulk_action_restore_label')</option>
                        @else
                            <option value="delete">@lang('cpanel/services.bulk_action_delete_label')</option>
                        @endif
                    </select>
                    <x-button type="submit" variant="secondary" size="sm">@lang('cpanel/services.bulk_action_apply')</x-button>
                </div>

                <div class="overflow-x-auto">
                    <table class="data-table users-table w-full text-left text-sm">
                        <thead class="bg-surface-2">
                            <tr>
                                <th class="w-10 px-4 py-3"><input class="form-check-input" id="selectAll" name="allservices" type="checkbox" aria-label="Select all"></th>
                                <th class="w-12 px-4 py-3"><x-eyebrow>@lang('cpanel/services.table_order')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/services.table_title')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/services.table_status')</x-eyebrow></th>
                            </tr>
                        </thead>
                        <tbody>
                        @php($lang = get_current_lang())
                        @forelse($services_list as $service)
                            <tr class="border-b border-border transition-colors last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 align-middle"><input class="form-check-input services-checkbox-input" id="service_{{$service->id}}" name="services[]" type="checkbox" value="{{$service->id}}" aria-label="Select service"></td>
                                <td class="px-4 py-3 align-middle text-subtle">{{$service->sort_order}}</td>
                                <td class="px-4 py-3 align-middle">
                                    <span class="font-medium text-fg">{{$service->translate($lang)?->title}}</span>
                                    <span class="user_actions">
                                        @if (Auth::user()->can('manage_services', 'App\Http\Models\UserRoles'))
                                            <a href="{{route('cpanel_edit_service', ['id' => $service->id, 'lang' => get_current_lang()])}}" target="_blank">@lang('cpanel/services.edit')</a>
                                            <input type="hidden" class="deleted_service_id" value="{{$service->id}}" name="deleted_service_id">
                                            @if(!$is_trash)
                                                <button type="button" class="delete_service">@lang('cpanel/services.delete')</button>
                                            @else
                                                <button type="button" class="destroy_service">@lang('cpanel/services.destroy')</button>
                                                <a href="{{route('cpanel_restore_service', $service->id)}}" class="restore_service">@lang('cpanel/services.restore')</a>
                                            @endif
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    @if($service->status == 1)
                                        <x-badge variant="success">@lang('cpanel/services.status_published')</x-badge>
                                    @else
                                        <x-badge variant="neutral">@lang('cpanel/services.status_private')</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty-state :headline="__('cpanel/services.not_found')" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
            <div class="border-t border-border px-5 py-4">
                <x-pagination :paginator="$services_list" />
            </div>
        </div>
    </div>
@endsection

@push('finalscripts')
    <script>
        var delete_confirmation = '@lang('cpanel/services.js_delete_confirmation')',
            delete_success = '@lang('cpanel/services.js_delete_success')',
            destroy_confirmation = '@lang('cpanel/services.js_destroy_confirmation')',
            destroy_success = '@lang('cpanel/services.js_destroy_success')',
            restore_confirmation = '@lang('cpanel/services.js_restore_confirmation')',
            restore_success = '@lang('cpanel/services.js_restore_success')',
            error_message = '@lang('cpanel/services.js_error_message')';
    </script>
    <script src="{{asset('admin')}}/js/service.js"></script>
@endpush
