<?php
/**
 * AgenticCms-Laravel
 * File: edit_menu.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 06.09.2019
 * Redesigned: DESIGN_SYSTEM §5 — x-card / x-field / x-button containers; token-driven.
 * Preserves:
 *   - form id="add_menu_form", @method("PUT"), field names title + slug + content (#menuContent)
 *   - .menu-box / .menu-list / .sortable / .ui-sortable / $existing_menu render_menu() output
 *   - .add_menu_item button type="button" — JS hook in menu.js
 *   - .create_menu on submit button
 *   - session flash + @include('cpanel.menus.partials.source-accordion')
 * DESIGN_SYSTEM §5/§7: the drag-only jQuery UI nestedSortable (loaded from a
 * googleapis CDN) is replaced by self-hosted menu-reorder.js — keyboard move
 * up/down + arrow keys with aria-live announcements, native HTML5 drag as a
 * progressive enhancement. No CDN scripts remain on this screen.
 */
?>

@extends('cpanel.core.index')

@php
    $menu_params = [
        'menu_type' => "list",
        'menu_class' => "menu-list sortable ui-sortable",
    ];
    $existing_menu = render_menu(json_decode($entity->content), $menu_params);
@endphp

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/menus.edit_menu_headline')</h1>
        </div>

        @include('cpanel.core.flash')
        @if (($update_message = Session::get('message')) !== null)
            <x-alert variant="{{ $update_message ? 'success' : 'error' }}" class="mb-4">
                {{ $update_message ? __('cpanel/menus.menu_updated') : __('cpanel/menus.menu_error') }}
            </x-alert>
        @endif

        <form action="{{ route('cpanel_update_menu',['id' => $entity->id]) }}" id="add_menu_form" method="POST">
            @csrf
            @method("PUT")
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

                {{-- Source panel --}}
                <div class="lg:col-span-1">
                    <x-card>
                        <x-slot:header>
                            <h2 class="text-sm font-semibold text-fg">@lang('cpanel/menus.edit_menu_headline')</h2>
                        </x-slot:header>

                        <div class="space-y-4">
                            @include('cpanel.core.translation')

                            <x-field label="{{ __('cpanel/menus.menu_name') }}" name="title" :required="true">
                                <input type="text" id="menu_title" required class="form-control w-full" name="title" value="{{ old('title',$entity->title) }}">
                            </x-field>

                            <x-field label="{{ __('cpanel/menus.menu_slug') }}" name="slug" :required="true">
                                <input type="text" id="cpanel_slug" required class="form-control w-full" name="slug" value="{{ old('slug', $entity->slug) }}">
                            </x-field>

                            @include('cpanel.menus.partials.source-accordion')

                            <x-button type="button" variant="outline" class="add_menu_item w-full">
                                @lang('cpanel/menus.add_to_menu')
                            </x-button>
                        </div>
                    </x-card>
                </div>

                {{-- Builder canvas --}}
                <div class="lg:col-span-2">
                    <x-card>
                        <x-slot:header>
                            <h2 class="text-sm font-semibold text-fg">@lang('cpanel/menus.list_headline')</h2>
                        </x-slot:header>

                        {{-- .menu-box preserves JS hooks; existing_menu rendered with .menu-list/.sortable classes.
                             Reordering is keyboard-accessible via menu-reorder.js. --}}
                        <div class="menu-box min-h-[200px] rounded-md border border-dashed border-border bg-surface-2 p-3"
                             role="list"
                             aria-label="{{ __('cpanel/menus.list_headline') }}">
                            @if($existing_menu)
                                {!! $existing_menu !!}
                            @else
                                <ul class="menu-list sortable" id="sortable"></ul>
                            @endif
                        </div>
                        {{-- Reorder announcements (DESIGN_SYSTEM §5). --}}
                        <div id="menu-reorder-live" class="sr-only" role="status" aria-live="polite"></div>
                        <input type="hidden" name="content" id="menuContent">

                        <x-slot:footer>
                            <div class="flex justify-end">
                                <x-button type="submit" variant="primary" class="create_menu">
                                    @lang('cpanel/menus.update_menu')
                                </x-button>
                            </div>
                        </x-slot:footer>
                    </x-card>
                </div>

            </div>
        </form>
    </div>
@endsection

@push('finalscripts')
    <script>
        window.menuReorderStrings = {
            reorder: @json(__('cpanel/menus.reorder')),
            move_up: @json(__('cpanel/menus.move_up')),
            move_down: @json(__('cpanel/menus.move_down')),
            moved: @json(__('cpanel/menus.reorder_moved')),
            of: @json(__('cpanel/menus.reorder_of')),
            at_top: @json(__('cpanel/menus.reorder_at_top')),
            at_bottom: @json(__('cpanel/menus.reorder_at_bottom')),
            item: @json(__('cpanel/menus.reorder_item')),
        };
    </script>
    <script src="{{asset('admin')}}/js/menu.js"></script>
    <script src="{{asset('admin')}}/js/menu-reorder.js"></script>
@endpush
