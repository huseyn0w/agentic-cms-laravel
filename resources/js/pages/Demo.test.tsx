import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { initI18n } from '@/lib/i18n';
import Demo from '@/pages/Demo';

// usePage() is not available outside an Inertia runtime, so stub it. Head also
// needs a HeadManager from the Inertia App context (createInertiaApp), which
// this unit render doesn't set up, so it's stubbed to a no-op too — unrelated
// to the i18n assertion below.
vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual<typeof import('@inertiajs/react')>('@inertiajs/react');
    return {
        ...actual,
        usePage: () => ({
            props: {
                locale: { current: 'en', available: {} },
                auth: { user: null, can: {} },
                messages: {},
                flash: {},
            },
        }),
        Head: () => null,
    };
});

describe('Demo page', () => {
    it('renders a translated label from the dictionary', () => {
        initI18n('en', { 'cpanel/categories.add_new_category': 'Add new category' });

        render(<Demo message="Inertia is wired." />);

        expect(screen.getByText('Add new category')).toBeInTheDocument();
    });
});
