import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

const post = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  router: { post: (...a: any[]) => post(...a) },
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import RevisionDiff from './RevisionDiff';

const fields = [
  { field: 'title', old: 'Original title', current: 'Updated title', changed: true },
  { field: 'slug', old: 'x', current: 'x', changed: false },
];
const props = (over = {}) => ({
  entity_id: 7,
  lang: 'en',
  revision: { id: 30, created_at: '02.01.2026 10:00' },
  fields,
  ...over,
});

beforeEach(() => {
  post.mockClear();
  vi.spyOn(window, 'confirm').mockReturnValue(true);
});

describe('Posts RevisionDiff', () => {
  it('renders each field with its old and current values', () => {
    render(<RevisionDiff {...props()} />);
    expect(screen.getByText('title')).toBeInTheDocument();
    expect(screen.getByText('Original title')).toBeInTheDocument();
    expect(screen.getByText('Updated title')).toBeInTheDocument();
    expect(screen.getByText('Changed')).toBeInTheDocument();
    expect(screen.getByText('Unchanged')).toBeInTheDocument();
  });

  it('restore posts to the scoped restore endpoint for this revision', () => {
    render(<RevisionDiff {...props()} />);
    fireEvent.click(screen.getByTestId('diff-restore'));
    expect(post).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/posts/7/revisions/30/restore/en',
      {},
      expect.anything(),
    );
  });
});
