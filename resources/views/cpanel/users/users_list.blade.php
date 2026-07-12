<?php
/**
 * AgenticCms-Laravel
 * File: users_list.blade.php
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
            <h1 class="font-serif text-2xl text-fg">@lang('cpanel/users.list_headline')</h1>
            <x-button as="a" :href="route('cpanel_add_new_user')" variant="primary" size="sm">
                @lang('cpanel/users.add_new_user')
            </x-button>
        </div>

        @include('cpanel.core.flash')
        @if ($update_message = Session::get('message'))
            <x-alert :variant="$update_message ? 'success' : 'error'" class="mb-4">{{ $update_message ? __('cpanel/users.bulky_deleted_message') : __('cpanel/users.bulky_deleted_error_message') }}</x-alert>
        @endif
        @if (Session::get('user_added'))
            <x-alert variant="success" class="mb-4">@lang('cpanel/users.user_added')</x-alert>
        @endif

        <div class="overflow-hidden rounded-lg border border-border bg-surface">
            <form method="POST" action="{{route('cpanel_users_bulk_delete')}}"
                  x-data="{ selected: 0 }"
                  x-on:change="selected = $el.querySelectorAll('.users-checkbox-input:checked').length">
                @csrf
                @method('DELETE')

                {{-- Bulk-action bar (DESIGN_SYSTEM §5). Retains the .select-cover hook. --}}
                <div class="select-cover flex flex-wrap items-center gap-3 border-b border-border bg-surface-2 px-5 py-3" aria-live="polite">
                    <span class="font-mono text-xs uppercase tracking-[0.08em] text-muted" x-text="selected > 0 ? selected + ' selected' : '@lang('cpanel/users.bulk_action_label')'">@lang('cpanel/users.bulk_action_label')</span>
                    <select id="inputState" name="users_action" required class="h-9 rounded-sm border border-strong bg-surface px-3 text-sm text-fg focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-1">
                        <option selected="selected">@lang('cpanel/users.bulk_action_label')</option>
                        <option value="delete">@lang('cpanel/users.bulk_action_delete_label')</option>
                    </select>
                    <x-button type="submit" variant="secondary" size="sm">@lang('cpanel/users.bulk_action_apply')</x-button>
                </div>

                <div class="overflow-x-auto">
                    <table class="data-table users-table w-full text-left text-sm">
                        <thead class="bg-surface-2">
                            <tr>
                                <th class="w-10 px-4 py-3"><input class="form-check-input" id="selectAll" name="allusers" type="checkbox" aria-label="Select all"></th>
                                <th class="w-12 px-4 py-3"><x-eyebrow>№</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/users.table_username')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/users.table_email')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/users.table_name')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/users.table_surname')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/users.table_country')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/users.table_city')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/users.table_role')</x-eyebrow></th>
                            </tr>
                        </thead>
                        <tbody>
                        @php($users_count = 0)
                        @forelse($users_list as $user)
                            @php($users_count++)
                            <tr class="border-b border-border transition-colors last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 align-middle"><input class="form-check-input users-checkbox-input" id="user_{{$user->id}}" name="users[]" type="checkbox" value="{{$user->id}}" aria-label="Select user"></td>
                                <td class="px-4 py-3 align-middle text-subtle">{{$users_count}}</td>
                                <td class="px-4 py-3 align-middle">
                                    <span class="font-medium text-fg">{{$user->username}}</span>
                                    <span class="user_actions">
                                        @if (Auth::user()->can('manage_users', 'App\Http\Models\UserRoles'))
                                            <a href="{{route('cpanel_edit_user_profile', $user->id)}}" target="_blank">@lang('cpanel/users.edit_user')</a>
                                            <input type="hidden" class="deleted_user_id" value="{{$user->id}}" name="deleted_user_id">
                                            <button type="button" class="delete_user">@lang('cpanel/users.delete_user')</button>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle text-muted">{{$user->email}}</td>
                                <td class="px-4 py-3 align-middle text-muted">{{$user->name}}</td>
                                <td class="px-4 py-3 align-middle text-muted">{{$user->surname}}</td>
                                <td class="px-4 py-3 align-middle text-muted">{{$user->country}}</td>
                                <td class="px-4 py-3 align-middle text-muted">{{$user->city}}</td>
                                <td class="px-4 py-3 align-middle"><x-badge variant="neutral">{{$user->role->name}}</x-badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="9"><x-empty-state :headline="__('cpanel/users.not_found')" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
            <div class="border-t border-border px-5 py-4">
                <x-pagination :paginator="$users_list" />
            </div>
        </div>
    </div>
@endsection

@push('finalscripts')
    <script>
        var delete_confirmation = '@lang('cpanel/users.js_delete_confirmation')',
            delete_success = '@lang('cpanel/users.js_delete_success')',
            delete_error = '@lang('cpanel/users.js_delete_error')';
    </script>
    <script src="{{asset('admin')}}/js/user.js"></script>
@endpush
