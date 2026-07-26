import { render, screen, fireEvent } from '@testing-library/react';
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
});
