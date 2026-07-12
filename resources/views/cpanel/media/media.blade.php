<?php
/**
 * AgenticCms-Laravel
 * File: media.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 23.08.2019
 * Redesigned: DESIGN_SYSTEM §5 "File upload / dropzone" — dashed border-strong,
 *             surface-2 fill, icon + prompt per spec, plus a REAL native
 *             <input type="file"> fallback + drag-over token states (border→--primary).
 * Preserves:  The Laravel FileManager iframe at /filemanager (primary file management UI).
 *             The stand-alone-button.js script (drives LFM picker hooks elsewhere in admin).
 *             The <meta name="csrf-token"> required by dropzone/LFM JS.
 *
 * NOTE (iframe limitation): the media LIBRARY (browse/rename/crop/delete) is an
 * entire third-party LFM UI that only lives inside the iframe, so those affordances
 * can't be re-hosted natively here. The dropzone below is the first-party upload
 * surface: it posts real files to LFM's own upload endpoint and reloads the iframe
 * on success, giving a keyboard/AT-operable upload path independent of the iframe.
 */
?>

@extends('cpanel.core.index')
@push('extrastyles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="mx-auto max-w-7xl"
         x-data="mediaDropzone({
            endpoint: '/filemanager/upload?type=Files&working_dir=%2F',
            strings: {
                uploading: @json(__('cpanel/media.dropzone_uploading')),
                success: @json(__('cpanel/media.dropzone_success')),
                error: @json(__('cpanel/media.dropzone_error')),
            }
         })">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">@lang('cpanel/media.headline')</h1>
        </div>

        {{-- §5 File upload / dropzone: dashed 2px border-strong, surface-2 fill,
             centered icon + prompt + caption. Drag-over → border --primary + tint.
             Always paired with a real native <input type="file"> (keyboard/AT path). --}}
        <div
            class="group relative mb-6 rounded-lg border-2 border-dashed bg-surface-2 px-6 py-10 text-center transition-colors duration-[var(--dur-base)]"
            :class="dragging ? 'border-primary bg-primary/5' : 'border-strong'"
            @dragover.prevent="dragging = true"
            @dragenter.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop($event)"
            data-testid="media-dropzone"
        >
            <label class="flex cursor-pointer flex-col items-center gap-3">
                <x-icon name="upload" class="text-muted shrink-0" width="28" height="28" />
                <span class="text-sm font-medium text-fg">@lang('cpanel/media.dropzone_prompt')</span>
                <span class="text-xs text-subtle">@lang('cpanel/media.dropzone_hint')</span>

                {{-- REAL native file input — the keyboard/AT upload path (§5). --}}
                <input
                    type="file"
                    name="upload[]"
                    multiple
                    class="sr-only"
                    aria-label="{{ __('cpanel/media.dropzone_input_label') }}"
                    @change="onSelect($event)"
                    data-testid="media-file-input"
                >
                <span class="mt-1 inline-flex h-9 items-center rounded-md bg-surface-2 px-4 text-sm font-medium text-fg ring-1 ring-inset ring-strong transition-colors group-hover:bg-border">
                    @lang('cpanel/media.dropzone_button')
                </span>
            </label>

            {{-- Upload status (aria-live for AT feedback). --}}
            <p
                class="mt-4 text-sm"
                :class="status === 'error' ? 'text-error' : 'text-muted'"
                x-show="message"
                x-text="message"
                role="status"
                aria-live="polite"
            ></p>
        </div>

        {{-- The iframe IS the media library (LFM runs inside it) — browse/manage. --}}
        <div class="overflow-hidden rounded-lg border border-border bg-surface">
            <div class="flex items-center gap-3 border-b border-border bg-surface px-5 py-3">
                <x-icon name="upload" class="text-muted shrink-0" width="18" height="18" />
                <span class="text-sm text-muted">@lang('cpanel/media.headline')</span>
            </div>

            {{-- Laravel FileManager runs inside this iframe --}}
            <iframe
                x-ref="lfm"
                src="/filemanager"
                class="block w-full border-0"
                style="height: 70vh; min-height: 500px;"
                title="{{ __('cpanel/media.headline') }}"
            ></iframe>
        </div>
    </div>
@endsection

@push('extrascripts')
    <script src="{{base_path('vendor')}}/laravel-filemanager/js/stand-alone-button.js"></script>
@endpush

@push('finalscripts')
    <script>
        // Progressive-enhancement dropzone (DESIGN_SYSTEM §5). Posts real files to
        // LFM's own upload endpoint, then reloads the library iframe on success.
        function mediaDropzone(config) {
            return {
                dragging: false,
                status: '',
                message: '',
                token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                onDrop(e) {
                    this.dragging = false;
                    if (e.dataTransfer && e.dataTransfer.files.length) {
                        this.upload(e.dataTransfer.files);
                    }
                },
                onSelect(e) {
                    if (e.target.files && e.target.files.length) {
                        this.upload(e.target.files);
                    }
                },
                upload(files) {
                    const form = new FormData();
                    Array.prototype.forEach.call(files, (f) => form.append('upload[]', f));
                    form.append('_token', this.token);

                    this.status = 'uploading';
                    this.message = config.strings.uploading;

                    fetch(config.endpoint, {
                        method: 'POST',
                        body: form,
                        headers: { 'X-CSRF-TOKEN': this.token, 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    })
                        .then((res) => res.text().then((text) => ({ ok: res.ok, text })))
                        .then(({ ok, text }) => {
                            // LFM returns "OK" (or a JSON payload) on success, an error
                            // string otherwise. Treat a non-2xx or an error body as failure.
                            const failed = !ok || /error/i.test(text);
                            if (failed) {
                                this.status = 'error';
                                this.message = config.strings.error;
                                return;
                            }
                            this.status = 'success';
                            this.message = config.strings.success;
                            const iframe = this.$refs.lfm;
                            if (iframe) iframe.contentWindow.location.reload();
                        })
                        .catch(() => {
                            this.status = 'error';
                            this.message = config.strings.error;
                        });
                },
            };
        }
    </script>
@endpush
