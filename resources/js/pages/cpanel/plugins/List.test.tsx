import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

const put = vi.fn();
vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    router: { put: (...a: any[]) => put(...a) },
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@/layouts/AdminLayout', () => ({ AdminLayout: ({ children }: any) => <div>{children}</div> }));

import List from './List';

const props = (over = {}) => ({
    plugins: [
        { slug: 'seo', name: 'SEO', description: 'Meta tags', enabled: true },
        { slug: 'cache', name: 'Cache', description: 'Page cache', enabled: false },
    ],
    ...over,
});

beforeEach(() => put.mockClear());

describe('Plugins List', () => {
    it('renders each plugin name, description and status', () => {
        render(<List {...props()} />);
        expect(screen.getByText('SEO')).toBeInTheDocument();
        expect(screen.getByText('Meta tags')).toBeInTheDocument();
        expect(screen.getByText('Cache')).toBeInTheDocument();
    });

    it('disables an enabled plugin via PUT /toggle with enabled=false', () => {
        render(<List {...props()} />);
        fireEvent.click(screen.getByTestId('toggle-seo'));
        expect(put).toHaveBeenCalledWith(
            '/agentic-cms-laravel-admin/plugins/toggle',
            { slug: 'seo', enabled: false },
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('enables a disabled plugin via PUT /toggle with enabled=true', () => {
        render(<List {...props()} />);
        fireEvent.click(screen.getByTestId('toggle-cache'));
        expect(put).toHaveBeenCalledWith(
            '/agentic-cms-laravel-admin/plugins/toggle',
            { slug: 'cache', enabled: true },
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('shows an empty state when there are no plugins', () => {
        render(<List {...props({ plugins: [] })} />);
        expect(screen.getByText('No plugins found.')).toBeInTheDocument();
    });
});
