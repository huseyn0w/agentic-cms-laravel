<?php
/**
 * AgenticCms-Laravel — edit-comment dialog.
 * Phase 5: restyled to DESIGN_SYSTEM §5. Keeps editCommentDialog Alpine
 * component and native <dialog> behavior unchanged.
 * x-ref="dialog", x-ref="field", x-ref="id" preserved for Alpine wiring.
 */
?>

<div
    x-data="editCommentDialog()"
    @open-edit-comment.window="show($event.detail)"
>
    <dialog
        x-ref="dialog"
        @close="open = false"
        @click.self="close()"
        class="m-auto w-[min(92vw,32rem)] rounded-lg border border-border bg-surface p-0 text-fg shadow-lift backdrop:bg-black/45 backdrop:backdrop-blur-[2px]"
    >
        <form action="{{ route('update_post_comment') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="flex items-center justify-between border-b border-border px-6 py-4">
                <h2 class="font-serif text-xl text-fg">@lang('default/modal.edit_comment')</h2>
                <button type="button" @click="close()" aria-label="Close"
                        class="inline-flex h-8 w-8 items-center justify-center rounded text-muted transition-colors hover:text-fg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                    <x-icon name="x" width="20" height="20" aria-hidden="true" />
                </button>
            </div>

            <div class="px-6 py-5">
                @if ($errors->any())
                    <div class="mb-4">
                        <x-alert variant="error">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    </div>
                @endif

                <x-field name="comment" label="{{ __('default/post.comment') }}" :error="$errors->first('comment')">
                    <textarea
                        x-ref="field"
                        id="comment"
                        name="comment"
                        rows="5"
                        required
                        placeholder="{{ __('default/post.comment') }}"
                        class="w-full resize-y bg-surface border border-border-strong rounded-sm px-3 py-2.5 text-fg placeholder:text-subtle focus:outline-none focus:border-ring focus:ring-2 focus:ring-ring/30 font-sans text-base"
                        @if($errors->has('comment')) aria-invalid="true" aria-describedby="comment-error" @endif
                    ></textarea>
                </x-field>
                <input type="hidden" x-ref="id" name="updated_comment_id" id="updated_comment_id" value="">
            </div>

            <div class="flex justify-end gap-3 border-t border-border px-6 py-4">
                <x-button type="submit" variant="primary">@lang('default/modal.update_comment_btn')</x-button>
            </div>
        </form>
    </dialog>
</div>
