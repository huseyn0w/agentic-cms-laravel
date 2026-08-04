import { render, screen, fireEvent, within } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

const del = vi.fn();
const put = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  router: { delete: (...a: any[]) => del(...a), put: (...a: any[]) => put(...a) },
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import List from './List';

const rows = [
  { id: 1, post_title: 'Hello World', comment: 'Nice post', author: 'ada', date: '01.02.2026', status: 0 },
  { id: 2, post_title: 'Second', comment: 'Approved one', author: 'bob', date: '02.02.2026', status: 1 },
];
const props = (over = {}) => ({
  comments_list: { data: rows, current_page: 1, last_page: 1, total: 2 },
  ...over,
});

beforeEach(() => {
  del.mockClear();
  put.mockClear();
  vi.spyOn(window, 'confirm').mockReturnValue(true);
});

describe('Comments List', () => {
  it('renders post title, comment body and author', () => {
    render(<List {...props()} />);
    expect(screen.getByText('Hello World')).toBeInTheDocument();
    expect(screen.getByText('Nice post')).toBeInTheDocument();
    expect(screen.getByText('bob')).toBeInTheDocument();
  });

  it('approves a pending comment via PUT /{id}/approve', () => {
    render(<List {...props()} />);
    const row = screen.getByText('Nice post').closest('tr')!;
    fireEvent.click(within(row).getByText('Approve'));
    expect(put).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/comments/1/approve',
      {},
      expect.objectContaining({ preserveScroll: true }),
    );
  });

  it('unapproves an approved comment via PUT /{id}/unapprove', () => {
    render(<List {...props()} />);
    const row = screen.getByText('Approved one').closest('tr')!;
    fireEvent.click(within(row).getByText('Unapprove'));
    expect(put).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/comments/2/unapprove',
      {},
      expect.objectContaining({ preserveScroll: true }),
    );
  });

  it('bulk-deletes selected comments through /multipleDelete', () => {
    render(<List {...props()} />);
    fireEvent.click(screen.getByLabelText('select-2'));
    fireEvent.click(screen.getByTestId('bulk-delete-confirm'));
    expect(del).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/comments/multipleDelete',
      expect.objectContaining({ data: { comments: [2] } }),
    );
  });

  it('a single row delete routes through the bulk delete endpoint', () => {
    render(<List {...props()} />);
    const row = screen.getByText('Nice post').closest('tr')!;
    fireEvent.click(within(row).getByText('Delete'));
    expect(del).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/comments/multipleDelete',
      expect.objectContaining({ data: { comments: [1] } }),
    );
  });
});
