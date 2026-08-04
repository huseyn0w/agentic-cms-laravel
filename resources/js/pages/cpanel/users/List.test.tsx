import { render, screen, fireEvent, within } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

const del = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  router: { delete: (...a: any[]) => del(...a) },
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import List from './List';

const rows = [
  { id: 1, username: 'admin', email: 'a@x.io', name: 'Ada', surname: 'Lovelace', country: 'UK', city: 'London', role: 'Administrator' },
  { id: 2, username: 'editor', email: 'e@x.io', name: null, surname: null, country: null, city: null, role: 'Editor' },
];
const props = (over = {}) => ({
  users_list: { data: rows, current_page: 1, last_page: 1, total: 2 },
  ...over,
});

beforeEach(() => {
  del.mockClear();
  vi.spyOn(window, 'confirm').mockReturnValue(true);
});

describe('Users List', () => {
  it('renders rows with username, email, and role', () => {
    render(<List {...props()} />);
    expect(screen.getByText('admin')).toBeInTheDocument();
    expect(screen.getByText('e@x.io')).toBeInTheDocument();
    expect(screen.getByText('Administrator')).toBeInTheDocument();
    expect(screen.getAllByText('Edit')).toHaveLength(2);
  });

  it('bulk-deletes selected users with the required users_action', () => {
    render(<List {...props()} />);
    fireEvent.click(screen.getByLabelText('select-2'));
    fireEvent.click(screen.getByTestId('bulk-delete-confirm'));
    expect(del).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/users/multipleDelete',
      expect.objectContaining({ data: { users: [2], users_action: 'delete' } }),
    );
  });

  it('a single row delete also routes through the bulk delete endpoint', () => {
    render(<List {...props()} />);
    const row = screen.getByText('admin').closest('tr')!;
    fireEvent.click(within(row).getByText('Delete'));
    expect(del).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/users/multipleDelete',
      expect.objectContaining({ data: { users: [1], users_action: 'delete' } }),
    );
  });
});
