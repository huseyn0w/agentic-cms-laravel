<?php

/**
 * Active browser sessions panel on the admin profile. Consumed via the
 * flattened `cpanel/sessions.*` i18n keys by
 * resources/js/components/admin/ActiveSessionsPanel.tsx.
 */

return [
    'headline' => 'Active sessions',
    'subtitle' => 'Browsers currently signed in to your account. Revoke any you do not recognise.',
    'current' => 'This device',
    'col_device' => 'Device',
    'col_ip' => 'IP',
    'col_last_active' => 'Last active',
    'revoke' => 'Revoke',
    'logout_others' => 'Log out all other sessions',
    'password_prompt' => 'Confirm your password to log out other sessions',
    'password_label' => 'Current password',
    'revoked' => 'Session revoked.',
    'others_logged_out' => 'All other sessions have been logged out.',
    'empty' => 'No other active sessions.',
];
