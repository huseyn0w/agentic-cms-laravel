<?php
/**
 * AgenticCms-Laravel
 * File: footer-scripts.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 09.08.2019
 */
?>

{{-- jQuery is still required by the admin module scripts (custom-fields, menu
     builder, bulk-delete, LFM, TinyMCE init in agentic-cms-laravel.js). Bootstrap,
     Popper, Chartist, bootstrap-notify and the Light Bootstrap Dashboard theme
     scripts are no longer loaded — their behaviour is replaced by the Tailwind
     admin bundle (resources/js/admin.js). --}}
<script src="{{asset('admin')}}/js/core/jquery.3.2.1.min.js" type="text/javascript"></script>

{{-- Per-view third-party deps (TinyMCE CDN, datepicker, jquery-ui, LFM,
     nestedSortable) load here — BEFORE agentic-cms-laravel.js so its tinymce.init() /
     datepicker() calls find them, preserving the original load order. --}}
@stack('extrascripts')

{{-- agentic-cms-laravel.js defines url_slug(), showNotification() (now backed by the
     Tailwind toast via the $.notify shim) and the TinyMCE/datepicker init. --}}
<script src="{{asset('admin')}}/js/agentic-cms-laravel.js"></script>

@stack('finalscripts')
</body>
</html>
