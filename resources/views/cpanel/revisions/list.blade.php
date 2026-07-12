<?php
/**
 * AgenticCms-Laravel
 * File: list.blade.php — revision history for a post/page translation.
 * Shared by posts and pages; the owning controller passes the route names.
 */
?>

@extends('cpanel.core.index')

@section('content')
    <div class="mx-auto max-w-5xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-2xl text-fg">@lang('cpanel/revisions.headline')</h1>
                <p class="mt-1 text-sm text-muted">@lang('cpanel/revisions.subtitle')</p>
            </div>
            <x-button as="a" :href="route($edit_route, ['id' => $entity_id, 'lang' => $lang])" variant="ghost" size="sm">
                @lang('cpanel/revisions.back_to_editor')
            </x-button>
        </div>

        @include('cpanel.core.flash')

        <div class="overflow-hidden rounded-lg border border-border bg-surface">
            <div class="overflow-x-auto">
                <table class="data-table users-table w-full text-left text-sm">
                    <thead class="bg-surface-2">
                        <tr>
                            <th class="w-16 px-4 py-3"><x-eyebrow>@lang('cpanel/revisions.table_revision')</x-eyebrow></th>
                            <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/revisions.table_author')</x-eyebrow></th>
                            <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/revisions.table_date')</x-eyebrow></th>
                            <th class="px-4 py-3 text-right"><x-eyebrow>@lang('cpanel/revisions.table_actions')</x-eyebrow></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($revisions as $revision)
                        <tr class="border-b border-border transition-colors last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3 align-middle font-medium text-fg">v{{ $revisions->total() - ($revisions->firstItem() - 1) - $loop->index }}</td>
                            <td class="px-4 py-3 align-middle text-muted">{{ optional($revision->author)->username ?? __('cpanel/revisions.unknown_author') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-middle text-muted">{{ \Carbon\Carbon::parse($revision->created_at)->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3 align-middle">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route($diff_route, ['id' => $entity_id, 'revision' => $revision->id, 'lang' => $lang]) }}" class="text-sm font-medium text-primary hover:text-primary-hover">
                                        @lang('cpanel/revisions.compare')
                                    </a>
                                    <form action="{{ route($restore_route, ['id' => $entity_id, 'revision' => $revision->id, 'lang' => $lang]) }}" method="POST" onsubmit="return confirm('{{ __('cpanel/revisions.restore_confirm') }}');">
                                        @csrf
                                        <x-button type="submit" variant="ghost" size="sm">@lang('cpanel/revisions.restore')</x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty-state :headline="__('cpanel/revisions.empty')" /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($revisions->hasPages())
                <div class="border-t border-border px-5 py-4">
                    <x-pagination :paginator="$revisions" />
                </div>
            @endif
        </div>
    </div>
@endsection
