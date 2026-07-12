<?php
/**
 * AgenticCms-Laravel
 * File: new_role.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 11.08.2019
 */
?>

@extends('cpanel.core.index')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/roles.new_role_headline')</h1>
        </div>

        @include('cpanel.core.flash')

        <form action="{{ route('cpanel_save_user_role') }}" method="POST">
            @csrf
            <x-card>
                <x-field label="{{ __('cpanel/roles.role_name') }}" name="name">
                    <input type="text" id="name" required class="form-control w-full" name="name" value="{{ old('name') }}">
                </x-field>

                <fieldset class="mt-4 rounded-lg border border-border p-4">
                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-muted">@lang('cpanel/roles.table_action')</legend>
                    <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($role_permissions as $permission)
                            @php($permission_name = str_replace('_', " ", $permission->name))
                            <label for="{{$permission->name}}" class="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                                <input class="form-check-input" id="{{$permission->name}}" name="permissions[]" value="{{$permission->name}}" type="checkbox">
                                <span class="capitalize">{{$permission_name}}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <x-slot:footer>
                    <div class="flex justify-end">
                        <x-button type="submit" variant="primary">@lang('cpanel/roles.add_new_role')</x-button>
                    </div>
                </x-slot:footer>
            </x-card>
        </form>
    </div>
@endsection

@push('finalscripts')
    <script src="{{asset('admin')}}/js/role.js"></script>
@endpush
