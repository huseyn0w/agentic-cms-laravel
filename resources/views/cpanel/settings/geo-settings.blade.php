<?php
/**
 * Cmstack-Laravel
 * File: geo-settings.blade.php
 * GEO (Generative Engine Optimization) admin settings page.
 * Redesigned: DESIGN_SYSTEM §5 — x-card / x-field / x-button / x-alert / x-eyebrow
 * Preserves ALL field names + data-testid attributes for tests:
 *   business_name, business_type, description, founder_name, services, service_area,
 *   contact_email, contact_phone, address, same_as, faq, emit_jsonld, include_in_llms
 */
?>

@extends('cpanel.core.index')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/settings.geo_settings_headline')</h1>
            <p class="mt-2 text-sm leading-relaxed text-muted">@lang('cpanel/settings.geo_intro')</p>
        </div>

        @include('cpanel.core.flash')
        @if (($update_message = Session::get('message')) !== null)
            <x-alert variant="{{ $update_message ? 'success' : 'error' }}" class="mb-4">
                {{ $update_message ? __('cpanel/settings.geo_settings_updates_success') : $update_message }}
            </x-alert>
        @endif

        @if ($errors->any())
            <x-alert variant="error" class="mb-4">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </x-alert>
        @endif

        <form action="{{ route('cpanel_update_geo_settings') }}" method="POST">
            @csrf

            {{-- Identity --}}
            <x-card class="mb-6">
                <x-slot:header>
                    <x-eyebrow>@lang('cpanel/settings.geo_identity_section')</x-eyebrow>
                </x-slot:header>
                <div class="space-y-4">
                    <x-field label="{{ __('cpanel/settings.geo_business_name') }}" name="business_name">
                        <input type="text" id="business_name" name="business_name" class="form-control w-full" value="{{ old('business_name', $geo_settings->business_name) }}" data-testid="geo-business-name">
                    </x-field>
                    <x-field label="{{ __('cpanel/settings.geo_business_type') }}" name="business_type">
                        @php $type = old('business_type', $geo_settings->business_type ?? 'Organization'); @endphp
                        <select id="business_type" name="business_type" class="form-control w-full" data-testid="geo-business-type">
                            <option value="Organization" @selected($type === 'Organization')>@lang('cpanel/settings.geo_type_organization')</option>
                            <option value="LocalBusiness" @selected($type === 'LocalBusiness')>@lang('cpanel/settings.geo_type_localbusiness')</option>
                            <option value="ProfessionalService" @selected($type === 'ProfessionalService')>@lang('cpanel/settings.geo_type_professionalservice')</option>
                            <option value="Person" @selected($type === 'Person')>@lang('cpanel/settings.geo_type_person')</option>
                        </select>
                    </x-field>
                    <x-field label="{{ __('cpanel/settings.geo_description') }}" name="description">
                        <textarea rows="3" id="description" name="description" class="form-control w-full" data-testid="geo-description">{{ old('description', $geo_settings->description) }}</textarea>
                    </x-field>
                    <x-field label="{{ __('cpanel/settings.geo_founder_name') }}" name="founder_name">
                        <input type="text" id="founder_name" name="founder_name" class="form-control w-full" value="{{ old('founder_name', $geo_settings->founder_name) }}">
                    </x-field>
                </div>
            </x-card>

            {{-- Services & reach --}}
            <x-card class="mb-6">
                <x-slot:header>
                    <x-eyebrow>@lang('cpanel/settings.geo_services_section')</x-eyebrow>
                </x-slot:header>
                <div class="space-y-4">
                    <x-field label="{{ __('cpanel/settings.geo_services') }}" name="services" help="{{ __('cpanel/settings.geo_services_help') }}">
                        <textarea rows="5" id="services" name="services" class="form-control w-full" placeholder="Laravel development&#10;Custom CMS&#10;AI / MCP integration" data-testid="geo-services">{{ old('services', $geo_settings->services) }}</textarea>
                    </x-field>
                    <x-field label="{{ __('cpanel/settings.geo_service_area') }}" name="service_area" help="{{ __('cpanel/settings.geo_service_area_help') }}">
                        <input type="text" id="service_area" name="service_area" class="form-control w-full" value="{{ old('service_area', $geo_settings->service_area) }}" data-testid="geo-service-area">
                    </x-field>
                </div>
            </x-card>

            {{-- Contact --}}
            <x-card class="mb-6">
                <x-slot:header>
                    <x-eyebrow>@lang('cpanel/settings.geo_contact_section')</x-eyebrow>
                </x-slot:header>
                <div class="space-y-4">
                    <x-field label="{{ __('cpanel/settings.geo_contact_email') }}" name="contact_email">
                        <input type="email" id="contact_email" name="contact_email" class="form-control w-full" value="{{ old('contact_email', $geo_settings->contact_email) }}">
                    </x-field>
                    <x-field label="{{ __('cpanel/settings.geo_contact_phone') }}" name="contact_phone">
                        <input type="text" id="contact_phone" name="contact_phone" class="form-control w-full" value="{{ old('contact_phone', $geo_settings->contact_phone) }}">
                    </x-field>
                    <x-field label="{{ __('cpanel/settings.geo_address') }}" name="address">
                        <input type="text" id="address" name="address" class="form-control w-full" value="{{ old('address', $geo_settings->address) }}">
                    </x-field>
                </div>
            </x-card>

            {{-- Authority / citations --}}
            <x-card class="mb-6">
                <x-slot:header>
                    <x-eyebrow>@lang('cpanel/settings.geo_authority_section')</x-eyebrow>
                </x-slot:header>
                <div class="space-y-4">
                    <x-field label="{{ __('cpanel/settings.geo_same_as') }}" name="same_as" help="{{ __('cpanel/settings.geo_same_as_help') }}">
                        <textarea rows="4" id="same_as" name="same_as" class="form-control w-full" placeholder="https://linkedin.com/in/...&#10;https://github.com/...">{{ old('same_as', $geo_settings->same_as) }}</textarea>
                    </x-field>
                </div>
            </x-card>

            {{-- FAQ --}}
            <x-card class="mb-6">
                <x-slot:header>
                    <x-eyebrow>@lang('cpanel/settings.geo_faq_section')</x-eyebrow>
                </x-slot:header>
                <div class="space-y-4">
                    <x-field label="{{ __('cpanel/settings.geo_faq') }}" name="faq" help="{{ __('cpanel/settings.geo_faq_help') }}">
                        <textarea rows="5" id="faq" name="faq" class="form-control w-full" placeholder="Do you work remotely? | Yes, with clients across the EU and worldwide.">{{ old('faq', $geo_settings->faq) }}</textarea>
                    </x-field>
                </div>
            </x-card>

            {{-- Output toggles --}}
            <x-card>
                <x-slot:header>
                    <x-eyebrow>@lang('cpanel/settings.geo_output_section')</x-eyebrow>
                </x-slot:header>
                <div class="flex flex-col gap-3">
                    <label for="emit_jsonld" class="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                        <input class="form-check-input" id="emit_jsonld" name="emit_jsonld" type="checkbox" value="1" {{ old('emit_jsonld', $geo_settings->emit_jsonld ?? true) ? 'checked' : '' }}>
                        @lang('cpanel/settings.geo_emit_jsonld')
                    </label>
                    <label for="include_in_llms" class="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                        <input class="form-check-input" id="include_in_llms" name="include_in_llms" type="checkbox" value="1" {{ old('include_in_llms', $geo_settings->include_in_llms ?? true) ? 'checked' : '' }} data-testid="geo-include-in-llms">
                        @lang('cpanel/settings.geo_include_in_llms')
                    </label>
                </div>

                <x-slot:footer>
                    <div class="flex justify-end">
                        <x-button type="submit" variant="primary" data-testid="geo-submit">@lang('cpanel/settings.update_button_label')</x-button>
                    </div>
                </x-slot:footer>
            </x-card>
        </form>
    </div>
@endsection
