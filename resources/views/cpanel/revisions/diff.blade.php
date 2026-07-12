<?php
/**
 * AgenticCms-Laravel
 * File: diff.blade.php — per-field comparison of one revision vs the live row.
 * Shared by posts and pages; values are escaped (snapshots may contain HTML).
 * Redesigned: DESIGN_SYSTEM §5 — x-card, x-button, x-badge, token colors.
 * Preserves:
 *   - $fields iteration with $field['field'], $field['changed'], $field['old'], $field['current']
 *   - restore action form POST to $restore_route with revision + entity_id + lang params
 *   - back link via $list_route
 */
?>

@extends('cpanel.core.index')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-fg">@lang('cpanel/revisions.diff_headline')</h1>
                <p class="mt-1 text-sm text-muted">@lang('cpanel/revisions.diff_subtitle')</p>
                <p class="mt-1 font-mono text-xs text-subtle">{{ \Carbon\Carbon::parse($revision->created_at)->format('d.m.Y H:i') }}</p>
            </div>
            <x-button variant="ghost" href="{{ route($list_route, ['id' => $entity_id, 'lang' => $lang]) }}" as="a">
                <x-icon name="arrow-left" width="16" height="16" />
                @lang('cpanel/revisions.back_to_list')
            </x-button>
        </div>

        <x-card class="!p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table w-full">
                    <thead>
                        <tr>
                            <th class="w-40 px-4 py-3 text-left">
                                <x-eyebrow>@lang('cpanel/revisions.diff_field')</x-eyebrow>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <x-eyebrow>@lang('cpanel/revisions.diff_old')</x-eyebrow>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <x-eyebrow>@lang('cpanel/revisions.diff_current')</x-eyebrow>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($fields as $field)
                        <tr class="{{ $field['changed'] ? 'bg-warning-bg' : '' }}">
                            <td class="align-top px-4 py-3">
                                <span class="font-medium text-fg text-sm">{{ $field['field'] }}</span>
                                <div class="mt-1">
                                    @if($field['changed'])
                                        <x-badge variant="warning">@lang('cpanel/revisions.diff_changed')</x-badge>
                                    @else
                                        <x-badge variant="neutral">@lang('cpanel/revisions.diff_unchanged')</x-badge>
                                    @endif
                                </div>
                            </td>
                            <td class="align-top px-4 py-3">
                                <div class="max-h-64 overflow-auto whitespace-pre-wrap break-words text-sm text-muted font-mono">{{ $field['old'] }}</div>
                            </td>
                            <td class="align-top px-4 py-3">
                                <div class="max-h-64 overflow-auto whitespace-pre-wrap break-words text-sm text-fg font-mono">{{ $field['current'] }}</div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end border-t border-border px-5 py-4">
                <form action="{{ route($restore_route, ['id' => $entity_id, 'revision' => $revision->id, 'lang' => $lang]) }}"
                      method="POST"
                      onsubmit="return confirm('{{ __('cpanel/revisions.restore_confirm') }}');">
                    @csrf
                    <x-button type="submit" variant="primary">
                        @lang('cpanel/revisions.restore')
                    </x-button>
                </form>
            </div>
        </x-card>
    </div>
@endsection
