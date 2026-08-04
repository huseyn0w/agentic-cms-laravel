import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

const post = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  router: { post: (...a: any[]) => post(...a) },
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import Revisions from './Revisions';

const rows = [{ id: 30, version: 2, author: 'admin', created_at: '02.01.2026 10:00' }];
const props = () => ({
  entity_id: 7,
  lang: 'en',
  revisions: { data: rows, current_page: 1, last_page: 1, total: 1 },
});

beforeEach(() => {
  post.mockClear();
  vi.spyOn(window, 'confirm').mockReturnValue(true);
});

describe('Pages Revisions (wrapper)', () => {
  it('builds compare + restore links against the pages base URL', () => {
    render(<Revisions {...props()} />);
    expect(screen.getByText('Compare')).toHaveAttribute(
      'href',
      '/agentic-cms-laravel-admin/pages/7/revisions/30/compare/en',
    );
    fireEvent.click(screen.getByTestId('restore-30'));
    expect(post).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/pages/7/revisions/30/restore/en',
      {},
      expect.anything(),
    );
  });
});
