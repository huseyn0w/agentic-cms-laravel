<?php
/**
 * AgenticCms-Laravel
 * File: yourprofile.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 12.11.2019
 * Phase 5: redesigned to DESIGN_SYSTEM §5 using component library.
 *          avatarUpload Alpine component, form wiring, and all field names preserved.
 */
?>
@extends(config('app.template_name').'/index')

@section('content')

@php
    $home_page_data = get_data(1, 'page', ['slug', 'title']);
    $countries = get_countries_array();
    $avatar_src = image_src($user->avatar, true);
@endphp

@include(config('app.template_name').'.partials.banner', [
    'title'  => __('default/profile.profile'),
    'crumbs' => [
        ['label' => $home_page_data->title, 'url' => config('app.url')],
        ['label' => __('default/profile.edit_profile'), 'url' => null],
    ],
])

<section class="mx-auto max-w-[720px] px-5 py-16 sm:px-8 sm:py-24">

    {{-- Validation errors --}}
    @if ($errors->any())
        <x-alert variant="error" class="mb-6">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    {{-- Session flash --}}
    @if (Session::has('message'))
        @if (Session::get('message'))
            <x-alert variant="success" class="mb-6">@lang('default/profile.user_updated')</x-alert>
        @else
            <x-alert variant="error" class="mb-6">@lang('default/profile.user_update_error')</x-alert>
        @endif
    @endif

    <form action="{{ route('update_user_info') }}" method="POST" enctype="multipart/form-data" class="space-y-8" novalidate>
        @method('PUT')
        @csrf

        {{-- Avatar upload — avatarUpload Alpine component preserved --}}
        <x-card>
            <x-slot name="header">
                <x-eyebrow>@lang('default/profile.avatar')</x-eyebrow>
            </x-slot>

            <div x-data="avatarUpload('{{ $avatar_src }}')" class="flex items-center gap-6">
                <img
                    :src="preview"
                    x-ref="preview"
                    alt="{{ $user->username }}"
                    width="96"
                    height="96"
                    decoding="async"
                    class="h-24 w-24 rounded-2xl object-cover ring-1 ring-[var(--border-strong)] shadow-card"
                />
                <div class="flex flex-col gap-2">
                    <label
                        for="file-upload"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-md border border-[var(--border-strong)] bg-[var(--surface)] px-4 h-10 text-sm font-medium text-[var(--text)] transition-colors duration-[var(--dur-fast)] hover:bg-[var(--surface-2)] focus-within:ring-2 focus-within:ring-[var(--ring)] focus-within:ring-offset-2 active:scale-[0.98]"
                    >
                        <x-icon name="upload" class="h-4 w-4" aria-hidden="true" />
                        @lang('default/profile.edit')
                        <input
                            id="file-upload"
                            type="file"
                            name="avatar"
                            accept="image/*"
                            class="sr-only"
                            @change="pick"
                        />
                    </label>
                    <p class="font-mono text-xs text-[var(--text-subtle)]">PNG, JPG, GIF, WEBP</p>
                </div>
            </div>
        </x-card>

        {{-- Account fields --}}
        <x-card>
            <x-slot name="header">
                <div class="flex items-center justify-between">
                    <x-eyebrow>@lang('default/profile.account')</x-eyebrow>
                    <a
                        href="{{ route('get_change_password_interface') }}"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-[var(--primary)] hover:text-[var(--primary-hover)] transition-colors duration-[var(--dur-fast)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--ring)] focus-visible:ring-offset-2 rounded"
                    >
                        <x-icon name="user" class="h-4 w-4" aria-hidden="true" />
                        @lang('default/profile.change_password')
                    </a>
                </div>
            </x-slot>

            <div class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    @if($user->username)
                        <div>
                            <span class="block mb-1.5 text-sm font-medium font-sans text-[var(--text)]">@lang('default/profile.username')</span>
                            <p class="h-10 flex items-center rounded-sm border border-[var(--border)] bg-[var(--surface-2)] px-3 text-base text-[var(--text-muted)] select-none">
                                {{ $user->username }}
                            </p>
                        </div>
                    @endif
                    <div @class(['sm:col-span-2' => !$user->username])>
                        <x-field
                            label="{{ __('default/profile.email') }}"
                            name="email"
                            :error="$errors->first('email')"
                        >
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                class="w-full h-10 bg-[var(--surface)] border border-[var(--border-strong)] rounded-sm px-3 text-[var(--text)] placeholder:text-[var(--text-subtle)] focus:outline-none focus:border-[var(--ring)] focus:ring-2 focus:ring-[var(--ring)]/30 transition-colors duration-[var(--dur-fast)]"
                                @if($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif
                            />
                        </x-field>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field
                        label="{{ __('default/profile.name') }}"
                        name="name"
                        :error="$errors->first('name')"
                    >
                        <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="{{ __('default/profile.name') }}"
                            value="{{ old('name', $user->name) }}"
                            class="w-full h-10 bg-[var(--surface)] border border-[var(--border-strong)] rounded-sm px-3 text-[var(--text)] placeholder:text-[var(--text-subtle)] focus:outline-none focus:border-[var(--ring)] focus:ring-2 focus:ring-[var(--ring)]/30 transition-colors duration-[var(--dur-fast)]"
                            @if($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif
                        />
                    </x-field>
                    <x-field
                        label="{{ __('default/profile.surname') }}"
                        name="surname"
                        :error="$errors->first('surname')"
                    >
                        <input
                            type="text"
                            id="surname"
                            name="surname"
                            placeholder="{{ __('default/profile.surname') }}"
                            value="{{ old('surname', $user->surname) }}"
                            class="w-full h-10 bg-[var(--surface)] border border-[var(--border-strong)] rounded-sm px-3 text-[var(--text)] placeholder:text-[var(--text-subtle)] focus:outline-none focus:border-[var(--ring)] focus:ring-2 focus:ring-[var(--ring)]/30 transition-colors duration-[var(--dur-fast)]"
                            @if($errors->has('surname')) aria-invalid="true" aria-describedby="surname-error" @endif
                        />
                    </x-field>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field
                        label="{{ __('default/profile.country') }}"
                        name="country"
                        :error="$errors->first('country')"
                    >
                        <select
                            id="country"
                            name="country"
                            class="w-full h-10 bg-[var(--surface)] border border-[var(--border-strong)] rounded-sm px-3 text-[var(--text)] focus:outline-none focus:border-[var(--ring)] focus:ring-2 focus:ring-[var(--ring)]/30 transition-colors duration-[var(--dur-fast)]"
                            @if($errors->has('country')) aria-invalid="true" aria-describedby="country-error" @endif
                        >
                            @foreach($countries as $country)
                                <option value="{{ $country['name'] }}" @selected($country['name'] === $user->country)>{{ $country['name'] }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field
                        label="{{ __('default/profile.city') }}"
                        name="city"
                        :error="$errors->first('city')"
                    >
                        <input
                            type="text"
                            id="city"
                            name="city"
                            value="{{ old('city', $user->city) }}"
                            class="w-full h-10 bg-[var(--surface)] border border-[var(--border-strong)] rounded-sm px-3 text-[var(--text)] placeholder:text-[var(--text-subtle)] focus:outline-none focus:border-[var(--ring)] focus:ring-2 focus:ring-[var(--ring)]/30 transition-colors duration-[var(--dur-fast)]"
                            @if($errors->has('city')) aria-invalid="true" aria-describedby="city-error" @endif
                        />
                    </x-field>
                </div>

                <x-field
                    label="{{ __('default/profile.about') }}"
                    name="about_me"
                    :error="$errors->first('about_me')"
                >
                    <textarea
                        id="about_me"
                        name="about_me"
                        rows="4"
                        class="w-full bg-[var(--surface)] border border-[var(--border-strong)] rounded-sm px-3 py-2.5 text-[var(--text)] placeholder:text-[var(--text-subtle)] resize-y focus:outline-none focus:border-[var(--ring)] focus:ring-2 focus:ring-[var(--ring)]/30 transition-colors duration-[var(--dur-fast)]"
                        @if($errors->has('about_me')) aria-invalid="true" aria-describedby="about_me-error" @endif
                    >{{ old('about_me', $user->about_me) }}</textarea>
                </x-field>
            </div>
        </x-card>

        {{-- Social links --}}
        <x-card>
            <x-slot name="header">
                <x-eyebrow>Links</x-eyebrow>
            </x-slot>

            <div class="grid gap-5 sm:grid-cols-2">
                @foreach([
                    'facebook_url'  => 'Facebook',
                    'google_url'    => 'Google',
                    'twitter_url'   => 'Twitter',
                    'instagram_url' => 'Instagram',
                    'linkedin_url'  => 'Linkedin',
                    'xing_url'      => 'Xing',
                ] as $field => $label)
                    <x-field
                        :label="$label"
                        :name="$field"
                        :error="$errors->first($field)"
                    >
                        <input
                            type="text"
                            id="{{ $field }}"
                            name="{{ $field }}"
                            placeholder="https://"
                            value="{{ old($field, $user->$field) }}"
                            class="w-full h-10 bg-[var(--surface)] border border-[var(--border-strong)] rounded-sm px-3 text-[var(--text)] placeholder:text-[var(--text-subtle)] focus:outline-none focus:border-[var(--ring)] focus:ring-2 focus:ring-[var(--ring)]/30 transition-colors duration-[var(--dur-fast)]"
                            @if($errors->has($field)) aria-invalid="true" aria-describedby="{{ $field }}-error" @endif
                        />
                    </x-field>
                @endforeach
            </div>
        </x-card>

        {{-- Gender — radio pill cards; values "male"/"female" preserved --}}
        <x-card>
            <x-slot name="header">
                <x-eyebrow>@lang('default/profile.gender')</x-eyebrow>
            </x-slot>

            <fieldset>
                <legend class="sr-only">@lang('default/profile.gender')</legend>
                <div class="flex flex-wrap gap-3">
                    <label class="flex flex-1 min-w-[140px] cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition-colors duration-[var(--dur-fast)] has-[:checked]:border-[var(--primary)] has-[:checked]:bg-[var(--surface-2)] border-[var(--border-strong)]">
                        <input
                            type="radio"
                            name="gender"
                            value="male"
                            id="male"
                            @checked($user->gender === 'male')
                            class="h-4 w-4 text-[var(--primary)] focus:ring-[var(--ring)] border-[var(--border-strong)]"
                        />
                        <span class="text-sm font-medium text-[var(--text)]">@lang('default/profile.gender_male')</span>
                    </label>
                    <label class="flex flex-1 min-w-[140px] cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition-colors duration-[var(--dur-fast)] has-[:checked]:border-[var(--primary)] has-[:checked]:bg-[var(--surface-2)] border-[var(--border-strong)]">
                        <input
                            type="radio"
                            name="gender"
                            value="female"
                            id="female"
                            @checked($user->gender === 'female')
                            class="h-4 w-4 text-[var(--primary)] focus:ring-[var(--ring)] border-[var(--border-strong)]"
                        />
                        <span class="text-sm font-medium text-[var(--text)]">@lang('default/profile.gender_female')</span>
                    </label>
                </div>
            </fieldset>
        </x-card>

        {{-- Submit --}}
        <div class="flex justify-end">
            <x-button type="submit" variant="primary" size="md">
                @lang('default/profile.updated_profile')
            </x-button>
        </div>
    </form>
</section>

@endsection
