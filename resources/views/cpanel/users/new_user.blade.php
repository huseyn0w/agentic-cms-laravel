<?php
/**
 * AgenticCms-Laravel
 * File: new_user.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 11.08.2019
 */
?>

@extends('cpanel.core.index')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/users.new_user_headline')</h1>
        </div>

        @include('cpanel.core.flash')

        <form action="{{ route('cpanel_save_new_user') }}" method="POST">
            @csrf
            <x-card>
                <div class="grid grid-cols-1 gap-x-5 md:grid-cols-2">
                    <x-field label="{{ __('cpanel/users.username') }}" name="username">
                        <input type="text" id="username" class="form-control w-full" name="username" value="{{ old('username') }}">
                    </x-field>
                    <x-field label="{{ __('cpanel/users.email') }}" name="email">
                        <input type="email" id="email" class="form-control w-full" name="email" value="{{ old('email') }}">
                    </x-field>
                    <x-field label="{{ __('cpanel/users.password') }}" name="password">
                        <input type="password" id="password" class="form-control w-full" name="password" value="">
                    </x-field>
                    <x-field label="{{ __('cpanel/users.confirm_password') }}" name="confirm_password">
                        <input type="password" id="confirm_password" class="form-control w-full" name="password_confirmation" value="">
                    </x-field>
                    <x-field label="{{ __('cpanel/users.name') }}" name="name">
                        <input type="text" id="name" class="form-control w-full" name="name" value="{{ old('name') }}">
                    </x-field>
                    <x-field label="{{ __('cpanel/users.surname') }}" name="surname">
                        <input type="text" id="surname" class="form-control w-full" name="surname" value="{{ old('surname') }}">
                    </x-field>
                    <x-field label="{{ __('cpanel/users.country') }}">
                        <select name="country" id="country" class="form-control">
                            @foreach($countries as $country)
                                <option value="{{$country['name']}}">{{$country['name']}}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field label="{{ __('cpanel/users.city') }}">
                        <input type="text" name="city" class="form-control w-full" value="{{ old('city') }}">
                    </x-field>
                </div>

                <x-field label="{{ __('cpanel/users.status') }}">
                    <select name="role_id" id="user_role" class="form-control">
                        @foreach($user_roles as $role)
                            <option value="{{$role['id']}}">{{$role['name']}}</option>
                        @endforeach
                    </select>
                </x-field>

                <x-field label="{{ __('cpanel/users.about') }}">
                    <textarea rows="4" class="form-control w-full" name="about_me" placeholder="Here can be your description">{{ old('about_me') }}</textarea>
                </x-field>

                <div class="field">
                    <span class="field-label">@lang('cpanel/users.gender')</span>
                    <div class="flex flex-wrap gap-6">
                        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                            <input class="form-check-input" type="radio" name="gender" value="male" id="male"> @lang('cpanel/users.gender_male')
                        </label>
                        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                            <input class="form-check-input" type="radio" name="gender" value="female" id="female"> @lang('cpanel/users.gender_female')
                        </label>
                    </div>
                </div>

                <fieldset class="mt-2 rounded-lg border border-border p-4">
                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-muted">@lang('cpanel/users.social_profiles')</legend>
                    <div class="grid grid-cols-1 gap-x-5 md:grid-cols-2">
                        <x-field label="{{ __('cpanel/users.facebook') }}">
                            <input type="text" class="form-control w-full" name="facebook_url" placeholder="https://" value="{{ old('facebook_url') }}">
                        </x-field>
                        <x-field label="{{ __('cpanel/users.google') }}">
                            <input type="text" class="form-control w-full" name="google_url" placeholder="https://" value="{{ old('google_url') }}">
                        </x-field>
                        <x-field label="{{ __('cpanel/users.twitter') }}">
                            <input type="text" class="form-control w-full" name="twitter_url" placeholder="https://" value="{{ old('twitter_url') }}">
                        </x-field>
                        <x-field label="{{ __('cpanel/users.instagram') }}">
                            <input type="text" class="form-control w-full" name="instagram_url" placeholder="https://" value="{{ old('instagram_url') }}">
                        </x-field>
                        <x-field label="{{ __('cpanel/users.linkedin') }}">
                            <input type="text" class="form-control w-full" name="linkedin_url" placeholder="https://" value="{{ old('linkedin_url') }}">
                        </x-field>
                        <x-field label="{{ __('cpanel/users.xing') }}">
                            <input type="text" class="form-control w-full" name="xing_url" placeholder="https://" value="{{ old('xing_url') }}">
                        </x-field>
                    </div>
                </fieldset>
                <x-slot:footer>
                    <div class="flex justify-end">
                        <x-button type="submit" variant="primary">@lang('cpanel/users.add_new_user')</x-button>
                    </div>
                </x-slot:footer>
            </x-card>
        </form>
    </div>
@endsection

@push('extrascripts')
    <script src="{{asset('admin')}}/js/userprofile.js"></script>
@endpush

@push('finalscripts')
    <script src="{{asset('admin')}}/js/user.js"></script>
@endpush
