<?php
/**
 * AgenticCms-Laravel
 * File: flash.blade.php — DESIGN_SYSTEM §5 Phase 6
 * Reusable validation-error block for admin forms.
 * Pull in with @include('cpanel.core.flash').
 * Uses <x-alert variant="error"> — token-driven, flips in dark mode.
 */
?>
@if ($errors->any())
    <x-alert variant="error" class="mb-4">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
