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
vi.mock('@/components/RichText', () => ({ RichText: ({ id }: any) => <div data-testid={`richtext-${id}`} /> }));
vi.mock('@/components/admin/CustomFieldsBuilder', () => ({ CustomFieldsBuilder: () => <div data-testid="custom-fields" /> }));
vi.mock('@/lib/lfm', () => ({ useLfmPicker: () => ({ open: vi.fn() }) }));
import Form from './Form';

const templates = [{ value: 'default', label: 'Default' }, { value: 'home', label: 'Home' }];
const authors = [{ id: 1, username: 'admin' }, { id: 2, username: 'editor' }];
const categories_list = [{ category_id: 1, title: 'News' }];

describe('Pages Form', () => {
  it('new: renders fields, template + builder, and posts to /new', () => {
    render(<Form entity={null} templates={templates} authors={authors} categories_list={categories_list} translation_links={{}} />);
    expect(screen.getByTestId('page-title')).toBeInTheDocument();
    expect(screen.getByTestId('page-slug')).toBeInTheDocument();
    expect(screen.getByTestId('page-submit')).toBeInTheDocument();
    expect(screen.getByTestId('richtext-content')).toBeInTheDocument();
    expect(screen.getByTestId('custom-fields')).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Home' })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'editor' })).toBeInTheDocument();

    fireEvent.submit(screen.getByTestId('page-title').closest('form')!);
    expect(transform).toHaveBeenCalled();
    expect(post).toHaveBeenCalledWith('/agentic-cms-laravel-admin/pages/new');
  });

  it('edit: prefills title and PUTs to /{id}/update', () => {
    const entity = {
      id: 4, title: 'About', slug: 'about', content: '<p>c</p>', author_id: 2,
      meta_keywords: 'k', meta_description: 'd', canonical_url: '', meta_noindex: false,
      status: 1, template: 'default', updated_at: '2026-01-01 10:00:00', custom_fields: {},
    };
    render(<Form entity={entity} templates={templates} authors={authors} categories_list={categories_list} translation_links={{}} />);
    expect(screen.getByTestId('page-title')).toHaveValue('About');
    fireEvent.submit(screen.getByTestId('page-title').closest('form')!);
    expect(put).toHaveBeenCalledWith('/agentic-cms-laravel-admin/pages/4/update');
  });
});
