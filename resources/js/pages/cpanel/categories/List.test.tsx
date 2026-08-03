import { render, screen, fireEvent, within } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const del = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  // Expose prefetch/cacheFor as data attributes to lock the instant-nav strategy.
  Link: ({ children, prefetch, cacheFor, ...p }: any) => (
    <a data-prefetch={String(prefetch)} data-cache-for={String(cacheFor)} {...p}>{children}</a>
  ),
  router: { delete: (...a: any[]) => del(...a) },
  usePage: () => ({ props: { locale: { current: 'en' } } }),
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

  it('prefetches the New link on mount and each row Edit link on hover, both cached 15s', () => {
    render(<List {...props} />);
    const newLink = screen.getByText(/New category/).closest('a')!;
    expect(newLink).toHaveAttribute('data-prefetch', 'mount');
    expect(newLink).toHaveAttribute('data-cache-for', '15s');
    const travelRow = screen.getByText('Travel').closest('tr')!;
    const editLink = within(travelRow).getByText('Edit').closest('a')!;
    expect(editLink).toHaveAttribute('data-prefetch', 'true');
    expect(editLink).toHaveAttribute('data-cache-for', '15s');
  });

  it('select-all selects every non-protected row and never the protected id=1, then unchecking clears', () => {
    const multiRowProps = {
      categories_list: {
        data: [
          { id: 1, title: 'Root', slug: 'root', parent_title: null },
          { id: 2, title: 'Travel', slug: 'travel', parent_title: null },
          { id: 3, title: 'Food', slug: 'food', parent_title: null },
        ],
        current_page: 1, last_page: 1, total: 3,
      },
    };
    render(<List {...multiRowProps} />);
    const selectAll = screen.getByLabelText('select-all');

    fireEvent.click(selectAll);
    expect(screen.getByLabelText('select-2')).toBeChecked();
    expect(screen.getByLabelText('select-3')).toBeChecked();
    expect(screen.queryByLabelText('select-1')).not.toBeInTheDocument();

    fireEvent.click(selectAll);
    expect(screen.getByLabelText('select-2')).not.toBeChecked();
    expect(screen.getByLabelText('select-3')).not.toBeChecked();
  });
});
