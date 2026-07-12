<?php
/**
 * AgenticCms-Laravel
 * File: contacts.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 08.10.2019
 * Template Name: "Contact Page";
 * Phase 5: redesigned to DESIGN_SYSTEM §3/§4/§5 — x-field, x-button, x-alert.
 */
?>


@php
    if(is_logged_in()):
        $firstname = \Auth::user()->name;
        $lastname = \Auth::user()->surname;
        $email = \Auth::user()->email;
    endif;
@endphp

@extends(config('app.template_name').'/index')

@section('content')

@include(config('app.template_name').'.partials.banner', [
    'title'  => $data->title,
    'crumbs' => [
        ['label' => $home_page_data->title, 'url' => config('app.url')],
        ['label' => $data->title, 'url' => null],
    ],
])

<section class="mx-auto max-w-[720px] px-4 py-16 sm:px-6 sm:py-20 lg:px-8">

    {{-- Validation errors --}}
    @if (count($errors) > 0)
        <x-alert variant="error" class="mb-6">
            <ul class="list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    {{-- Success flash --}}
    @if ($message = Session::get('success'))
        <x-alert variant="success" class="mb-6">
            {{ $message }}
        </x-alert>
    @endif

    <div class="mb-8">
        <h2 class="font-serif text-2xl font-medium text-[var(--text)]">@lang('default/page.have_question')</h2>
    </div>

    <form action="{{ route('sendform') }}" method="post" class="space-y-5" novalidate>
        @csrf

        @if(is_logged_in())
            <input type="hidden" name="first_name" value="{{ old('first_name', $firstname) }}">
            <input type="hidden" name="last_name"  value="{{ old('last_name', $lastname) }}">
            <input type="hidden" name="email"      value="{{ old('email', $email) }}">

            <x-field name="subject" :label="__('default/page.subject')" :required="true" :error="$errors->first('subject')">
                <input
                    id="subject"
                    type="text"
                    name="subject"
                    placeholder="{{ __('default/page.subject') }}"
                    required
                    @if($errors->has('subject')) aria-invalid="true" aria-describedby="subject-error" @endif
                    value="{{ old('subject') }}"
                    class="field-input"
                >
            </x-field>
        @else
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="first_name" :label="__('default/page.first_name')" :required="true" :error="$errors->first('first_name')">
                    <input
                        id="first_name"
                        type="text"
                        name="first_name"
                        placeholder="{{ __('default/page.first_name') }}"
                        required
                        @if($errors->has('first_name')) aria-invalid="true" aria-describedby="first_name-error" @endif
                        value="{{ old('first_name') }}"
                        class="field-input"
                    >
                </x-field>

                <x-field name="last_name" :label="__('default/page.last_name')" :required="true" :error="$errors->first('last_name')">
                    <input
                        id="last_name"
                        type="text"
                        name="last_name"
                        placeholder="{{ __('default/page.last_name') }}"
                        required
                        @if($errors->has('last_name')) aria-invalid="true" aria-describedby="last_name-error" @endif
                        value="{{ old('last_name') }}"
                        class="field-input"
                    >
                </x-field>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="email" :label="__('default/page.email')" :required="true" :error="$errors->first('email')">
                    <input
                        id="email"
                        type="email"
                        name="email"
                        placeholder="{{ __('default/page.email') }}"
                        required
                        @if($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif
                        value="{{ old('email') }}"
                        class="field-input"
                    >
                </x-field>

                <x-field name="subject" :label="__('default/page.subject')" :required="true" :error="$errors->first('subject')">
                    <input
                        id="subject"
                        type="text"
                        name="subject"
                        placeholder="{{ __('default/page.subject') }}"
                        required
                        @if($errors->has('subject')) aria-invalid="true" aria-describedby="subject-error" @endif
                        value="{{ old('subject') }}"
                        class="field-input"
                    >
                </x-field>
            </div>
        @endif

        <x-field name="message" :label="__('default/page.message')" :required="true" :error="$errors->first('message')">
            <textarea
                id="message"
                name="message"
                rows="6"
                placeholder="{{ __('default/page.message') }}"
                required
                @if($errors->has('message')) aria-invalid="true" aria-describedby="message-error" @endif
                class="field-input resize-y"
            >{{ old('message') }}</textarea>
        </x-field>

        {{-- Captcha (renders nothing when keys are absent; keeps g-recaptcha-response). --}}
        <div class="[&_div]:!mt-0">
            {!! app('captcha')->render(); !!}
        </div>

        <div class="pt-2">
            <x-button type="submit" variant="primary" size="md" icon="arrow-right">
                @lang('default/page.submit')
            </x-button>
        </div>
    </form>
</section>

@endsection
