<?php
/**
 * AgenticCms-Laravel
 * File: footer.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 21.07.2019
 */
?>
<footer class="border-t border-border px-4 py-5 sm:px-6 lg:px-8">
    <p class="text-center text-xs text-muted">
        &copy; {{ now()->year }}
        @lang('cpanel/nav/bottom.made')
        <a href="https://www.linkedin.com/in/huseyn0w/" class="font-medium text-primary hover:opacity-80">Huseyn0w</a>
        <span class="text-subtle">&middot;</span>
        @lang('cpanel/nav/bottom.developed_by')
        <a href="https://elman.group" target="_blank" rel="noopener" class="font-medium text-primary hover:opacity-80">Elman Group</a>
    </p>
</footer>
@include('cpanel.core.footer-scripts')
