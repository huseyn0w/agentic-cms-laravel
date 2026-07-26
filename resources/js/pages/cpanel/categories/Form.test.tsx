import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const post = vi.fn();
const put = vi.fn();
const setData = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  usePage: () => ({ props: { locale: { current: 'en' } } }),
  useForm: (initial: any) => ({
    data: initial, errors: {}, processing: false, setData, post, put,
  }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import Form from './Form';

const parent_options = [{ category_id: 5, title: 'Travel', depth: 0 }];

describe('Categories Form', () => {
  it('new: renders required title/slug testids and posts to /new', () => {
    render(<Form entity={null} parent_options={parent_options} translation_links={{}} />);
    expect(screen.getByTestId('category-title')).toBeInTheDocument();
    expect(screen.getByTestId('category-slug')).toBeInTheDocument();
    fireEvent.submit(screen.getByTestId('category-title').closest('form')!);
    expect(post).toHaveBeenCalledWith('/agentic-cms-laravel-admin/categories/new');
  });

  it('edit: prefills and PUTs to /{id}/update', () => {
    const entity = { id: 7, title: 'City guides', slug: 'city-guides', description: 'x',
      parent_category_id: 5, meta_description: null, meta_keywords: null };
    render(<Form entity={entity} parent_options={parent_options} translation_links={{ Deutsch: 'agentic-cms-laravel-admin/categories/7/de' }} />);
    fireEvent.submit(screen.getByTestId('category-title').closest('form')!);
    expect(put).toHaveBeenCalledWith('/agentic-cms-laravel-admin/categories/7/update');
  });
});
