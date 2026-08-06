import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { PreviewBanner } from './PreviewBanner';

vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

describe('PreviewBanner', () => {
  it('renders the noindex preview strip with the English fallback', () => {
    render(<PreviewBanner />);
    const banner = screen.getByTestId('preview-banner');
    expect(banner).toBeInTheDocument();
    expect(banner).toHaveTextContent(/not indexed/i);
  });
});
