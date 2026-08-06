<?php

/**
 * Admin two-factor authentication: the profile enrollment panel + the login
 * challenge page. Consumed via the flattened `cpanel/twofactor.*` i18n keys.
 */

return [

    // Profile enrollment panel
    'headline' => 'Two-factor authentication',
    'subtitle' => 'Protect your account with a time-based code from an authenticator app.',
    'enable' => 'Enable 2FA',
    'confirm' => 'Confirm',
    'confirm_label' => 'Enter the 6-digit code',
    'manual_key' => 'Manual key',
    'active' => '2FA is active on your account.',
    'disable' => 'Disable 2FA',
    'password_label' => 'Current password',
    'recovery_headline' => 'Recovery codes — store them safely',
    'invalid_code' => 'The code is invalid.',
    'disabled' => 'Two-factor authentication disabled.',
    'enrollment_required' => 'Set up two-factor authentication to continue.',

    // Login challenge page
    'challenge_headline' => 'Two-factor authentication',
    'challenge_subtitle' => 'Enter the code from your authenticator app, or a recovery code.',
    'challenge_recovery_hint' => 'Lost your device? Enter one of your recovery codes instead.',
    'challenge_submit' => 'Verify',

];
