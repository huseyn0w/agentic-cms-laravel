<?php
/**
 * AgenticCms-Laravel
 * File: seo-settings.blade.php
 * Phase 7 (SEO/GEO): global SEO settings admin page.
 * Redesigned: DESIGN_SYSTEM §5 — x-card / x-field / x-button / x-alert / x-eyebrow
 * Preserves: ALL field names (title_separator, default_meta_description, default_og_image,
 *            og_site_name, twitter_handle, google_site_verification, bing_site_verification,
 *            ga4_measurement_id, gtm_container_id, discourage_search_engines, sitemap_enabled,
 *            robots_extra) and form action route cpanel_update_seo_settings.
 */
?>

@extends('cpanel.core.index')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/settings.seo_settings_headline')</h1>
        </div>

        @include('cpanel.core.flash')
        @if (($update_message = Session::get('message')) !== null)
            <x-alert variant="{{ $update_message ? 'success' : 'error' }}" class="mb-4">
                {{ $update_message ? __('cpanel/settings.seo_settings_updates_success') : $update_message }}
            </x-alert>
        @endif

        @if ($errors->any())
            <x-alert variant="error" class="mb-4">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </x-alert>
        @endif

        <form action="{{ route('cpanel_update_seo_settings') }}" method="POST">
            @csrf

            {{-- Meta defaults --}}
            <x-card class="mb-6">
                <x-slot:header>
                    <x-eyebrow>@lang('cpanel/settings.seo_meta_section')</x-eyebrow>
                </x-slot:header>
                <div class="space-y-4">
                    <x-field label="{{ __('cpanel/settings.seo_title_separator') }}" name="title_separator" :required="true">
                        <input type="text" id="title_separator" required maxlength="8" name="title_separator" class="form-control w-full" value="{{ old('title_separator', $seo_settings->title_separator) }}">
                    </x-field>
                    <x-field label="{{ __('cpanel/settings.seo_default_description') }}" name="default_meta_description">
                        <textarea rows="3" id="default_meta_description" name="default_meta_description" class="form-control w-full">{{ old('default_meta_description', $seo_settings->default_meta_description) }}</textarea>
                    </x-field>
                    <x-field label="{{ __('cpanel/settings.seo_default_og_image') }}" name="default_og_image">
                        <input type="text" id="default_og_image" name="default_og_image" class="form-control w-full" value="{{ old('default_og_image', $seo_settings->default_og_image) }}" placeholder="https://...">
                    </x-field>
                </div>
            </x-card>

            {{-- Social --}}
            <x-card class="mb-6">
                <x-slot:header>
                    <x-eyebrow>@lang('cpanel/settings.seo_social_section')</x-eyebrow>
                </x-slot:header>
                <div class="space-y-4">
                    <x-field label="{{ __('cpanel/settings.seo_og_site_name') }}" name="og_site_name">
                        <input type="text" id="og_site_name" name="og_site_name" class="form-control w-full" value="{{ old('og_site_name', $seo_settings->og_site_name) }}">
                    </x-field>
                    <x-field label="{{ __('cpanel/settings.seo_twitter_handle') }}" name="twitter_handle">
                        <input type="text" id="twitter_handle" name="twitter_handle" class="form-control w-full" value="{{ old('twitter_handle', $seo_settings->twitter_handle) }}" placeholder="@yourhandle">
                    </x-field>
                </div>
            </x-card>

            {{-- Verification --}}
            <x-card class="mb-6">
                <x-slot:header>
                    <x-eyebrow>@lang('cpanel/settings.seo_verification_section')</x-eyebrow>
                </x-slot:header>
                <div class="space-y-4">
                    <x-field label="{{ __('cpanel/settings.seo_google_verification') }}" name="google_site_verification">
                        <input type="text" id="google_site_verification" name="google_site_verification" class="form-control w-full" value="{{ old('google_site_verification', $seo_settings->google_site_verification) }}">
                    </x-field>
                    <x-field label="{{ __('cpanel/settings.seo_bing_verification') }}" name="bing_site_verification">
                        <input type="text" id="bing_site_verification" name="bing_site_verification" class="form-control w-full" value="{{ old('bing_site_verification', $seo_settings->bing_site_verification) }}">
                    </x-field>
                </div>
            </x-card>

            {{-- Analytics --}}
            <x-card class="mb-6">
                <x-slot:header>
                    <x-eyebrow>@lang('cpanel/settings.seo_analytics_section')</x-eyebrow>
                </x-slot:header>
                <div class="space-y-4">
                    <p class="text-xs text-muted">@lang('cpanel/settings.seo_analytics_help')</p>
                    <x-field label="{{ __('cpanel/settings.seo_ga4_id') }}" name="ga4_measurement_id">
                        <input type="text" id="ga4_measurement_id" name="ga4_measurement_id" class="form-control w-full" value="{{ old('ga4_measurement_id', $seo_settings->ga4_measurement_id) }}" placeholder="G-XXXXXXX">
                    </x-field>
                    <x-field label="{{ __('cpanel/settings.seo_gtm_id') }}" name="gtm_container_id">
                        <input type="text" id="gtm_container_id" name="gtm_container_id" class="form-control w-full" value="{{ old('gtm_container_id', $seo_settings->gtm_container_id) }}" placeholder="GTM-XXXXXXX">
                    </x-field>
                </div>
            </x-card>

            {{-- Indexing / robots / sitemap --}}
            <x-card>
                <x-slot:header>
                    <x-eyebrow>@lang('cpanel/settings.seo_indexing_section')</x-eyebrow>
                </x-slot:header>
                <div class="space-y-4">
                    <div class="flex flex-col gap-2">
                        <label for="discourage" class="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                            <input class="form-check-input" id="discourage" name="discourage_search_engines" type="checkbox" value="1" {{ old('discourage_search_engines', $seo_settings->discourage_search_engines) ? 'checked' : '' }}>
                            @lang('cpanel/settings.seo_discourage')
                        </label>
                        <p class="text-xs text-muted ml-6">@lang('cpanel/settings.seo_discourage_help')</p>
                        <label for="sitemap_enabled" class="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                            <input class="form-check-input" id="sitemap_enabled" name="sitemap_enabled" type="checkbox" value="1" {{ old('sitemap_enabled', $seo_settings->sitemap_enabled) ? 'checked' : '' }}>
                            @lang('cpanel/settings.seo_sitemap_enabled')
                        </label>
                    </div>
                    <x-field label="{{ __('cpanel/settings.seo_robots_extra') }}" name="robots_extra" help="{{ __('cpanel/settings.seo_robots_extra_help') }}">
                        <textarea rows="4" id="robots_extra" name="robots_extra" class="form-control w-full font-mono text-sm">{{ old('robots_extra', $seo_settings->robots_extra) }}</textarea>
                    </x-field>
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
