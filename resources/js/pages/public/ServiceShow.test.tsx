import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

import ServiceShow from './ServiceShow';
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
    indexUrl: 'https://example.test/services',
    service: { title: 'Managed Hosting', excerpt: 'We host your app.', content: '<p>Details here.</p>', thumbnail: null },
    crumbs: [
        { label: 'Home', url: 'https://example.test' },
        { label: 'Services', url: 'https://example.test/services' },
        { label: 'Managed Hosting', url: null },
    ],
};

describe('public ServiceShow', () => {
    it('renders the title, excerpt, prose content and breadcrumb', () => {
        render(<ServiceShow {...base} />);
        expect(screen.getByRole('heading', { level: 1, name: 'Managed Hosting' })).toBeInTheDocument();
        expect(screen.getByText('We host your app.')).toBeInTheDocument();
        expect(screen.getByText('Details here.')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Services' })).toHaveAttribute('href', 'https://example.test/services');
    });

    it('links back to the services index', () => {
        render(<ServiceShow {...base} />);
        expect(screen.getByRole('link', { name: /Back to services/i })).toHaveAttribute('href', 'https://example.test/services');
    });
});
