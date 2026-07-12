<?php
/**
 * AgenticCms-Laravel
 * File: profile.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 14.11.2019
 * Phase 5: redesigned to DESIGN_SYSTEM §5 using component library.
 */
?>
@extends(config('app.template_name').'/index')

@section('content')

@php
    $home_page_data = get_data(1, 'page', ['slug', 'title']);
    $logged_user_name = get_logged_user_username();

    $socials = array_filter([
        'Facebook'  => $user->facebook_url,
        'Google'    => $user->google_url,
        'Twitter'   => $user->twitter_url,
        'Instagram' => $user->instagram_url,
        'LinkedIn'  => $user->linkedin_url,
        'Xing'      => $user->xing_url,
    ]);
@endphp

@include(config('app.template_name').'.partials.banner', [
    'title'  => __('default/profile.profile'),
    'crumbs' => [
        ['label' => $home_page_data->title, 'url' => config('app.url')],
        ['label' => __('default/profile.profile_info'), 'url' => null],
    ],
])

<section class="mx-auto max-w-[1080px] px-5 py-16 sm:px-8 sm:py-24">
    <div class="grid gap-8 lg:grid-cols-[280px_1fr]">

        {{-- Left column: avatar + identity --}}
        <div class="flex flex-col items-center gap-5 lg:items-start">
            @php
                $profile_avatar_src = image_src($user->avatar, true);
                $profile_display_name = trim($user->name.' '.$user->surname) ?: $user->username;
                $profile_initial = $profile_display_name ? strtoupper(mb_substr($profile_display_name, 0, 1)) : '?';
            @endphp
            @if($profile_avatar_src)
                <img
                    src="{{ $profile_avatar_src }}"
                    alt="{{ $profile_display_name }}"
                    width="96"
                    height="96"
                    class="h-24 w-24 shrink-0 rounded-2xl object-cover shadow-card ring-1 ring-[var(--border-strong)]"
                />
            @else
                <span
                    role="img"
                    aria-label="{{ $profile_display_name }}"
                    class="inline-flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl bg-[var(--surface-2)] font-mono text-2xl text-[var(--text-muted)] ring-1 ring-[var(--border-strong)]"
                >{{ $profile_initial }}</span>
            @endif

            <div class="text-center lg:text-left">
                <h1 class="font-serif text-2xl font-medium tracking-[-0.01em] text-[var(--text)]">
                    {{ $profile_display_name }}
                </h1>
                <p class="mt-1 font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-muted)]">
                    @@{{ $user->username }}
                </p>
            </div>

            {{-- Role + gender badges --}}
            <div class="flex flex-wrap items-center justify-center gap-2 lg:justify-start">
                <x-badge variant="primary">{{ $user->role->name }}</x-badge>
                @if($user->gender)
                    <x-badge variant="neutral" class="capitalize">{{ $user->gender }}</x-badge>
                @endif
            </div>

            {{-- Edit own profile link --}}
            @if($user->username === $logged_user_name)
                <x-button href="{{ route('get_user_info') }}" variant="outline" size="sm" icon="user">
                    @lang('default/profile.edit')
                </x-button>
            @endif
        </div>

        {{-- Right column: details --}}
        <div class="flex flex-col gap-8">

            {{-- About me --}}
            @if($user->about_me)
                <x-card>
                    <x-eyebrow class="mb-3">@lang('default/profile.about_me')</x-eyebrow>
                    <p class="font-serif text-lg leading-relaxed text-[var(--text)] border-l-2 border-[var(--primary)] pl-4">
                        {{ $user->about_me }}
                    </p>
                </x-card>
            @endif

            {{-- Contact / location details --}}
            <x-card>
                <x-eyebrow class="mb-4">@lang('default/profile.details')</x-eyebrow>
                <dl class="grid gap-x-10 gap-y-5 sm:grid-cols-2">
                    <div>
                        <dt class="font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-muted)]">
                            @lang('default/profile.email')
                        </dt>
                        <dd class="mt-1 text-base text-[var(--text)]">{{ $user->email }}</dd>
                    </div>

                    @if($user->country)
                        <div>
                            <dt class="font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-muted)]">
                                @lang('default/profile.country')
                            </dt>
                            <dd class="mt-1 flex items-center gap-1.5 text-base text-[var(--text)]">
                                <x-icon name="chevron-right" class="text-[var(--text-subtle)] w-3.5 h-3.5" aria-hidden="true" />
                                {{ $user->country }}
                            </dd>
                        </div>
                    @endif

                    @if($user->city)
                        <div>
                            <dt class="font-mono text-xs uppercase tracking-[0.08em] text-[var(--text-muted)]">
                                @lang('default/profile.city')
                            </dt>
                            <dd class="mt-1 flex items-center gap-1.5 text-base text-[var(--text)]">
                                <x-icon name="chevron-right" class="text-[var(--text-subtle)] w-3.5 h-3.5" aria-hidden="true" />
                                {{ $user->city }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            {{-- Social links --}}
            @if(!empty($socials))
                <x-card>
                    <x-eyebrow class="mb-4">Links</x-eyebrow>
                    <div class="flex flex-wrap gap-2">
                        @foreach($socials as $label => $url)
                            <a
                                href="{{ $url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="{{ $label }} ({{ __('default/profile.opens_in_new_tab') }})"
                                class="inline-flex items-center gap-2 rounded-full border border-[var(--border-strong)] bg-[var(--surface)] px-4 py-2 text-sm font-medium text-[var(--text)] transition-colors duration-[var(--dur-fast)] hover:border-[var(--primary)] hover:bg-[var(--surface-2)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--ring)] focus-visible:ring-offset-2 active:scale-[0.98]"
                            >
                                {{ $label }}
                                <x-icon name="external-link" class="h-3.5 w-3.5 text-[var(--text-muted)]" aria-hidden="true" />
                            </a>
                        @endforeach
                    </div>
                </x-card>
            @endif

        </div>
    </div>
</section>

@endsection
