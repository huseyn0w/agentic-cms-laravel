<?php

/**
 * Admin Security screen (Russian). See the English file for structure.
 */

return [

    // Login-protection form
    'protection_headline' => 'Защита входа',
    'protection_subtitle' => 'Ограничение числа неудачных попыток входа по email и IP.',
    'save_button' => 'Сохранить',
    'settings_saved' => 'Настройки безопасности сохранены.',
    'throttle_enabled' => 'Ограничивать неудачные попытки входа',
    'max_attempts' => 'Попыток до блокировки',
    'decay_minutes' => 'Длительность блокировки (минуты)',
    'block_enabled' => 'Автоблокировка повторных нарушителей на дольше',
    'block_threshold' => 'Попыток до автоблокировки',
    'block_minutes' => 'Длительность автоблокировки (минуты)',

    // Activity log
    'activity_headline' => 'Журнал активности',
    'audit_subtitle' => 'Активность аутентификации — входы, неудачные попытки и блокировки.',
    'filter_all' => 'Все',
    'empty' => 'Активность пока не зафиксирована',

    // Login protection — 2FA
    'require_2fa' => 'Требовать 2FA для всех с доступом в админку',

    // Password policy
    'password_policy_headline' => 'Политика паролей',
    'password_policy_subtitle' => 'Применяется при установке или сбросе пароля.',
    'password_min_length' => 'Минимальная длина',
    'password_mixed_case' => 'Требовать верхний и нижний регистр',
    'password_numbers' => 'Требовать цифру',
    'password_symbols' => 'Требовать спецсимвол',
    'password_hibp' => 'Отклонять пароли из известных утечек',

    // Security headers
    'headers_headline' => 'Заголовки безопасности',
    'headers_subtitle' => 'Базовые защитные заголовки шлются всегда. HSTS и CSP — по выбору.',
    'hsts_enabled' => 'Слать HSTS (только HTTPS)',
    'hsts_max_age' => 'HSTS max-age (секунды)',
    'csp' => 'Content-Security-Policy (для продвинутых — пусто = выключено)',
    'csp_report_only' => 'Только отчёт (не применять CSP, только логировать)',

    // Event labels
    'action_login' => 'Вход',
    'action_login_failed' => 'Неудачный вход',
    'action_logout' => 'Выход',
    'action_lockout' => 'Блокировка',
    'action_2fa_enabled' => '2FA включена',
    'action_2fa_disabled' => '2FA отключена',
    'action_2fa_failed' => 'Неудачная 2FA',

    // Table columns
    'col_when' => 'Когда',
    'col_action' => 'Событие',
    'col_actor' => 'Аккаунт',
    'col_ip' => 'IP',
    'col_detail' => 'Детали',

];
