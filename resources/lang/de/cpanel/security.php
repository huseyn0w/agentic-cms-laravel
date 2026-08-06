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
    'password_history' => 'Wiederverwendung der letzten N Passwörter sperren (0 = aus)',
    'password_reused' => 'Dieses Passwort entspricht einem kürzlich verwendeten. Bitte wählen Sie ein anderes.',

    // Security headers
    'headers_headline' => 'Sicherheits-Header',
    'headers_subtitle' => 'Basis-Härtungs-Header werden immer gesendet. HSTS und CSP sind optional.',
    'hsts_enabled' => 'HSTS senden (nur HTTPS)',
    'hsts_max_age' => 'HSTS max-age (Sekunden)',
    'csp' => 'Content-Security-Policy (erweitert — leer lassen zum Deaktivieren)',
    'csp_report_only' => 'Nur-Bericht (CSP nicht erzwingen, nur protokollieren)',

    // Admin IP allowlist
    'ip_allowlist_headline' => 'Admin-IP-Zulassungsliste',
    'ip_allowlist_subtitle' => 'Eine IP oder ein CIDR pro Zeile. Leer bedeutet keine Einschränkung. Nur diese Adressen erreichen das Admin-Panel.',
    'ip_current' => 'Ihre aktuelle IP',
    'ip_forbidden' => 'Ihre IP-Adresse darf nicht auf das Admin-Panel zugreifen.',

    // Site lockdown
    'lockdown_headline' => 'Website-Sperre',
    'lockdown_subtitle' => 'Die öffentliche Website privat schalten. Nicht angemeldete Besucher werden zur Anmeldeseite geleitet; Admin-Panel und Anmeldung bleiben erreichbar.',
    'lockdown_enabled' => 'Anmeldung erforderlich, um die öffentliche Website zu sehen',

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
