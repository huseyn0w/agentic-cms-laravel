import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import { Pagination } from './Pagination';

describe('Pagination', () => {
  it('shows the range summary and no nav on a single page', () => {
    render(<Pagination meta={{ current_page: 1, last_page: 1, from: 1, to: 3, total: 3 }} />);
    expect(screen.getByText('1–3', { exact: false })).toBeInTheDocument();
    expect(screen.queryByTestId('page-next')).not.toBeInTheDocument();
    expect(screen.queryByTestId('page-prev')).not.toBeInTheDocument();
  });

  it('renders prev/next Inertia links to the paginator URLs on a middle page', () => {
    render(<Pagination meta={{
      current_page: 2, last_page: 3, from: 11, to: 20, total: 25,
      prev_page_url: '/x?page=1', next_page_url: '/x?page=3',
    }} />);
    expect(screen.getByTestId('page-prev')).toHaveAttribute('href', '/x?page=1');
    expect(screen.getByTestId('page-next')).toHaveAttribute('href', '/x?page=3');
    expect(screen.getByText('2 / 3')).toBeInTheDocument();
  });

  it('disables prev on the first page and next on the last page', () => {
    const { rerender } = render(<Pagination meta={{
      current_page: 1, last_page: 3, from: 1, to: 10, total: 25,
      prev_page_url: null, next_page_url: '/x?page=2',
    }} />);
    expect(screen.getByTestId('page-prev-disabled')).toBeInTheDocument();
    expect(screen.getByTestId('page-next')).toBeInTheDocument();

    rerender(<Pagination meta={{
      current_page: 3, last_page: 3, from: 21, to: 25, total: 25,
      prev_page_url: '/x?page=2', next_page_url: null,
    }} />);
    expect(screen.getByTestId('page-prev')).toBeInTheDocument();
    expect(screen.getByTestId('page-next-disabled')).toBeInTheDocument();
  });
});
