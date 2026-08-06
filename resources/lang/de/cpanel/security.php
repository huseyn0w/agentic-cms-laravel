<?php

/**
 * Admin Security screen (German). See the English file for structure.
 */

return [

    // Login-protection form
    'protection_headline' => 'Anmeldeschutz',
    'protection_subtitle' => 'Fehlgeschlagene Anmeldeversuche nach E-Mail und IP begrenzen.',
    'save_button' => 'Speichern',
    'settings_saved' => 'Sicherheitseinstellungen gespeichert.',
    'throttle_enabled' => 'Fehlgeschlagene Anmeldeversuche drosseln',
    'max_attempts' => 'Maximale Versuche vor Sperrung',
    'decay_minutes' => 'Sperrdauer (Minuten)',
    'block_enabled' => 'Wiederholungstäter länger automatisch sperren',
    'block_threshold' => 'Versuche vor automatischer Sperre',
    'block_minutes' => 'Dauer der automatischen Sperre (Minuten)',

    // Activity log
    'activity_headline' => 'Aktivitätsprotokoll',
    'audit_subtitle' => 'Anmeldeaktivität — Anmeldungen, fehlgeschlagene Versuche und Sperrungen.',
    'filter_all' => 'Alle',
    'empty' => 'Noch keine Aktivität aufgezeichnet',

    // Login protection — 2FA
    'require_2fa' => '2FA für alle mit Admin-Zugang erforderlich',

    // Password policy
    'password_policy_headline' => 'Passwortrichtlinie',
    'password_policy_subtitle' => 'Gilt, wenn Konten ein Passwort festlegen oder zurücksetzen.',
    'password_min_length' => 'Mindestlänge',
    'password_mixed_case' => 'Groß- und Kleinbuchstaben verlangen',
    'password_numbers' => 'Eine Ziffer verlangen',
    'password_symbols' => 'Ein Sonderzeichen verlangen',
    'password_hibp' => 'Passwörter aus bekannten Datenlecks ablehnen',

    // Event labels
    'action_login' => 'Anmeldung',
    'action_login_failed' => 'Fehlgeschlagene Anmeldung',
    'action_logout' => 'Abmeldung',
    'action_lockout' => 'Sperrung',
    'action_2fa_enabled' => '2FA aktiviert',
    'action_2fa_disabled' => '2FA deaktiviert',
    'action_2fa_failed' => 'Fehlgeschlagene 2FA',

    // Table columns
    'col_when' => 'Wann',
    'col_action' => 'Ereignis',
    'col_actor' => 'Konto',
    'col_ip' => 'IP',
    'col_detail' => 'Detail',

];
