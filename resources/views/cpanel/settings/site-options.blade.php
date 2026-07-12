<?php
/**
 * AgenticCms-Laravel
 * File: site-options.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 21.07.2019
 * Redesigned: DESIGN_SYSTEM §5 — x-card / x-field / x-button / x-alert
 * Preserves: logo_url / copyright / linkedin_url / github_url field names,
 *            the LFM image-picker trigger (#lfm / #thumbnail) + finalscripts.
 */
?>

@extends('cpanel.core.index')

@section('content')
    @php
        $logo_url = $site_options->logo_url;
        $copyright = $site_options->copyright;
        $github_url = $site_options->github_url;
        $linkedin_url = $site_options->linkedin_url;
    @endphp

    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/settings.site_options_headline')</h1>
        </div>

        @include('cpanel.core.flash')
        @if (($update_message = Session::get('message')) !== null)
            <x-alert variant="{{ $update_message ? 'success' : 'error' }}" class="mb-4">
                {{ $update_message ? __('cpanel/settings.site_options_updates_success') : $update_message }}
            </x-alert>
        @endif

        <form action="{{ route('cpanel_update_site_options') }}" method="POST">
            @csrf
            <x-card>
                <div class="space-y-4">
                    {{-- Logo: LFM image-picker trigger + text input (field names preserved) --}}
                    <x-field label="{{ __('cpanel/settings.logo') }}" name="logo_url">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <a id="lfm" data-input="thumbnail" data-preview="holder"
                               class="choose-image inline-flex items-center gap-1.5 rounded-md border border-strong bg-surface-2 px-3 py-2 text-sm text-fg transition-colors hover:bg-surface cursor-pointer shrink-0">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 16-5-5L5 19"/></svg>
                                @lang('cpanel/settings.choose_image')
                            </a>
                            <input id="thumbnail" class="form-control w-full" type="text" name="logo_url" value="{{ old('logo_url', $logo_url) }}">
                        </div>
                    </x-field>

                    <x-field label="{{ __('cpanel/settings.footer_copyright') }}" name="copyright" :required="true">
                        <input type="text" id="copyright" required name="copyright" class="form-control w-full" value="{{ old('copyright', $copyright) }}">
                    </x-field>

                    <x-field label="{{ __('cpanel/settings.linkedin_url') }}" name="linkedin_url" :required="true">
                        <input type="text" id="linkedin_url" required name="linkedin_url" class="form-control w-full" value="{{ old('linkedin_url', $linkedin_url) }}">
                    </x-field>

                    <x-field label="{{ __('cpanel/settings.github_url') }}" name="github_url" :required="true">
                        <input type="text" id="github_url" required name="github_url" class="form-control w-full" value="{{ old('github_url', $github_url) }}">
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

@push('finalscripts')
    <script>
        var site_url = "<?php echo config('app.url'); ?>/";
    </script>
    <script src="{{asset('')}}/vendor/laravel-filemanager/js/lfm.js"></script>
    <script src="{{asset('admin')}}/js/custom-fields/custom-image.js"></script>
@endpush
