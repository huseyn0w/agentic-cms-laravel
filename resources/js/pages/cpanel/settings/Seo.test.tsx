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
import Seo from './Seo';

const entity = {
  title_separator: '|', default_meta_description: 'd', default_og_image: '',
  og_site_name: 'Site', twitter_handle: '@x', google_site_verification: '',
  bing_site_verification: '', ga4_measurement_id: 'G-1', gtm_container_id: '',
  discourage_search_engines: false, sitemap_enabled: true, robots_extra: '',
};

describe('SEO settings', () => {
  it('prefills fields and toggles, posts to the seo-settings endpoint', () => {
    render(<Seo seo_settings={entity} />);
    expect(screen.getByTestId('title_separator')).toHaveValue('|');
    expect(screen.getByLabelText('sitemap_enabled')).toBeChecked();
    expect(screen.getByLabelText('discourage_search_engines')).not.toBeChecked();

    fireEvent.submit(screen.getByTestId('title_separator').closest('form')!);
    expect(post).toHaveBeenCalledWith('/agentic-cms-laravel-admin/seo-settings', expect.anything());
  });

  it('no longer renders the AI-crawler toggles (moved to the AEO tab)', () => {
    render(<Seo seo_settings={entity} />);
    expect(screen.queryByLabelText('ai_crawler_gptbot')).not.toBeInTheDocument();
  });
});
