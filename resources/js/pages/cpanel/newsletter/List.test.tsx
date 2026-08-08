import { describe, it, expect, vi } from 'vitest';
import { render, screen, within } from '@testing-library/react';
import type { AnchorHTMLAttributes } from 'react';
import List, { type Row } from './List';

vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...props }: AnchorHTMLAttributes<HTMLAnchorElement>) => <a {...props}>{children}</a>,
  router: { delete: vi.fn(), get: vi.fn() },
  useForm: () => ({ data: { email: '', search: '' }, setData: vi.fn(), post: vi.fn(), processing: false, reset: vi.fn(), errors: {} }),
}));

// Mocked t returns the key, so the component's `tr(key, fallback)` helper falls
// back to the English fallback string — assert those visible labels.
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

const paginator = (rows: Row[]) => ({ data: rows, current_page: 1, last_page: 1, total: rows.length });

describe('cpanel newsletter List', () => {
  it('renders a row per subscriber with the right status pill', () => {
    render(
      <List
        subscribers={paginator([
          { id: 1, email: 'a@example.com', status: 'confirmed', locale: 'en', source: 'footer', subscribed: '01.08.2026' },
          { id: 2, email: 'b@example.com', status: 'pending', locale: 'de', source: 'admin', subscribed: '02.08.2026' },
        ])}
        filters={{ status: null, search: null }}
      />,
    );

    expect(screen.getByText('a@example.com')).toBeInTheDocument();
    expect(screen.getByText('b@example.com')).toBeInTheDocument();
    // Status pill labels resolve to the English fallbacks under the mocked t.
    // "Confirmed"/"Pending" also appear as filter chips, so scope to the table.
    const table = within(screen.getByRole('table'));
    expect(table.getByText('Confirmed')).toBeInTheDocument();
    expect(table.getByText('Pending')).toBeInTheDocument();
  });

  it('highlights the active status filter chip', () => {
    render(
      <List subscribers={paginator([])} filters={{ status: 'confirmed', search: null }} />,
    );
    expect(screen.getByTestId('filter-confirmed')).toHaveAttribute('aria-current', 'true');
    expect(screen.getByTestId('filter-all')).not.toHaveAttribute('aria-current');
  });
});
