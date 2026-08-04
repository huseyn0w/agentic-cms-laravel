import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const post = vi.fn();
const put = vi.fn();
const setData = vi.fn();
const transform = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  usePage: () => ({ props: { locale: { current: 'en' } } }),
  useForm: (initial: any) => ({ data: initial, errors: {}, processing: false, setData, post, put, transform }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
// Stub the heavy children (TipTap / LFM) — this suite tests the form wiring.
vi.mock('@/components/RichText', () => ({ RichText: ({ id }: any) => <div data-testid={`richtext-${id}`} /> }));
vi.mock('@/components/MediaField', () => ({ MediaField: ({ label }: any) => <div data-testid="media-field">{label}</div> }));
vi.mock('@/lib/lfm', () => ({ useLfmPicker: () => ({ open: vi.fn() }) }));
import Form from './Form';

const categories_list = [{ category_id: 1, title: 'News' }, { category_id: 2, title: 'Tech' }];
const authors = [{ id: 1, username: 'admin' }, { id: 2, username: 'editor' }];

describe('Posts Form', () => {
  it('new: renders title/slug/submit, both editors + thumbnail, and posts to /new', () => {
    render(<Form entity={null} categories_list={categories_list} authors={authors} translation_links={{}} />);
    expect(screen.getByTestId('post-title')).toBeInTheDocument();
    expect(screen.getByTestId('post-slug')).toBeInTheDocument();
    expect(screen.getByTestId('post-submit')).toBeInTheDocument();
    expect(screen.getByTestId('richtext-content')).toBeInTheDocument();
    expect(screen.getByTestId('richtext-preview')).toBeInTheDocument();
    expect(screen.getByTestId('media-field')).toBeInTheDocument();
    // author + category options rendered
    expect(screen.getByRole('option', { name: 'editor' })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Tech' })).toBeInTheDocument();

    fireEvent.submit(screen.getByTestId('post-title').closest('form')!);
    expect(transform).toHaveBeenCalled(); // empty dates dropped before submit
    expect(post).toHaveBeenCalledWith('/agentic-cms-laravel-admin/posts/new');
  });

  it('edit: prefills title and PUTs to /{id}/update', () => {
    const entity = {
      id: 9, title: 'Hello', slug: 'hello', content: '<p>c</p>', preview: '<p>p</p>',
      author_id: 2, meta_keywords: 'k', meta_description: 'd', canonical_url: '', meta_noindex: false,
      status: 1, thumbnail: '', updated_at: '2026-01-01 10:00:00', scheduled_at: '', category: [1], tags: 'a, b',
    };
    render(<Form entity={entity} categories_list={categories_list} authors={authors} translation_links={{}} />);
    expect(screen.getByTestId('post-title')).toHaveValue('Hello');
    fireEvent.submit(screen.getByTestId('post-title').closest('form')!);
    expect(put).toHaveBeenCalledWith('/agentic-cms-laravel-admin/posts/9/update');
  });
});
