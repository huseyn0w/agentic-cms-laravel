import { render, screen, fireEvent, within } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const del = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  router: { delete: (...a: any[]) => del(...a) },
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import List from './List';

const props = {
  categories_list: {
    data: [
      { id: 1, title: 'Root', slug: 'root', parent_title: null },
      { id: 2, title: 'Travel', slug: 'travel', parent_title: null },
    ],
    current_page: 1, last_page: 1, total: 2,
  },
};

describe('Categories List', () => {
  it('renders rows and hides the checkbox for the protected id=1', () => {
    render(<List {...props} />);
    expect(screen.getByText('Travel')).toBeInTheDocument();
    expect(screen.queryByLabelText('select-1')).not.toBeInTheDocument();
    expect(screen.getByLabelText('select-2')).toBeInTheDocument();
  });

  it('shows the bulk bar with testid after selecting a row and fires router.delete', () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    render(<List {...props} />);
    fireEvent.click(screen.getByLabelText('select-2'));
    const btn = screen.getByTestId('bulk-delete-confirm');
    fireEvent.click(btn);
    expect(del).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/categories/multipleDelete',
      expect.objectContaining({ data: { categories: [2], categories_action: 'delete' } }),
    );
  });

  it('hides the Delete action button for the protected id=1 row but shows it for others', () => {
    render(<List {...props} />);
    const rootRow = screen.getByText('Root').closest('tr')!;
    const travelRow = screen.getByText('Travel').closest('tr')!;
    expect(within(rootRow).queryByText('Delete')).not.toBeInTheDocument();
    expect(within(travelRow).getByText('Delete')).toBeInTheDocument();
  });

  it('renders an empty-state message when there are no categories', () => {
    render(<List categories_list={{ data: [], current_page: 1, last_page: 1, total: 0 }} />);
    expect(screen.getByText('No categories yet')).toBeInTheDocument();
  });
});
