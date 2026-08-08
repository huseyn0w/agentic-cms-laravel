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
import Aeo from './Aeo';

const catalog = [
  { key: 'gptbot', label: 'GPTBot' },
  { key: 'claudebot', label: 'ClaudeBot' },
];

describe('AEO settings', () => {
  it('renders a toggle per catalog bot reflecting the allow map', () => {
    render(<Aeo ai_crawler_catalog={catalog} ai_crawlers={{ gptbot: true, claudebot: false }} />);
    expect(screen.getByLabelText('ai_crawler_gptbot')).toBeChecked();
    expect(screen.getByLabelText('ai_crawler_claudebot')).not.toBeChecked();
  });

  it('flips a toggle and posts to the aeo-settings endpoint', () => {
    render(<Aeo ai_crawler_catalog={catalog} ai_crawlers={{ gptbot: true, claudebot: false }} />);
    fireEvent.click(screen.getByLabelText('ai_crawler_claudebot'));
    expect(setData).toHaveBeenCalledWith('ai_crawlers', { gptbot: true, claudebot: true });

    fireEvent.submit(screen.getByLabelText('ai_crawler_gptbot').closest('form')!);
    expect(post).toHaveBeenCalledWith('/agentic-cms-laravel-admin/aeo-settings', expect.anything());
  });
});
