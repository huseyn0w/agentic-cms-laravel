<?php
/**
 * AgenticCms-Laravel
 * File: comments_list.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 08.11.2019
 */
?>
@extends('cpanel.core.index')
@push('extrastyles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6">
            <h1 class="font-serif text-2xl text-fg">@lang('cpanel/comments.list_headline')</h1>
        </div>

        @include('cpanel.core.flash')
        @if (($update_message = Session::get('deleted')) !== null)
            <x-alert :variant="$update_message ? 'success' : 'error'" class="mb-4">{{ $update_message ? __('cpanel/comments.bulky_deleted_message') : __('cpanel/comments.bulky_deleted_error_message') }}</x-alert>
        @endif

        <div class="overflow-hidden rounded-lg border border-border bg-surface">
            <form method="POST" action="{{route('cpanel_comments_bulk_delete')}}"
                  x-data="{ selected: 0 }"
                  x-on:change="selected = $el.querySelectorAll('.comments-checkbox-input:checked').length">
                @csrf
                @method('DELETE')

                {{-- Bulk-action bar (DESIGN_SYSTEM §5). Retains the .select-cover hook. --}}
                <div class="select-cover flex flex-wrap items-center gap-3 border-b border-border bg-surface-2 px-5 py-3" aria-live="polite">
                    <span class="font-mono text-xs uppercase tracking-[0.08em] text-muted" x-text="selected > 0 ? selected + ' selected' : '@lang('cpanel/comments.bulk_action_label')'">@lang('cpanel/comments.bulk_action_label')</span>
                    <select id="inputState" name="comments_action" required class="h-9 rounded-sm border border-strong bg-surface px-3 text-sm text-fg focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-1">
                        <option selected="selected">@lang('cpanel/comments.bulk_action_label')</option>
                        <option value="delete">@lang('cpanel/comments.bulk_action_delete_label')</option>
                    </select>
                    <x-button type="submit" variant="secondary" size="sm">@lang('cpanel/comments.bulk_action_apply')</x-button>
                </div>

                <div class="overflow-x-auto">
                    <table class="data-table users-table w-full text-left text-sm">
                        <thead class="bg-surface-2">
                            <tr>
                                <th class="w-10 px-4 py-3"><input class="form-check-input" id="selectAll" name="allcomments" type="checkbox" aria-label="Select all"></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/comments.table_title')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/comments.table_name')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/comments.table_author')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/comments.table_publish_date')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/comments.table_status')</x-eyebrow></th>
                            </tr>
                        </thead>
                        <tbody>
                        @php($comments_count = 0)
                        @forelse($comments_list as $comment)
                            @php($comments_count++)
                            <tr class="border-b border-border transition-colors last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 align-middle"><input class="form-check-input comments-checkbox-input" id="comment_{{$comment->id}}" name="comments[]" type="checkbox" value="{{$comment->id}}" aria-label="Select comment"></td>
                                <td class="px-4 py-3 align-middle text-muted">{{$comment->post->title}}</td>
                                <td class="px-4 py-3 align-middle">
                                    <span class="text-fg">{{$comment->comment}}</span>
                                    <span class="user_actions">
                                        @if (Auth::user()->can('manage_comments', 'App\Http\Models\UserRoles'))
                                            <button type="button" class="delete_comment">@lang('cpanel/comments.delete')</button>
                                            @if($comment->status !== 1)
                                                <button type="button" class="approve_comment">@lang('cpanel/comments.approve')</button>
                                            @else
                                                <button type="button" class="unapprove_comment">@lang('cpanel/comments.unapprove')</button>
                                            @endif
                                            <input type="hidden" class="action_comment_id" value="{{$comment->id}}" name="deleted_comment_id">
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle text-muted">{{$comment->user->username}}</td>
                                <td class="whitespace-nowrap px-4 py-3 align-middle text-muted">{{$comment->created_at->format('d.m.Y')}}</td>
                                <td class="px-4 py-3 align-middle">
                                    @if($comment->status == 1)
                                        <x-badge variant="success">@lang('cpanel/comments.status_approved')</x-badge>
                                    @else
                                        <x-badge variant="warning">@lang('cpanel/comments.status_pending')</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty-state :headline="__('cpanel/comments.not_found')" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
            <div class="border-t border-border px-5 py-4">
                <x-pagination :paginator="$comments_list" />
            </div>
        </div>
    </div>
@endsection

@push('finalscripts')
    <script>
        var  _token = '{{ csrf_token() }}',
            delete_confirmation = '@lang('cpanel/comments.js_delete_confirmation')',
            approve_confirmation = '@lang('cpanel/comments.js_approve_confirmation')',
            unapprove_confirmation = '@lang('cpanel/comments.js_unapprove_confirmation')',
            approve_success = '@lang('cpanel/comments.js_approve')',
            delete_success = '@lang('cpanel/comments.js_delete')',
            unapprove_success = '@lang('cpanel/comments.js_unapprove')',
            error_message = '@lang('cpanel/comments.js_error')';
    </script>
    <script src="{{asset('admin')}}/js/comments.js"></script>
@endpush
