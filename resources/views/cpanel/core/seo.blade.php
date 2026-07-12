<?php
/**
 * AgenticCms-Laravel
 * File: seo.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 25.10.2019
 */
?>

<div class="mt-6 border-t border-[var(--border)] pt-5">
    <h4 class="mb-4 flex items-center gap-2 text-sm font-semibold text-[var(--text)]">
        <svg class="h-4 w-4 text-[var(--text-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3" stroke-linecap="round"/></svg>
        @lang('cpanel/seo.seo_headline')
    </h4>

    <div class="field">
        <label for="meta_keywords" class="field-label">@lang('cpanel/seo.meta_keywords_headline')</label>
        <input type="text" id="meta_keywords" required class="form-control" name="meta_keywords" value="{{ old('meta_keywords', isset($entity) ? $entity->meta_keywords : null) }}">
        <div class="field-desc"><p>@lang('cpanel/seo.meta_keywords_text')</p></div>
    </div>

    <div class="field">
        <label for="meta_description" class="field-label">@lang('cpanel/seo.meta_description_headline')</label>
        <input type="text" id="meta_description" required class="form-control" name="meta_description" value="{{ old('meta_description', isset($entity) ? $entity->meta_description : null) }}">
        <div class="field-desc"><p>@lang('cpanel/seo.meta_description_text')</p></div>
    </div>

    {{-- Phase 7: optional per-entity canonical override + noindex --}}
    <div class="field">
        <label for="canonical_url" class="field-label">@lang('cpanel/seo.canonical_headline')</label>
        <input type="text" id="canonical_url" class="form-control" name="canonical_url" value="{{ old('canonical_url', isset($entity) ? ($entity->canonical_url ?? null) : null) }}" placeholder="https://...">
        <div class="field-desc"><p>@lang('cpanel/seo.canonical_text')</p></div>
    </div>

    <div class="field">
        <label for="meta_noindex" class="flex cursor-pointer items-center gap-2.5 text-sm text-[var(--text-muted)]">
            <input class="form-check-input" id="meta_noindex" name="meta_noindex" type="checkbox" value="1" {{ old('meta_noindex', isset($entity) ? ($entity->meta_noindex ?? false) : false) ? 'checked' : '' }}>
            @lang('cpanel/seo.noindex_headline')
        </label>
        <div class="field-desc"><p>@lang('cpanel/seo.noindex_text')</p></div>
    </div>
</div>
