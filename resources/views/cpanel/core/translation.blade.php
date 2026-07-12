<?php
/**
 * AgenticCms-Laravel
 * File: translation.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 07.12.2019
 */
?>

@if(isset($translation_links) && is_array($translation_links) && !empty($translation_links))
    <div class="field">
        <label class="field-label">@lang('cpanel/general.add_translation')</label>
        <div class="flex flex-wrap gap-2">
            @foreach($translation_links as $title => $link)
                <a href="{{config('app.url').'/'.$link}}" target="_blank"
                   class="inline-flex items-center gap-1.5 rounded-full border border-border bg-surface px-3 py-1 text-xs font-medium text-muted transition hover:border-strong hover:text-primary">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
                    {{$title}}
                </a>
            @endforeach
        </div>
    </div>
@endif
