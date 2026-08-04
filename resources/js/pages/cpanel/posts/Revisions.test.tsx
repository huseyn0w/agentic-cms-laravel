import { render, screen, fireEvent, within } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

const post = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  router: { post: (...a: any[]) => post(...a) },
}));
// t(k)=k → the component's tr(k, fallback) renders the fallback string.
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import Revisions from './Revisions';

const rows = [
  { id: 30, version: 2, author: 'admin', created_at: '02.01.2026 10:00' },
  { id: 29, version: 1, author: null, created_at: '01.01.2026 09:00' },
];
const props = (over = {}) => ({
  entity_id: 7,
  lang: 'en',
  revisions: { data: rows, current_page: 1, last_page: 1, total: 2 },
  ...over,
});

beforeEach(() => {
  post.mockClear();
  vi.spyOn(window, 'confirm').mockReturnValue(true);
});

describe('Posts Revisions', () => {
  it('renders each revision with version, author, and a compare link', () => {
    render(<Revisions {...props()} />);
    expect(screen.getByText('v2')).toBeInTheDocument();
    expect(screen.getByText('v1')).toBeInTheDocument();
    expect(screen.getByText('admin')).toBeInTheDocument();
    expect(screen.getByText('Unknown')).toBeInTheDocument(); // null author fallback
    const compare = screen.getAllByText('Compare');
    expect(compare).toHaveLength(2);
    expect(compare[0]).toHaveAttribute(
      'href',
      '/agentic-cms-laravel-admin/posts/7/revisions/30/compare/en',
    );
  });

  it('restore posts to the scoped restore endpoint', () => {
    render(<Revisions {...props()} />);
    fireEvent.click(screen.getByTestId('restore-29'));
    expect(post).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/posts/7/revisions/29/restore/en',
      {},
      expect.anything(),
    );
  });

  it('shows an empty state when there are no revisions', () => {
    render(<Revisions {...props({ revisions: { data: [], current_page: 1, last_page: 1, total: 0 } })} />);
    expect(screen.getByText('No revisions yet. The first edit will create one.')).toBeInTheDocument();
  });
});
