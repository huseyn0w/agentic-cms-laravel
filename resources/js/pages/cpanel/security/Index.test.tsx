import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, prefetch, cacheFor, ...p }: any) => <a {...p}>{children}</a>,
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@/components/admin/Pagination', () => ({ Pagination: () => null }));

import Index from './Index';

const page = (rows: any[], filter: string | null = null) => ({
  audit_log: { data: rows, current_page: 1, last_page: 1, total: rows.length },
  filter,
  actions: ['login', 'login_failed', 'logout', 'lockout'],
});

describe('Security audit screen', () => {
  it('renders each audit row with its actor and IP', () => {
    render(<Index {...page([
      { id: 1, action: 'login', description: 'Signed in', actor: 'admin', ip: '127.0.0.1', when: '06.08.2026 10:00' },
      { id: 2, action: 'login_failed', description: 'Failed login attempt', actor: 'bob@x.test', ip: '10.0.0.9', when: '06.08.2026 09:59' },
    ])} />);

    expect(screen.getByText('admin')).toBeInTheDocument();
    expect(screen.getByText('127.0.0.1')).toBeInTheDocument();
    expect(screen.getByText('bob@x.test')).toBeInTheDocument();
    // descriptions are unique to the rows (the action label also appears as a
    // filter chip, so assert on the description column instead)
    expect(screen.getByText('Signed in')).toBeInTheDocument();
    expect(screen.getByText('Failed login attempt')).toBeInTheDocument();
  });

  it('shows an empty state when there is no activity', () => {
    render(<Index {...page([])} />);
    expect(screen.getByText('No activity recorded yet')).toBeInTheDocument();
  });

  it('marks the active filter chip', () => {
    render(<Index {...page([], 'login')} />);
    const chip = screen.getByText('Sign in').closest('a')!;
    expect(chip).toHaveClass('bg-primary');
  });
});
