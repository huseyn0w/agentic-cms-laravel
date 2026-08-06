import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

let sharedProps: any = { errors: {}, flash: {} };

vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, ...p }: any) => <a {...p}>{children}</a>,
    usePage: () => ({ props: sharedProps }),
}));

import ProfileEdit from './ProfileEdit';
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
    title: 'Profile',
    crumbs: [{ label: 'Home', url: 'https://example.test' }, { label: 'Edit profile', url: null }],
    action: 'https://example.test/profile/update',
    csrfToken: 'test-token',
    changePasswordUrl: 'https://example.test/profile/change_password',
    avatar: '/avatar.png',
    countries: ['Germany', 'Turkey'],
    profile: {
        username: 'jane',
        email: 'jane@example.test',
        name: 'Jane',
        surname: 'Doe',
        country: 'Germany',
        city: 'Berlin',
        about_me: 'Hi',
        gender: 'female',
        facebook_url: null,
        google_url: null,
        twitter_url: null,
        instagram_url: null,
        linkedin_url: 'https://linkedin.test/jane',
        xing_url: null,
    },
    ...over,
});

describe('public ProfileEdit', () => {
    it('renders a method-spoofed multipart form seeded with current values', () => {
        sharedProps = { errors: {}, flash: {} };
        const { container } = render(<ProfileEdit {...(props() as any)} />);
        const form = container.querySelector('form')!;
        expect(form).toHaveAttribute('action', 'https://example.test/profile/update');
        expect(form).toHaveAttribute('enctype', 'multipart/form-data');
        expect(form.querySelector('input[name="_method"]')).toHaveAttribute('value', 'PUT');
        expect(form.querySelector('input[name="_token"]')).toHaveAttribute('value', 'test-token');
        expect(screen.getByLabelText('Email')).toHaveValue('jane@example.test');
        expect(container.querySelector('input[name="linkedin_url"]')).toHaveValue('https://linkedin.test/jane');
        expect(container.querySelector('input[name="avatar"]')).toHaveAttribute('type', 'file');
    });

    it('surfaces validation errors from shared props', () => {
        sharedProps = { errors: { email: 'The email field is required.' }, flash: {} };
        render(<ProfileEdit {...(props() as any)} />);
        expect(screen.getByRole('alert')).toHaveTextContent('The email field is required.');
    });

    it('shows the success flash', () => {
        sharedProps = { errors: {}, flash: { success: 'Profile updated.' } };
        render(<ProfileEdit {...(props() as any)} />);
        expect(screen.getByRole('status')).toHaveTextContent('Profile updated.');
    });
});
