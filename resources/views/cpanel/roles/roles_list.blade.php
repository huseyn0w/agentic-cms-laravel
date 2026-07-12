<?php
/**
 * AgenticCms-Laravel
 * File: roles_list.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 09.08.2019
 */
?>

@extends('cpanel.core.index')
@push('extrastyles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-serif text-2xl text-fg">@lang('cpanel/roles.list_headline')</h1>
            <x-button as="a" :href="route('cpanel_add_user_role')" variant="primary" size="sm">
                @lang('cpanel/roles.add_new_role')
            </x-button>
        </div>

        @include('cpanel.core.flash')
        @if (Session::get('role_added'))
            <x-alert variant="success" class="mb-4">@lang('cpanel/roles.role_added')</x-alert>
        @endif

        <div class="overflow-hidden rounded-lg border border-border bg-surface">
            <div class="overflow-x-auto">
                <table class="data-table users-table w-full text-left text-sm">
                    <thead class="bg-surface-2">
                        <tr>
                            <th class="w-12 px-4 py-3"><x-eyebrow>№</x-eyebrow></th>
                            <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/roles.table_name')</x-eyebrow></th>
                            <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/roles.table_action')</x-eyebrow></th>
                        </tr>
                    </thead>
                    <tbody>
                    @php($roles_count = 0)
                    @forelse($roles_list as $role)
                        @php($roles_count++)
                        <tr class="border-b border-border transition-colors last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3 align-middle text-subtle">{{$roles_count}}</td>
                            <td class="px-4 py-3 align-middle font-medium text-fg">{{$role->name}}</td>
                            <td class="px-4 py-3 align-middle">
                                <span class="user_actions">
                                    @if (Auth::user()->can('manage_user_roles', 'App\Http\Models\UserRoles'))
                                        <a href="{{route('cpanel_edit_user_role', $role->id)}}" target="_blank">@lang('cpanel/roles.edit')</a>
                                        @if($role->id !== 1 && $role->id !== 2)
                                            <input type="hidden" class="deleted_role_id" value="{{$role->id}}" name="deleted_role_id">
                                            <button type="button" class="delete_role">@lang('cpanel/roles.delete')</button>
                                        @endif
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-empty-state :headline="__('cpanel/roles.not_found')" /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-border px-5 py-4">
                <x-pagination :paginator="$roles_list" />
            </div>
        </div>
    </div>
@endsection

@push('finalscripts')
    <script>
        var delete_confirmation = '@lang('cpanel/roles.js_delete_confirmation')',
            delete_success = '@lang('cpanel/roles.js_delete')',
            error_message = '@lang('cpanel/roles.js_error')';
    </script>
    <script src="{{asset('admin')}}/js/role.js"></script>
@endpush
