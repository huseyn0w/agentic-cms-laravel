import { render, screen, fireEvent, within } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

const post = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  router: { post: (...a: any[]) => post(...a) },
  usePage: () => ({ props: { locale: { current: 'en' } } }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import List from './List';

const rows = [
  { id: 1, title: 'Consulting', sort_order: 1, status: 1 },
  { id: 2, title: 'Draft svc', sort_order: 2, status: 0 },
];
const props = (over = {}) => ({
  services_list: { data: rows, current_page: 1, last_page: 1, total: 2 },
  trashed: false,
  ...over,
});

beforeEach(() => {
  post.mockClear();
  vi.spyOn(window, 'confirm').mockReturnValue(true);
});

describe('Services List', () => {
  it('renders rows with order, title, and status in live mode', () => {
    render(<List {...props()} />);
    expect(screen.getByText('Consulting')).toBeInTheDocument();
    expect(screen.getByText('Published')).toBeInTheDocument();
    expect(screen.getByText('Private')).toBeInTheDocument();
    expect(screen.getAllByText('Edit')).toHaveLength(2);
  });

  it('bulk-deletes via POST /multiple with services_action=delete', () => {
    render(<List {...props()} />);
    fireEvent.click(screen.getByLabelText('select-2'));
    fireEvent.click(screen.getByTestId('bulk-delete-confirm'));
    expect(post).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/services/multiple',
      { services: [2], services_action: 'delete' },
      expect.anything(),
    );
  });

  it('a single row delete also routes through /multiple', () => {
    render(<List {...props()} />);
    const row = screen.getByText('Consulting').closest('tr')!;
    fireEvent.click(within(row).getByText('Delete'));
    expect(post).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/services/multiple',
      { services: [1], services_action: 'delete' },
      expect.anything(),
    );
  });

  it('in trashed mode hides New, and bulk restore sends services_action=restore', () => {
    render(<List {...props({ trashed: true })} />);
    expect(screen.queryByText('New service')).not.toBeInTheDocument();
    fireEvent.click(screen.getByLabelText('select-1'));
    fireEvent.click(screen.getByTestId('bulk-restore-confirm'));
    expect(post).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/services/multiple',
      { services: [1], services_action: 'restore' },
      expect.anything(),
    );
  });

  it('trashed row destroy sends services_action=destroy', () => {
    render(<List {...props({ trashed: true })} />);
    const row = screen.getByText('Draft svc').closest('tr')!;
    fireEvent.click(within(row).getByText('Delete permanently'));
    expect(post).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/services/multiple',
      { services: [2], services_action: 'destroy' },
      expect.anything(),
    );
  });
});
