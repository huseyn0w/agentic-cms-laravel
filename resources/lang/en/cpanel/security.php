<?php

/**
 * Admin Security screen: the login-protection settings form and the
 * authentication audit log. Consumed via the flattened `cpanel/security.*`
 * i18n keys by resources/js/pages/cpanel/security/Index.tsx.
 */

return [

    // Login-protection form
    'protection_headline' => 'Login protection',
    'protection_subtitle' => 'Rate-limit failed sign-in attempts by email and IP.',
    'save_button' => 'Save',
    'settings_saved' => 'Security settings saved.',
    'throttle_enabled' => 'Throttle failed sign-in attempts',
    'max_attempts' => 'Max attempts before lockout',
    'decay_minutes' => 'Lockout duration (minutes)',
    'block_enabled' => 'Auto-block repeat offenders for longer',
    'block_threshold' => 'Attempts before auto-block',
    'block_minutes' => 'Auto-block duration (minutes)',

    // Activity log
    'activity_headline' => 'Activity log',
    'audit_subtitle' => 'Authentication activity — sign-ins, failed attempts and lockouts.',
    'filter_all' => 'All',
    'empty' => 'No activity recorded yet',

    // Login protection — 2FA
    'require_2fa' => 'Require 2FA for everyone with admin access',

    // Event labels
    'action_login' => 'Sign in',
    'action_login_failed' => 'Failed sign in',
    'action_logout' => 'Sign out',
    'action_lockout' => 'Lockout',
    'action_2fa_enabled' => '2FA enabled',
    'action_2fa_disabled' => '2FA disabled',
    'action_2fa_failed' => 'Failed 2FA',

    // Table columns
    'col_when' => 'When',
    'col_action' => 'Event',
    'col_actor' => 'Account',
    'col_ip' => 'IP',
    'col_detail' => 'Detail',

];
