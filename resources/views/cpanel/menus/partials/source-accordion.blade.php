<?php
/**
 * Cmstack-Laravel — menu builder source accordion (Phase 7c redesign).
 * Shared by new_menu / edit_menu.
 * DESIGN_SYSTEM §5: token-driven containers with card + eyebrow + field structure.
 *
 * Preserves ALL JS hooks that menu.js + admin.js depend on:
 *   - Select IDs: #pages_list, #posts_list, #categories_list
 *   - Select classes: .multiple_list .menu_item
 *   - Input IDs: #link_label, #link_url
 *   - Input classes: .menu_item
 *   - data-type attributes on selects (data-type="pages/posts/categories")
 *   - data-toggle="collapse" + data-target="#*_tab" on accordion triggers
 *   - .collapse on collapsible panels (handled by resources/js/admin.js)
 *   - Accordion wrapper id="menusAccordion"
 */
?>
<div class="accordion mt-3 space-y-1" id="menusAccordion">

    {{-- Pages --}}
    <div class="overflow-hidden rounded-md border border-border bg-surface">
        <div class="flex" id="headingOne">
            <button class="flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium text-fg hover:bg-surface-2 transition-colors"
                    type="button" data-toggle="collapse" data-target="#pages_tab" aria-expanded="false" aria-controls="pages_tab">
                <span class="flex items-center gap-2">
                    <x-icon name="chevron-right" class="h-3.5 w-3.5 text-muted transition-transform" />
                    @lang('cpanel/menus.pages')
                </span>
            </button>
        </div>
        <div id="pages_tab" class="collapse border-t border-border">
            <div class="p-3">
                <select name="pages" multiple class="form-control multiple_list menu_item w-full" id="pages_list" data-type="pages" size="5">
                    @forelse($terms_list['pages'] as $page)
                        <option value="{{$page->slug}}">{{$page->title}}</option>
                    @empty
                        <option disabled>@lang('cpanel/menus.no_pages')</option>
                    @endforelse
                </select>
            </div>
        </div>
    </div>

    {{-- Posts --}}
    <div class="overflow-hidden rounded-md border border-border bg-surface">
        <div class="flex" id="headingTwo">
            <button class="flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium text-fg hover:bg-surface-2 transition-colors"
                    type="button" data-toggle="collapse" data-target="#posts_tab" aria-expanded="false" aria-controls="posts_tab">
                <span class="flex items-center gap-2">
                    <x-icon name="chevron-right" class="h-3.5 w-3.5 text-muted transition-transform" />
                    @lang('cpanel/menus.posts')
                </span>
            </button>
        </div>
        <div id="posts_tab" class="collapse border-t border-border">
            <div class="p-3">
                <select name="posts" multiple class="form-control multiple_list menu_item w-full" id="posts_list" data-type="posts" size="5">
                    @forelse($terms_list['posts'] as $post)
                        <option value="{{$post->slug}}">{{$post->title}}</option>
                    @empty
                        <option disabled>@lang('cpanel/menus.no_posts')</option>
                    @endforelse
                </select>
            </div>
        </div>
    </div>

    {{-- Categories --}}
    <div class="overflow-hidden rounded-md border border-border bg-surface">
        <div class="flex" id="headingThree">
            <button class="flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium text-fg hover:bg-surface-2 transition-colors"
                    type="button" data-toggle="collapse" data-target="#categories_tab" aria-expanded="false" aria-controls="categories_tab">
                <span class="flex items-center gap-2">
                    <x-icon name="chevron-right" class="h-3.5 w-3.5 text-muted transition-transform" />
                    @lang('cpanel/menus.categories')
                </span>
            </button>
        </div>
        <div id="categories_tab" class="collapse border-t border-border">
            <div class="p-3">
                <select name="category" multiple class="form-control multiple_list menu_item w-full" id="categories_list" data-type="categories" size="5">
                    @forelse($terms_list['categories'] as $category)
                        <option value="{{$category->slug}}">{{$category->title}}</option>
                    @empty
                        <option disabled>@lang('cpanel/menus.no_categories')</option>
                    @endforelse
                </select>
            </div>
        </div>
    </div>

    {{-- Custom link --}}
    <div class="overflow-hidden rounded-md border border-border bg-surface">
        <div class="flex" id="headingFour">
            <button class="flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium text-fg hover:bg-surface-2 transition-colors"
                    type="button" data-toggle="collapse" data-target="#custom_link_tap" aria-expanded="false" aria-controls="custom_link_tap">
                <span class="flex items-center gap-2">
                    <x-icon name="chevron-right" class="h-3.5 w-3.5 text-muted transition-transform" />
                    @lang('cpanel/menus.custom_link')
                </span>
            </button>
        </div>
        <div id="custom_link_tap" class="collapse border-t border-border">
            <div class="space-y-3 p-3">
                <x-field label="{{ __('cpanel/menus.custom_link_label') }}" name="link_label">
                    <input type="text" id="link_label" class="form-control menu_item w-full" name="link_label">
                </x-field>
                <x-field label="{{ __('cpanel/menus.custom_link_url') }}" name="link_url">
                    <input type="text" id="link_url" class="form-control menu_item w-full" name="link_url">
                </x-field>
            </div>
        </div>
    </div>

</div>
