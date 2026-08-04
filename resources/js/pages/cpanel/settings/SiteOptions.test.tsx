import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const post = vi.fn();
const setData = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  useForm: (initial: any) => ({ data: initial, errors: {}, processing: false, setData, post }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@/components/MediaField', () => ({ MediaField: ({ label }: any) => <div data-testid="media-field">{label}</div> }));
import SiteOptions from './SiteOptions';

const entity = {
  logo_url: 'https://x.io/logo.png', copyright: 'C 2026',
  linkedin_url: 'https://linkedin.com/in/x', github_url: 'https://github.com/x',
};
const props = (over = {}) => ({ site_options: entity, ...over });

describe('Site options', () => {
  it('prefills fields, renders the logo media field, posts to the site-options endpoint', () => {
    render(<SiteOptions {...props()} />);
    expect(screen.getByTestId('copyright')).toHaveValue('C 2026');
    expect(screen.getByTestId('media-field')).toBeInTheDocument();

    fireEvent.submit(screen.getByTestId('copyright').closest('form')!);
    expect(post).toHaveBeenCalledWith('/agentic-cms-laravel-admin/site-options', expect.anything());
  });
});
