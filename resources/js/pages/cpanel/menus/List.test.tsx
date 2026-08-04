import { render, screen, fireEvent, within } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

const del = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  router: { delete: (...a: any[]) => del(...a) },
  usePage: () => ({ props: { locale: { current: 'en' } } }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import List from './List';

const rows = [
  { id: 1, title: 'Main' },
  { id: 3, title: 'Footer' },
];
const props = (over = {}) => ({
  menus_list: { data: rows, current_page: 1, last_page: 1, total: 2 },
  ...over,
});

beforeEach(() => {
  del.mockClear();
  vi.spyOn(window, 'confirm').mockReturnValue(true);
});

describe('Menus List', () => {
  it('renders menu titles with an edit link each', () => {
    render(<List {...props()} />);
    expect(screen.getByText('Main')).toBeInTheDocument();
    expect(screen.getByText('Footer')).toBeInTheDocument();
    expect(screen.getAllByText('Edit')).toHaveLength(2);
  });

  it('hides delete for the primary menu (id 1) and shows it for the rest', () => {
    render(<List {...props()} />);
    const mainRow = screen.getByText('Main').closest('tr')!;
    const footerRow = screen.getByText('Footer').closest('tr')!;
    expect(within(mainRow).queryByText('Delete')).not.toBeInTheDocument();
    expect(within(footerRow).getByText('Delete')).toBeInTheDocument();
  });

  it('deletes a removable menu via DELETE /{id}/delete', () => {
    render(<List {...props()} />);
    const footerRow = screen.getByText('Footer').closest('tr')!;
    fireEvent.click(within(footerRow).getByText('Delete'));
    expect(del).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/menus/3/delete',
      expect.objectContaining({ preserveScroll: true }),
    );
  });
});
