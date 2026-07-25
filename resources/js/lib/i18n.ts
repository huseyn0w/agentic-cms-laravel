import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

export type Messages = Record<string, string>;

/**
 * Initialize react-i18next once from the current-locale dictionary shared by
 * the server (HandleInertiaRequests -> `messages`). Keys are literal — they
 * contain "/" and "." — so both separators are disabled. Missing keys return
 * the key itself, matching Laravel's __() fallback. Safe to call repeatedly:
 * after the first init it just re-syncs.
 */
export function initI18n(locale: string, messages: Messages): typeof i18n {
    if (!i18n.isInitialized) {
        void i18n.use(initReactI18next).init({
            lng: locale,
            resources: { [locale]: { translation: messages } },
            keySeparator: false,
            nsSeparator: false,
            fallbackLng: false,
            returnNull: false,
            showSupportNotice: false,
            interpolation: { escapeValue: false },
        });
    } else {
        syncI18n(locale, messages);
    }

    return i18n;
}

/** Replace the locale bundle with the freshly shared dictionary and switch to it. */
export function syncI18n(locale: string, messages: Messages): void {
    i18n.addResourceBundle(locale, 'translation', messages, true, true);

    if (i18n.language !== locale) {
        void i18n.changeLanguage(locale);
    }
}
