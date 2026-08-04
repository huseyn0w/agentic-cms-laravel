import { render, screen, fireEvent, within } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

const del = vi.fn();
const post = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  router: { delete: (...a: any[]) => del(...a), post: (...a: any[]) => post(...a) },
  usePage: () => ({ props: { locale: { current: 'en' } } }),
}));
// t(k)=k → the component's tr(k, fallback) renders the fallback string.
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import List from './List';

const rows = [
  { id: 1, title: 'First post', author: 'admin', created_at: '01.01.2026', status: 1 },
  { id: 2, title: 'Draft two', author: 'editor', created_at: '02.01.2026', status: 0 },
];
const props = (over = {}) => ({
  posts_list: { data: rows, current_page: 1, last_page: 1, total: 2 },
  trashed: false,
  ...over,
});

beforeEach(() => {
  del.mockClear();
  post.mockClear();
  vi.spyOn(window, 'confirm').mockReturnValue(true);
});

describe('Posts List', () => {
  it('renders rows with title, author, and status in live mode', () => {
    render(<List {...props()} />);
    expect(screen.getByText('First post')).toBeInTheDocument();
    expect(screen.getByText('editor')).toBeInTheDocument();
    expect(screen.getByText('Published')).toBeInTheDocument();
    expect(screen.getByText('Private')).toBeInTheDocument();
    expect(screen.getAllByText('Edit')).toHaveLength(2);
  });

  it('bulk-deletes selected posts through the redirecting bulk endpoint', () => {
    render(<List {...props()} />);
    fireEvent.click(screen.getByLabelText('select-2'));
    fireEvent.click(screen.getByTestId('bulk-delete-confirm'));
    expect(del).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/posts/multipleDelete',
      expect.objectContaining({ data: { posts: [2], posts_action: 'delete' } }),
    );
  });

  it('a single row delete also routes through the bulk delete endpoint', () => {
    render(<List {...props()} />);
    const row = screen.getByText('First post').closest('tr')!;
    fireEvent.click(within(row).getByText('Delete'));
    expect(del).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/posts/multipleDelete',
      expect.objectContaining({ data: { posts: [1], posts_action: 'delete' } }),
    );
  });

  it('in trashed mode hides New, and bulk restore hits the action endpoint', () => {
    render(<List {...props({ trashed: true })} />);
    expect(screen.queryByText('New post')).not.toBeInTheDocument();
    fireEvent.click(screen.getByLabelText('select-1'));
    fireEvent.click(screen.getByTestId('bulk-restore-confirm'));
    expect(post).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/posts/multiple',
      { posts: [1], posts_action: 'restore' },
      expect.anything(),
    );
  });

  it('trashed row destroy sends posts_action=destroy', () => {
    render(<List {...props({ trashed: true })} />);
    const row = screen.getByText('Draft two').closest('tr')!;
    fireEvent.click(within(row).getByText('Delete permanently'));
    expect(post).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/posts/multiple',
      { posts: [2], posts_action: 'destroy' },
      expect.anything(),
    );
  });
});
