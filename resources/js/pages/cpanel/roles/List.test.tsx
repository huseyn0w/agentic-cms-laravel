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
  { id: 1, name: 'Administrator' },
  { id: 2, name: 'Editor' },
  { id: 5, name: 'Author' },
];
const props = (over = {}) => ({
  roles_list: { data: rows, current_page: 1, last_page: 1, total: 3 },
  ...over,
});

beforeEach(() => {
  del.mockClear();
  vi.spyOn(window, 'confirm').mockReturnValue(true);
});

describe('Roles List', () => {
  it('renders role names with an edit link each', () => {
    render(<List {...props()} />);
    expect(screen.getByText('Administrator')).toBeInTheDocument();
    expect(screen.getByText('Author')).toBeInTheDocument();
    expect(screen.getAllByText('Edit')).toHaveLength(3);
  });

  it('hides delete for the seeded Administrator (1) and Editor (2) roles', () => {
    render(<List {...props()} />);
    const adminRow = screen.getByText('Administrator').closest('tr')!;
    const editorRow = screen.getByText('Editor').closest('tr')!;
    const authorRow = screen.getByText('Author').closest('tr')!;
    expect(within(adminRow).queryByText('Delete')).not.toBeInTheDocument();
    expect(within(editorRow).queryByText('Delete')).not.toBeInTheDocument();
    expect(within(authorRow).getByText('Delete')).toBeInTheDocument();
  });

  it('deletes a removable role via DELETE /{id}/delete', () => {
    render(<List {...props()} />);
    const authorRow = screen.getByText('Author').closest('tr')!;
    fireEvent.click(within(authorRow).getByText('Delete'));
    expect(del).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/roles/5/delete',
      expect.objectContaining({ preserveScroll: true }),
    );
  });
});
