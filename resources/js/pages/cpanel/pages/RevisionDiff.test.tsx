import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

const post = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  router: { post: (...a: any[]) => post(...a) },
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import RevisionDiff from './RevisionDiff';

const props = () => ({
  entity_id: 7,
  lang: 'en',
  revision: { id: 30, created_at: '02.01.2026 10:00' },
  fields: [{ field: 'content', old: 'a', current: 'b', changed: true }],
});

beforeEach(() => {
  post.mockClear();
  vi.spyOn(window, 'confirm').mockReturnValue(true);
});

describe('Pages RevisionDiff (wrapper)', () => {
  it('restore posts to the pages base restore endpoint for this revision', () => {
    render(<RevisionDiff {...props()} />);
    expect(screen.getByText('content')).toBeInTheDocument();
    fireEvent.click(screen.getByTestId('diff-restore'));
    expect(post).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/pages/7/revisions/30/restore/en',
      {},
      expect.anything(),
    );
  });
});
