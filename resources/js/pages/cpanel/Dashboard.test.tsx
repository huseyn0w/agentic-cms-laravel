import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import Dashboard from './Dashboard';

vi.mock('@inertiajs/react', () => ({ Head: () => null }));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

describe('Dashboard page', () => {
  it('renders the three latest-N panels', () => {
    render(<Dashboard
      posts={[{ id: 1, title: 'Hello world' }]}
      users={[{ username: 'ada' }]}
      comments={[{ comment: 'Nice' }]} />);
    expect(screen.getByText('Hello world')).toBeInTheDocument();
    expect(screen.getByText('ada')).toBeInTheDocument();
    expect(screen.getByText('Nice')).toBeInTheDocument();
  });

  it('renders the stat totals and flags pending comments', () => {
    render(<Dashboard
      posts={[]} users={[]} comments={[]}
      counts={{ posts: 128, users: 57, comments: 342, comments_pending: 12, scheduled: 2 }} />);
    expect(screen.getByText('128')).toBeInTheDocument();
    expect(screen.getByText('342')).toBeInTheDocument();
    expect(screen.getByText('57')).toBeInTheDocument();
    expect(screen.getByText('2')).toBeInTheDocument();
    // pending caption uses the real count from countPending()
    expect(screen.getByText('12 awaiting review')).toBeInTheDocument();
  });
});
