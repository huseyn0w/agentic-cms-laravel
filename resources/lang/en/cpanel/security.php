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

    // Password policy
    'password_policy_headline' => 'Password policy',
    'password_policy_subtitle' => 'Applied when accounts set or reset a password.',
    'password_min_length' => 'Minimum length',
    'password_mixed_case' => 'Require upper and lower case',
    'password_numbers' => 'Require a number',
    'password_symbols' => 'Require a symbol',
    'password_hibp' => 'Reject passwords found in known data breaches',
    'password_history' => 'Block reuse of the last N passwords (0 = off)',
    'password_reused' => 'This password matches a recent one. Please choose a different password.',

    // Security headers
    'headers_headline' => 'Security headers',
    'headers_subtitle' => 'Baseline hardening headers are always sent. HSTS and CSP are opt-in.',
    'hsts_enabled' => 'Send HSTS (HTTPS only)',
    'hsts_max_age' => 'HSTS max-age (seconds)',
    'csp' => 'Content-Security-Policy (advanced — leave blank to disable)',
    'csp_report_only' => 'Report-only (do not enforce CSP, only log)',

    // Admin IP allowlist
    'ip_allowlist_headline' => 'Admin IP allowlist',
    'ip_allowlist_subtitle' => 'One IP or CIDR per line. Empty means no restriction. Only these addresses may reach the admin panel.',
    'ip_current' => 'Your current IP',
    'ip_forbidden' => 'Your IP address is not allowed to access the admin panel.',

    // Site lockdown
    'lockdown_headline' => 'Site lockdown',
    'lockdown_subtitle' => 'Take the public site private. Visitors who are not signed in are sent to the login page; the admin panel and login stay reachable.',
    'lockdown_enabled' => 'Require sign-in to view the public site',

    // Event labels
    'action_login' => 'Sign in',
    'action_login_failed' => 'Failed sign in',
    'action_logout' => 'Sign out',
    'action_lockout' => 'Lockout',
    'action_2fa_enabled' => '2FA enabled',
    'action_2fa_disabled' => '2FA disabled',
    'action_2fa_failed' => 'Failed 2FA',
    'action_session_revoked' => 'Session revoked',

    // Table columns
    'col_when' => 'When',
    'col_action' => 'Event',
    'col_actor' => 'Account',
    'col_ip' => 'IP',
    'col_detail' => 'Detail',

];
