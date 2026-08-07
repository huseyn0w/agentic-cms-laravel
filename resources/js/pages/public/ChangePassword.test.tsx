import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

let sharedProps: any = { errors: {}, flash: {} };

vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, ...p }: any) => <a {...p}>{children}</a>,
    usePage: () => ({ props: sharedProps }),
    useForm: (initial: any) => ({ data: initial ?? {}, setData: () => undefined, post: () => undefined, processing: false, reset: () => undefined, errors: {} }),
}));

import ChangePassword from './ChangePassword';
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
    title: 'Change password',
    crumbs: [{ label: 'Home', url: 'https://example.test' }, { label: 'Change password', url: null }],
    action: 'https://example.test/profile/change_password',
    csrfToken: 'test-token',
    captchaHtml: '',
    ...over,
});

describe('public ChangePassword', () => {
    it('renders a method-spoofed form with the three password fields', () => {
        sharedProps = { errors: {}, flash: {} };
        const { container } = render(<ChangePassword {...(props() as any)} />);
        const form = container.querySelector('form')!;
        expect(form).toHaveAttribute('action', 'https://example.test/profile/change_password');
        expect(form.querySelector('input[name="_method"]')).toHaveAttribute('value', 'PUT');
        expect(container.querySelector('input[name="current_password"]')).toHaveAttribute('type', 'password');
        expect(container.querySelector('input[name="password"]')).toBeInTheDocument();
        expect(container.querySelector('input[name="password_confirmation"]')).toBeInTheDocument();
    });

    it('surfaces a wrong-current-password error from shared props', () => {
        sharedProps = { errors: { 0: 'Passwords do not match.' }, flash: {} };
        render(<ChangePassword {...(props() as any)} />);
        expect(screen.getByRole('alert')).toHaveTextContent('Passwords do not match.');
    });
});
