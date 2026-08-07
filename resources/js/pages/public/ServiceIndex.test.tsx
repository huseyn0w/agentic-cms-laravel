import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, prefetch, cacheFor, ...p }: any) => <a {...p}>{children}</a>,
    usePage: () => ({ props: {} }),
    useForm: (initial: any) => ({ data: initial ?? {}, setData: () => undefined, post: () => undefined, processing: false, reset: () => undefined, errors: {} }),
}));

import ServiceIndex from './ServiceIndex';
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

const base = {
    shell,
    heading: 'Our services',
    emptyText: 'No services yet',
    services: [
        { title: 'Hosting', url: 'https://example.test/services/hosting', icon: '🚀', excerpt: 'Fast hosting' },
        { title: 'Design', url: 'https://example.test/services/design', icon: null, excerpt: null },
    ],
};

describe('public ServiceIndex', () => {
    it('renders the heading and a card per service', () => {
        render(<ServiceIndex {...base} />);
        expect(screen.getByRole('heading', { level: 1, name: 'Our services' })).toBeInTheDocument();
        expect(screen.getAllByTestId('service-card')).toHaveLength(2);
        expect(screen.getByRole('heading', { level: 2, name: 'Hosting' })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Hosting' })).toHaveAttribute('href', 'https://example.test/services/hosting');
    });

    it('shows the empty state when there are no services', () => {
        render(<ServiceIndex {...base} services={[]} />);
        expect(screen.getByTestId('services-empty')).toHaveTextContent('No services yet');
    });
});
