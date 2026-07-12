<?php
/**
 * AgenticCms-Laravel
 * File: change_password.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 12.11.2019
 * Phase 5: redesigned to DESIGN_SYSTEM §5 using component library.
 *          Form action, field names (current_password/password/password_confirmation),
 *          and captcha widget preserved.
 */
?>

@extends(config('app.template_name').'/index')

@section('content')

    @php
        $home_page_data = get_data(1, 'page', ['slug', 'title']);
    @endphp

    @include(config('app.template_name').'.partials.banner', [
        'title'  => __('default/change_password.headline'),
        'crumbs' => [
            ['label' => $home_page_data->title, 'url' => config('app.url')],
            ['label' => __('default/change_password.edit_profile'), 'url' => route('get_user_info')],
            ['label' => __('default/change_password.change_password'), 'url' => null],
        ],
    ])

    <section class="mx-auto max-w-[480px] px-5 py-16 sm:px-8 sm:py-24">

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
                <x-alert variant="success" class="mb-6">@lang('default/change_password.password_updated')</x-alert>
            @else
                <x-alert variant="error" class="mb-6">@lang('default/change_password.problem_occurred')</x-alert>
            @endif
        @endif

        <x-card>
            <x-slot name="header">
                <x-eyebrow>@lang('default/change_password.change_password')</x-eyebrow>
            </x-slot>

            <form action="{{ route('change_password_action') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @method('PUT')
                @csrf

                <x-field
                    label="{{ __('default/change_password.current_password') }}"
                    name="current_password"
                    :error="$errors->first('current_password')"
                >
                    <input
                        type="password"
                        required
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                        class="w-full h-10 bg-[var(--surface)] border border-[var(--border-strong)] rounded-sm px-3 text-[var(--text)] placeholder:text-[var(--text-subtle)] focus:outline-none focus:border-[var(--ring)] focus:ring-2 focus:ring-[var(--ring)]/30 transition-colors duration-[var(--dur-fast)]"
                        @if($errors->has('current_password')) aria-invalid="true" aria-describedby="current_password-error" @endif
                    />
                </x-field>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field
                        label="{{ __('default/change_password.new_password') }}"
                        name="password"
                        :error="$errors->first('password')"
                    >
                        <input
                            type="password"
                            required
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            class="w-full h-10 bg-[var(--surface)] border border-[var(--border-strong)] rounded-sm px-3 text-[var(--text)] placeholder:text-[var(--text-subtle)] focus:outline-none focus:border-[var(--ring)] focus:ring-2 focus:ring-[var(--ring)]/30 transition-colors duration-[var(--dur-fast)]"
                            @if($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif
                        />
                    </x-field>

                    <x-field
                        label="{{ __('default/change_password.confirm_new_password') }}"
                        name="confirm_password"
                        :error="$errors->first('password_confirmation')"
                    >
                        <input
                            type="password"
                            required
                            id="confirm_password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            class="w-full h-10 bg-[var(--surface)] border border-[var(--border-strong)] rounded-sm px-3 text-[var(--text)] placeholder:text-[var(--text-subtle)] focus:outline-none focus:border-[var(--ring)] focus:ring-2 focus:ring-[var(--ring)]/30 transition-colors duration-[var(--dur-fast)]"
                            @if($errors->has('password_confirmation')) aria-invalid="true" aria-describedby="confirm_password-error" @endif
                        />
                    </x-field>
                </div>

                <div>
                    {!! app('captcha')->render(); !!}
                </div>

                <div class="pt-2 flex justify-end">
                    <x-button type="submit" variant="primary" size="md">
                        @lang('default/change_password.change_password')
                    </x-button>
                </div>
            </form>
        </x-card>
    </section>

@endsection
