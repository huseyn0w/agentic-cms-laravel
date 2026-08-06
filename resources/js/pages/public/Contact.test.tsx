import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

let sharedProps: any = { errors: {}, flash: {} };

vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, ...p }: any) => <a {...p}>{children}</a>,
    usePage: () => ({ props: sharedProps }),
}));

import Contact from './Contact';
import type { Shell } from '@/layouts/PublicLayout';

const shell: Shell = {
    wordmark: 'AgenticCms-Laravel',
    homeUrl: 'https://example.test',
    logoUrl: null,
    searchUrl: 'https://example.test/search',
    currentLang: 'EN',
    menu: [],
    languages: [],
    general: { websiteName: 'AgenticCms-Laravel', membership: true },
    site: { copyright: null, linkedinUrl: null, githubUrl: null },
    auth: { user: null, canSeeAdmin: false, loginUrl: '/login', registerUrl: '/register' },
};

const props = (over: Record<string, unknown> = {}) => ({
    shell,
    title: 'Contact us',
    crumbs: [{ label: 'Home', url: 'https://example.test' }, { label: 'Contact us', url: null }],
    action: 'https://example.test/contact/sendform',
    csrfToken: 'test-token',
    captchaHtml: '',
    prefill: null,
    ...over,
});

describe('public Contact', () => {
    it('renders the guest form with a CSRF token and the action', () => {
        sharedProps = { errors: {}, flash: {} };
        const { container } = render(<Contact {...(props() as any)} />);
        const form = container.querySelector('form')!;
        expect(form).toHaveAttribute('action', 'https://example.test/contact/sendform');
        expect(form.querySelector('input[name="_token"]')).toHaveAttribute('value', 'test-token');
        expect(screen.getByLabelText('First name')).toBeInTheDocument();
        expect(screen.getByLabelText('Message')).toBeInTheDocument();
    });

    it('renders hidden identity fields when prefilled for a logged-in user', () => {
        sharedProps = { errors: {}, flash: {} };
        const { container } = render(
            <Contact {...(props({ prefill: { first_name: 'Jane', last_name: 'Doe', email: 'jane@example.test' } }) as any)} />,
        );
        expect(container.querySelector('input[name="email"]')).toHaveAttribute('value', 'jane@example.test');
        expect(screen.queryByLabelText('First name')).not.toBeInTheDocument();
        expect(screen.getByLabelText('Subject')).toBeInTheDocument();
    });

    it('surfaces validation errors and the success flash from shared props', () => {
        sharedProps = { errors: { email: 'The email field is required.' }, flash: { success: 'Message sent.' } };
        render(<Contact {...(props() as any)} />);
        expect(screen.getByRole('alert')).toHaveTextContent('The email field is required.');
        expect(screen.getByRole('status')).toHaveTextContent('Message sent.');
    });
});
