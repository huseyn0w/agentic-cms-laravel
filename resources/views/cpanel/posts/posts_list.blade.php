<?php
/**
 * Cmstack-Laravel
 * File: posts_list.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 01.09.2019
 */
?>

@extends('cpanel.core.index')
@push('extrastyles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@php
$route_name = Route::current()->getName();
$is_trash = $route_name == "cpanel_trashed_posts_list";
@endphp

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-serif text-2xl text-fg">@lang('cpanel/posts.general_posts')</h1>
            <x-button as="a" :href="route('cpanel_add_new_post')" variant="primary" size="sm">
                @lang('cpanel/posts.add_new_post')
            </x-button>
        </div>

        @include('cpanel.core.flash')
        @if (Session::get('post_added'))
            <x-alert variant="success" class="mb-4">@lang('cpanel/posts.post_added')</x-alert>
        @endif
        @if (($update_message = Session::get('deleted')) !== null)
            <x-alert :variant="$update_message ? 'success' : 'error'" class="mb-4">{{ $update_message ? __('cpanel/posts.bulky_deleted_message') : __('cpanel/posts.bulky_error_message') }}</x-alert>
        @endif
        @if (($update_message = Session::get('restored')) !== null)
            <x-alert :variant="$update_message ? 'success' : 'error'" class="mb-4">{{ $update_message ? __('cpanel/posts.bulky_restored_message') : __('cpanel/posts.bulky_error_message') }}</x-alert>
        @endif
        @if (($update_message = Session::get('destroyed')) !== null)
            <x-alert :variant="$update_message ? 'success' : 'error'" class="mb-4">{{ $update_message ? __('cpanel/posts.bulky_destroyed_message') : __('cpanel/posts.bulky_error_message') }}</x-alert>
        @endif

        {{-- Status filter tabs: published vs trashed (DESIGN_SYSTEM §5 / Tabs) --}}
        <nav aria-label="@lang('cpanel/posts.general_posts')" class="mb-4 flex gap-1 border-b border-border">
            <a href="{{route('cpanel_posts_list')}}" @if(!$is_trash) aria-current="page" @endif class="-mb-px border-b-2 px-4 py-2.5 text-sm font-sans transition-colors duration-[var(--dur-fast)] {{ !$is_trash ? 'border-primary font-medium text-fg' : 'border-transparent text-muted hover:text-fg' }}">@lang('cpanel/posts.general_posts')</a>
            <a href="{{route('cpanel_trashed_posts_list')}}" @if($is_trash) aria-current="page" @endif class="-mb-px border-b-2 px-4 py-2.5 text-sm font-sans transition-colors duration-[var(--dur-fast)] {{ $is_trash ? 'border-primary font-medium text-fg' : 'border-transparent text-muted hover:text-fg' }}">@lang('cpanel/posts.trashed_posts')</a>
        </nav>

        <div class="overflow-hidden rounded-lg border border-border bg-surface">
            <form method="POST" action="{{ $is_trash ? route('cpanel_posts_bulk_action') : route('cpanel_posts_bulk_delete') }}"
                  x-ref="bulkForm"
                  x-data="{
                      selected: 0,
                      isTrash: {{ $is_trash ? 'true' : 'false' }},
                      confirmed: false,
                      pendingBody: '',
                      currentAction() {
                          return this.isTrash
                              ? (this.$refs.bulkAction ? this.$refs.bulkAction.value : '')
                              : 'delete';
                      },
                      onSubmit(e) {
                          if (this.confirmed) { this.confirmed = false; return; }
                          const action = this.currentAction();
                          // Only destructive actions require the confirm dialog;
                          // restore submits straight through.
                          if (action === 'delete' || action === 'destroy') {
                              e.preventDefault();
                              this.pendingBody = action === 'destroy'
                                  ? @json(__('cpanel/posts.bulk_confirm_destroy_body'))
                                  : @json(__('cpanel/posts.bulk_confirm_delete_body'));
                              window.dispatchEvent(new CustomEvent('open-modal', { detail: 'bulk-delete-posts' }));
                          }
                      },
                      proceed() {
                          window.dispatchEvent(new CustomEvent('close-modal', { detail: 'bulk-delete-posts' }));
                          this.confirmed = true;
                          this.$refs.bulkForm.submit();
                      },
                  }"
                  x-on:change="selected = $el.querySelectorAll('.posts-checkbox-input:checked').length"
                  x-on:submit="onSubmit($event)">
                @csrf
                @if($is_trash) @method('POST') @else @method('DELETE') @endif

                {{-- Bulk-action bar (DESIGN_SYSTEM §5). Retains the .select-cover hook. --}}
                <div class="select-cover flex flex-wrap items-center gap-3 border-b border-border bg-surface-2 px-5 py-3" aria-live="polite">
                    <span class="font-mono text-xs uppercase tracking-[0.08em] text-muted" x-text="selected > 0 ? selected + ' selected' : '@lang('cpanel/posts.bulk_action_label')'">@lang('cpanel/posts.bulk_action_label')</span>
                    <select id="inputState" name="posts_action" x-ref="bulkAction" required class="h-9 rounded-sm border border-strong bg-surface px-3 text-sm text-fg focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-1">
                        <option selected="selected">@lang('cpanel/posts.bulk_action_label')</option>
                        @if($is_trash)
                            <option value="destroy">@lang('cpanel/posts.bulk_action_destroy_label')</option>
                            <option value="restore">@lang('cpanel/posts.bulk_action_restore_label')</option>
                        @else
                            <option value="delete">@lang('cpanel/posts.bulk_action_delete_label')</option>
                        @endif
                    </select>
                    <x-button type="submit" variant="secondary" size="sm">@lang('cpanel/posts.bulk_action_apply_label')</x-button>
                </div>

                <div class="overflow-x-auto">
                    <table class="data-table users-table w-full text-left text-sm">
                        <thead class="bg-surface-2">
                            <tr>
                                <th class="w-10 px-4 py-3"><input class="form-check-input" id="selectAll" name="allposts" type="checkbox" aria-label="Select all"></th>
                                <th class="w-12 px-4 py-3"><x-eyebrow>№</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/posts.table_name')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/posts.table_author')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/posts.table_publish_date')</x-eyebrow></th>
                                <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/posts.table_status')</x-eyebrow></th>
                            </tr>
                        </thead>
                        <tbody>
                        @php($posts_count = 0)
                        @forelse($posts_list as $post)
                            @php($posts_count++)
                            <tr class="border-b border-border transition-colors last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 align-middle"><input class="form-check-input posts-checkbox-input" id="post_{{$post->id}}" name="posts[]" type="checkbox" value="{{$post->id}}" aria-label="Select post"></td>
                                <td class="px-4 py-3 align-middle text-subtle">{{$posts_count}}</td>
                                <td class="px-4 py-3 align-middle">
                                    <span class="font-medium text-fg">{{$post->title}}</span>
                                    <span class="user_actions">
                                        @if (Auth::user()->can('manage_posts', 'App\Http\Models\UserRoles'))
                                            <a href="{{route('cpanel_edit_post', ['id' => $post->id, 'lang' => get_current_lang()])}}" target="_blank">@lang('cpanel/posts.edit_post')</a>
                                            <input type="hidden" class="deleted_post_id" value="{{$post->id}}" name="deleted_post_id">
                                            @if(!$is_trash)
                                                <button type="button" class="delete_post">@lang('cpanel/posts.delete_post')</button>
                                            @else
                                                <button type="button" class="destroy_post">@lang('cpanel/posts.destroy_post')</button>
                                                <a href="{{route('cpanel_restore_post', $post->id)}}" class="restore_post">@lang('cpanel/posts.restore_post')</a>
                                            @endif
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle text-muted">{{$post->author->username}}</td>
                                <td class="whitespace-nowrap px-4 py-3 align-middle text-muted">{{Carbon\Carbon::parse($post->created_at)->format('d.m.Y')}}</td>
                                <td class="px-4 py-3 align-middle">
                                    @if($post->status == 1)
                                        <x-badge variant="success">@lang('cpanel/posts.status_published')</x-badge>
                                    @else
                                        <x-badge variant="neutral">@lang('cpanel/posts.status_private')</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty-state :headline="__('cpanel/posts.not_found')" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- DESIGN_SYSTEM §5: destructive bulk actions confirm via Modal
                     (destructive button + explicit consequence text) instead of
                     window.confirm(). Lives inside the form's Alpine scope so the
                     confirm button can call proceed() to submit. --}}
                <x-modal name="bulk-delete-posts" :title="__('cpanel/posts.bulk_confirm_title')">
                    <p class="text-sm text-muted" x-text="pendingBody"></p>

                    <x-slot:footer>
                        <x-button type="button" variant="ghost" x-on:click="$dispatch('close-modal', 'bulk-delete-posts')">
                            @lang('cpanel/posts.bulk_confirm_cancel')
                        </x-button>
                        <x-button type="button" variant="destructive" x-on:click="proceed()" data-testid="bulk-delete-confirm">
                            @lang('cpanel/posts.bulk_confirm_proceed')
                        </x-button>
                    </x-slot:footer>
                </x-modal>
            </form>
            <div class="border-t border-border px-5 py-4">
                <x-pagination :paginator="$posts_list" />
            </div>
        </div>
    </div>
@endsection

@push('finalscripts')
    <script>
        var delete_confirmation = '@lang('cpanel/posts.js_delete_confirmation')',
            destroy_confirmation = '@lang('cpanel/posts.js_destroy_confirmation')',
            delete_success = '@lang('cpanel/posts.js_delete_success')',
            destroy_success = '@lang('cpanel/posts.js_destroy_success')',
            error_message = '@lang('cpanel/posts.js_error')';
    </script>
    <script src="{{asset('admin')}}/js/post.js"></script>
@endpush
