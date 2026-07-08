<?php
/**
 * Cmstack-Laravel
 * File: general-settings.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 21.07.2019
 * Redesigned: DESIGN_SYSTEM §5 — x-card / x-field / x-button / x-alert
 */
?>

@extends('cpanel.core.index')

@section('content')
    @php
        $website_name = $general_settings->website_name;
        $tagline = $general_settings->tagline;
        $email = $general_settings->contact_email;
        $membership = $general_settings->membership;
        $email_verification = $general_settings->email_verification;
        $active_template_name = $general_settings->active_template_name;
        $posts_per_page = $general_settings->posts_per_page;
        $comments_per_page = $general_settings->comments_per_page;
        $directories = get_front_templates_array();
    @endphp

    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/settings.general_settings_headline')</h1>
        </div>

        @include('cpanel.core.flash')
        @if (($update_message = Session::get('message')) !== null)
            <x-alert variant="{{ $update_message ? 'success' : 'error' }}" class="mb-4">
                {{ $update_message ? __('cpanel/settings.general_settings_updates_success') : $update_message }}
            </x-alert>
        @endif

        <form action="{{ route('cpanel_update_general_settings') }}" method="POST">
            @csrf
            <x-card>
                <div class="space-y-4">
                    <x-field label="{{ __('cpanel/settings.website_name') }}" name="website_name" :required="true">
                        <input type="text" id="website_name" required name="website_name" class="form-control w-full" value="{{ old('website_name', $website_name) }}">
                    </x-field>

                    <x-field label="{{ __('cpanel/settings.tagline') }}" name="tagline" :required="true" help="{{ __('cpanel/settings.tagline_content') }}">
                        <textarea rows="3" required name="tagline" id="tagline" class="form-control w-full">{{ old('tagline', $tagline) }}</textarea>
                    </x-field>

                    <x-field label="{{ __('cpanel/settings.contact_email') }}" name="contact_email" :required="true">
                        <input type="email" id="contact_email" required name="contact_email" class="form-control w-full" value="{{ old('contact_email', $email) }}">
                    </x-field>

                    <div class="flex flex-col gap-2">
                        <label for="membership" class="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                            <input class="form-check-input" id="membership" name="membership" type="checkbox" {{$membership == 1 ? 'checked value=1' : null}}>
                            @lang('cpanel/settings.membership')
                        </label>
                        <label for="email_verification" class="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                            <input class="form-check-input" id="email_verification" name="email_verification" type="checkbox" {{$email_verification == 1 ? 'checked value=1' : null}}>
                            @lang('cpanel/settings.email_verification')
                        </label>
                    </div>

                    <x-field label="{{ __('cpanel/settings.active_template') }}" name="active_template_name" :required="true">
                        <select id="active_template_name" name="active_template_name" required class="form-control w-full">
                            @forelse($directories as $key => $value)
                                <option value="{{$value}}" {{ $value === $active_template_name ? 'selected' : '' }}>{{$value}}</option>
                            @empty
                                <option disabled>@lang('cpanel/settings.no_template')</option>
                            @endforelse
                        </select>
                    </x-field>

                    <div class="grid grid-cols-1 gap-x-5 sm:grid-cols-2">
                        <x-field label="{{ __('cpanel/settings.posts_per_page') }}" name="posts_per_page" :required="true">
                            <input type="number" min="1" required id="posts_per_page" name="posts_per_page" class="form-control w-full" value="{{ old('posts_per_page', $posts_per_page) }}">
                        </x-field>
                        <x-field label="{{ __('cpanel/settings.comments_per_page') }}" name="comments_per_page" :required="true">
                            <input type="number" min="1" required id="comments_per_page" name="comments_per_page" class="form-control w-full" value="{{ old('comments_per_page', $comments_per_page) }}">
                        </x-field>
                    </div>
                </div>

                <x-slot:footer>
                    <div class="flex justify-end">
                        <x-button type="submit" variant="primary">@lang('cpanel/settings.update_button_label')</x-button>
                    </div>
                </x-slot:footer>
            </x-card>
        </form>
    </div>
@endsection
