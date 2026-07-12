<?php
/**
 * AgenticCms-Laravel
 * File: home.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 21.07.2019
 * Redesigned: DESIGN_SYSTEM §5 — 3-col x-card grid, x-avatar, x-badge, x-empty-state
 * Preserves: $posts / $comments / $users iteration + route links
 */
?>
@extends('cpanel.core.index')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-fg">@lang('cpanel/home.dashboard')</h1>
        <p class="mt-1 text-sm text-muted">@lang('cpanel/home.greetings')</p>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        {{-- Latest posts --}}
        <x-card class="!p-0 overflow-hidden">
            <x-slot:header>
                <div class="flex items-center gap-2.5 px-5 pt-5 pb-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 3h14a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm2 4v2h10V7H7Zm0 4v2h10v-2H7Zm0 4v2h6v-2H7Z"/></svg>
                    </span>
                    <h2 class="text-sm font-semibold text-fg">@lang('cpanel/home.last_posts')</h2>
                </div>
            </x-slot:header>

            @if($posts->isEmpty())
                <x-empty-state icon="info" headline="{{ __('cpanel/home.no_posts') }}" class="py-8" />
            @else
                <ul class="divide-y divide-border">
                    @foreach($posts as $post)
                        <li class="px-5 py-3 text-sm text-fg">
                            <a href="{{route('cpanel_edit_post', ['id' => $post->id, 'lang' => get_current_lang()])}}"
                               class="line-clamp-1 text-fg transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded">
                                {{$post->title}}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        {{-- Latest comments --}}
        <x-card class="!p-0 overflow-hidden">
            <x-slot:header>
                <div class="flex items-center gap-2.5 px-5 pt-5 pb-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4h16a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H9l-5 4V5a1 1 0 0 1 1-1Z"/></svg>
                    </span>
                    <h2 class="text-sm font-semibold text-fg">@lang('cpanel/home.last_comments')</h2>
                </div>
            </x-slot:header>

            @if($comments->isEmpty())
                <x-empty-state icon="info" headline="{{ __('cpanel/home.no_comments') }}" class="py-8" />
            @else
                <ul class="divide-y divide-border">
                    @foreach($comments as $comment)
                        <li class="px-5 py-3 text-sm text-muted">
                            <p class="line-clamp-2">{{$comment->comment}}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        {{-- Latest users --}}
        <x-card class="!p-0 overflow-hidden">
            <x-slot:header>
                <div class="flex items-center gap-2.5 px-5 pt-5 pb-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-success-bg text-success">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5Z"/></svg>
                    </span>
                    <h2 class="text-sm font-semibold text-fg">@lang('cpanel/home.last_users')</h2>
                </div>
            </x-slot:header>

            @if($users->isEmpty())
                <x-empty-state icon="info" headline="{{ __('cpanel/home.no_users') }}" class="py-8" />
            @else
                <ul class="divide-y divide-border">
                    @foreach($users as $user)
                        <li class="flex items-center gap-3 px-5 py-3 text-sm text-fg">
                            <x-avatar :name="$user->username" size="sm" />
                            <span class="line-clamp-1">{{$user->username}}</span>
                            @if(isset($user->role_id))
                                <x-badge variant="neutral" class="ml-auto shrink-0">{{ $user->role_id }}</x-badge>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

    </div>
</div>
@endsection
