<?php
/**
 * Cmstack-Laravel
 * File: edit_role.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 11.08.2019
 */
?>

@extends('cpanel.core.index')

@section('content')
    @php
        $user_permissions = json_decode($role->permissions, true);
    @endphp

    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/roles.edit_role_headline')</h1>
        </div>

        @include('cpanel.core.flash')
        @if (($update_message = Session::get('message')) !== null)
            <div class="alert {{ $update_message ? 'alert-success' : 'alert-danger' }}"><strong>{{ $update_message ? __('cpanel/roles.role_updated') : __('cpanel/roles.role_error') }}</strong></div>
        @endif

        <form action="{{ route('cpanel_update_user_role',['id' => $role->id]) }}" method="POST">
            @csrf
            @method('PUT')
            <x-card>
                <x-field label="{{ __('cpanel/roles.role_name') }}" name="name">
                    <input type="text" id="name" required class="form-control w-full" name="name" value="{{ old('name', $role->name) }}">
                </x-field>

                <fieldset class="mt-4 rounded-lg border border-border p-4">
                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-muted">@lang('cpanel/roles.table_action')</legend>
                    <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($role_permissions as $permission)
                            @php($permission_name = str_replace('_', " ", $permission->name))
                            <label for="{{$permission->name}}" class="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                                <input class="form-check-input" id="{{$permission->name}}" name="permissions[]" value="{{$permission->name}}" type="checkbox" {{ ($user_permissions[$permission->name] ?? 0) === 1 ? 'checked' : '' }}>
                                <span class="capitalize">{{$permission_name}}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <x-slot:footer>
                    <div class="flex justify-end">
                        <x-button type="submit" variant="primary">@lang('cpanel/roles.update_role')</x-button>
                    </div>
                </x-slot:footer>
            </x-card>
        </form>
    </div>
@endsection

@push('finalscripts')
    <script src="{{asset('admin')}}/js/role.js"></script>
@endpush
