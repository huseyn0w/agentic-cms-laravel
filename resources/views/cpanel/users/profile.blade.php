<?php
/**
 * AgenticCms-Laravel
 * File: yourprofile.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 21.07.2019
 */
?>

@extends('cpanel.core.index')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/users.profile_headline')</h1>
        </div>

        @include('cpanel.core.flash')
        @if (($update_message = Session::get('message')) !== null)
            <div class="alert {{ $update_message ? 'alert-success' : 'alert-danger' }}"><strong>{{ $update_message ? __('cpanel/users.updated_success') : __('cpanel/users.updated_error') }}</strong></div>
        @endif

        <form action="{{ route('cpanel_update_user_profile', ['id' => $user->id]) }}" method="POST" enctype="multipart/form-data">
            @method('PUT')
            @csrf
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {{-- Main form --}}
                <div class="lg:col-span-2">
                    <x-card>
                        <div class="grid grid-cols-1 gap-x-5 md:grid-cols-2">
                            <div class="field">
                                <label class="field-label">@lang('cpanel/users.username')</label>
                                <p class="rounded-lg bg-surface-2 px-3.5 py-2.5 text-sm font-medium text-fg">{{$user->username}}</p>
                            </div>
                            <x-field label="{{ __('cpanel/users.email') }}" name="email">
                                <input type="email" id="email" class="form-control w-full" name="email" value="{{ old('email', $user->email) }}">
                            </x-field>
                            <x-field label="{{ __('cpanel/users.new_password') }}" name="password">
                                <input type="password" id="password" class="form-control w-full" name="password" value="">
                            </x-field>
                            <x-field label="{{ __('cpanel/users.new_password_confirmation') }}" name="confirm_password">
                                <input type="password" id="confirm_password" class="form-control w-full" name="password_confirmation" value="">
                            </x-field>
                            <x-field label="{{ __('cpanel/users.name') }}" name="name">
                                <input type="text" class="form-control w-full" id="name" name="name" value="{{ old('name', $user->name) }}">
                            </x-field>
                            <x-field label="{{ __('cpanel/users.surname') }}" name="surname">
                                <input type="text" class="form-control w-full" id="surname" name="surname" value="{{ old('surname', $user->surname) }}">
                            </x-field>
                            <x-field label="{{ __('cpanel/users.country') }}">
                                <select name="country" id="country" class="form-control">
                                    @foreach($countries as $country)
                                        <option value="{{$country['name']}}" {{$country['name'] === $user->country ? 'selected' : ''}}>{{$country['name']}}</option>
                                    @endforeach
                                </select>
                            </x-field>
                            <x-field label="{{ __('cpanel/users.city') }}">
                                <input type="text" name="city" class="form-control w-full" value="{{ old('city', $user->city) }}">
                            </x-field>
                        </div>

                        @if (Auth::user()->can('manage_users', 'App\Http\Models\UserRoles'))
                            <x-field label="{{ __('cpanel/users.status') }}">
                                <select name="role_id" id="user_role" class="form-control">
                                    @foreach($user_roles as $role)
                                        <option value="{{$role->id}}" {{$user->role->name === $role->name ? 'selected' : ''}}>{{$role->name}}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        @endif

                        <x-field label="{{ __('cpanel/users.about') }}">
                            <textarea rows="4" class="form-control w-full" name="about_me">{{ old('about_me', $user->about_me) }}</textarea>
                        </x-field>

                        <fieldset class="mt-2 rounded-lg border border-border p-4">
                            <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-muted">@lang('cpanel/users.social_profiles')</legend>
                            <div class="grid grid-cols-1 gap-x-5 md:grid-cols-2">
                                <x-field label="{{ __('cpanel/users.facebook') }}">
                                    <input type="text" class="form-control w-full" name="facebook_url" placeholder="https://" value="{{ old('facebook_url', $user->facebook_url) }}">
                                </x-field>
                                <x-field label="{{ __('cpanel/users.google') }}">
                                    <input type="text" class="form-control w-full" name="google_url" placeholder="https://" value="{{ old('google_url', $user->google_url) }}">
                                </x-field>
                                <x-field label="{{ __('cpanel/users.twitter') }}">
                                    <input type="text" class="form-control w-full" name="twitter_url" placeholder="https://" value="{{ old('twitter_url', $user->twitter_url) }}">
                                </x-field>
                                <x-field label="{{ __('cpanel/users.instagram') }}">
                                    <input type="text" class="form-control w-full" name="instagram_url" placeholder="https://" value="{{ old('instagram_url', $user->instagram_url) }}">
                                </x-field>
                                <x-field label="{{ __('cpanel/users.linkedin') }}">
                                    <input type="text" class="form-control w-full" name="linkedin_url" placeholder="https://" value="{{ old('linkedin_url', $user->linkedin_url) }}">
                                </x-field>
                                <x-field label="{{ __('cpanel/users.xing') }}">
                                    <input type="text" class="form-control w-full" name="xing_url" placeholder="https://" value="{{ old('xing_url', $user->xing_url) }}">
                                </x-field>
                            </div>
                        </fieldset>

                        <div class="field">
                            <span class="field-label">@lang('cpanel/users.gender')</span>
                            <div class="flex flex-wrap gap-6">
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                                    <input class="form-check-input" type="radio" name="gender" {{$user->gender === "male" ? 'checked' : null}} value="male" id="male"> @lang('cpanel/users.gender_male')
                                </label>
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                                    <input class="form-check-input" type="radio" name="gender" {{$user->gender === "female" ? 'checked' : null}} value="female" id="female"> @lang('cpanel/users.gender_female')
                                </label>
                            </div>
                        </div>
                        <x-slot:footer>
                            <div class="flex justify-end">
                                <x-button type="submit" variant="primary">@lang('cpanel/users.update_button_label')</x-button>
                            </div>
                        </x-slot:footer>
                    </x-card>
                </div>

                {{-- Avatar / identity card --}}
                <div class="lg:col-span-1">
                    <div class="card card-user">
                        <div class="card-body text-center">
                            <span class="user-avatar block">
                                @if(!empty($user->avatar))
                                    <img id="file-image" class="avatar border-gray" src="{{$user->avatar}}" alt="User profile" />
                                @else
                                    <img id="file-image" class="avatar border-gray" src="{{asset('admin')}}/img/faces/noavatar.svg" type="file" name="fileUpload" accept="image/*" />
                                @endif
                                <span class="input-group-btn mt-3 inline-block">
                                    <a id="lfm" data-input="thumbnail" data-preview="holder" class="choose-image">
                                        @lang('cpanel/users.avatar_edit')
                                    </a>
                                </span>
                                <input id="file-upload" value="{{old('avatar', $user->avatar)}}" type="hidden" name="avatar" />
                            </span>
                            <h5 class="title mt-4 text-base font-semibold text-fg">{{$user->name}} {{$user->surname}}</h5>
                            <p class="description text-sm text-muted">{{$user->username}}</p>
                            @if($user->about_me)
                                <p class="description mt-3 text-sm leading-relaxed text-muted">{{$user->about_me}}</p>
                            @endif
                        </div>
                        @php
                            $socials = [
                                'facebook_url' => 'fa-facebook-square',
                                'google_url' => 'fa-google-plus-square',
                                'twitter_url' => 'fa-twitter',
                                'instagram_url' => 'fa-instagram',
                                'linkedin_url' => 'fa-linkedin-square',
                                'xing_url' => 'fa-xing-square',
                            ];
                            $has_social = collect($socials)->keys()->some(fn ($k) => !empty($user->$k));
                        @endphp
                        @if($has_social)
                            <div class="border-t border-border">
                                <div class="button-container">
                                    @foreach($socials as $field => $icon)
                                        @if($user->$field)
                                            <a href="{{ old($field, $user->$field) }}" target="_blank" aria-label="{{ $field }}">
                                                <i class="fa {{ $icon }}"></i>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('extrascripts')
    <script src="{{asset('')}}/vendor/laravel-filemanager/js/lfm.js"></script>
@endpush

@push('finalscripts')
    <script src="{{asset('admin')}}/js/user.js"></script>
    <script>
        var site_url = "<?php echo config('app.url'); ?>/";
    </script>
    <script src="{{asset('admin')}}/js/thumbnail.js"></script>
@endpush
