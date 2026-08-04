import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const post = vi.fn();
const put = vi.fn();
const setData = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  usePage: () => ({ props: { locale: { current: 'en' } } }),
  useForm: (initial: any) => ({ data: initial, errors: {}, processing: false, setData, post, put }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@/components/RichText', () => ({ RichText: ({ id }: any) => <div data-testid={`richtext-${id}`} /> }));
vi.mock('@/components/MediaField', () => ({ MediaField: ({ label }: any) => <div data-testid="media-field">{label}</div> }));
vi.mock('@/lib/lfm', () => ({ useLfmPicker: () => ({ open: vi.fn() }) }));
import Form from './Form';

describe('Services Form', () => {
  it('new: renders title/slug/submit, content editor + thumbnail, and posts to /new', () => {
    render(<Form entity={null} translation_links={{}} />);
    expect(screen.getByTestId('service-title')).toBeInTheDocument();
    expect(screen.getByTestId('service-slug')).toBeInTheDocument();
    expect(screen.getByTestId('service-submit')).toBeInTheDocument();
    expect(screen.getByTestId('richtext-content')).toBeInTheDocument();
    expect(screen.getByTestId('media-field')).toBeInTheDocument();

    fireEvent.submit(screen.getByTestId('service-title').closest('form')!);
    expect(post).toHaveBeenCalledWith('/agentic-cms-laravel-admin/services/new');
  });

  it('edit: prefills title and PUTs to /{id}/update', () => {
    const entity = {
      id: 5, title: 'Consulting', slug: 'consulting', icon: 'star', excerpt: 'e', content: '<p>c</p>',
      thumbnail: '', meta_keywords: 'k', meta_description: 'd', canonical_url: '', meta_noindex: false,
      sort_order: 2, status: 1,
    };
    render(<Form entity={entity} translation_links={{}} />);
    expect(screen.getByTestId('service-title')).toHaveValue('Consulting');
    fireEvent.submit(screen.getByTestId('service-title').closest('form')!);
    expect(put).toHaveBeenCalledWith('/agentic-cms-laravel-admin/services/5/update');
  });
});
