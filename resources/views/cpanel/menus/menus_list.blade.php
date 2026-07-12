<?php
/**
 * AgenticCms-Laravel
 * File: menus_list.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 04.09.2019
 */
?>

@extends('cpanel.core.index')
@push('extrastyles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-serif text-2xl text-fg">@lang('cpanel/menus.list_headline')</h1>
            <x-button as="a" :href="route('cpanel_add_new_menu')" variant="primary" size="sm">
                @lang('cpanel/menus.add_new_menu')
            </x-button>
        </div>

        @include('cpanel.core.flash')
        @if (Session::get('menu_added'))
            <x-alert variant="success" class="mb-4">@lang('cpanel/menus.menu_added')</x-alert>
        @endif

        <div class="overflow-hidden rounded-lg border border-border bg-surface">
            <div class="overflow-x-auto">
                <table class="data-table users-table w-full text-left text-sm">
                    <thead class="bg-surface-2">
                        <tr>
                            <th class="w-12 px-4 py-3"><x-eyebrow>№</x-eyebrow></th>
                            <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/menus.table_name')</x-eyebrow></th>
                            <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/menus.table_action')</x-eyebrow></th>
                        </tr>
                    </thead>
                    <tbody>
                    @php($menus_count = 0)
                    @forelse($menus_list as $menu)
                        @php($menus_count++)
                        <tr class="border-b border-border transition-colors last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3 align-middle text-subtle">{{$menus_count}}</td>
                            <td class="px-4 py-3 align-middle font-medium text-fg">{{$menu->title}}</td>
                            <td class="px-4 py-3 align-middle">
                                <span class="user_actions">
                                    @if (Auth::user()->can('manage_menus', 'App\Http\Models\UserRoles'))
                                        <a href="{{route('cpanel_edit_menu', ['id' => $menu->id, 'lang' => get_current_lang()])}}" target="_blank">@lang('cpanel/menus.edit')</a>
                                        <input type="hidden" class="deleted_menu_id" value="{{$menu->id}}" name="deleted_menu_id">
                                        @if($menu->id > 1)
                                            <button type="button" class="delete_menu">@lang('cpanel/menus.delete')</button>
                                        @endif
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-empty-state :headline="__('cpanel/menus.not_found')" /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-border px-5 py-4">
                <x-pagination :paginator="$menus_list" />
            </div>
        </div>
    </div>
@endsection

@push('finalscripts')
    <script>
        var delete_confirmation = '@lang('cpanel/menus.js_delete_confirmation')',
            delete_success = '@lang('cpanel/menus.js_delete')',
            error_message = '@lang('cpanel/menus.js_error')';
    </script>
    <script src="{{asset('admin')}}/js/menu.js"></script>
@endpush
