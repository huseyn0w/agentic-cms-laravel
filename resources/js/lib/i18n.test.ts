import { describe, expect, it, beforeEach } from 'vitest';
import { initI18n, syncI18n } from '@/lib/i18n';

const EN = {
    'cpanel/categories.add_new_category': 'Add new category',
    'validation.min.string': 'The {{attribute}} must be at least {{min}} characters.',
};

const DE = {
    'cpanel/categories.add_new_category': 'Neue Kategorie hinzufügen',
};

describe('i18n', () => {
    beforeEach(() => {
        initI18n('en', EN);
        syncI18n('en', EN);
    });

    it('looks up a literal slash/dot key', () => {
        const i18n = initI18n('en', EN);
        expect(i18n.t('cpanel/categories.add_new_category')).toBe('Add new category');
    });

    it('interpolates {{placeholders}}', () => {
        const i18n = initI18n('en', EN);
        expect(i18n.t('validation.min.string', { attribute: 'Name', min: 3 })).toBe(
            'The Name must be at least 3 characters.',
        );
    });

    it('returns the key itself when missing', () => {
        const i18n = initI18n('en', EN);
        expect(i18n.t('does/not.exist')).toBe('does/not.exist');
    });

    it('switches language via syncI18n', () => {
        const i18n = initI18n('en', EN);
        syncI18n('de', DE);
        expect(i18n.language).toBe('de');
        expect(i18n.t('cpanel/categories.add_new_category')).toBe('Neue Kategorie hinzufügen');
    });
});
