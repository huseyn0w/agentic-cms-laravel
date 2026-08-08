import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { UpdateBanner } from './UpdateBanner';

const mockPage = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  usePage: () => mockPage(),
}));

describe('UpdateBanner', () => {
  it('renders with the available version when the shared prop is set', () => {
    mockPage.mockReturnValue({ props: { cms: { version: '1.0.0', updateAvailable: '2.3.0' } } });
    render(<UpdateBanner />);
    expect(screen.getByTestId('update-banner')).toBeInTheDocument();
    expect(screen.getByTestId('update-banner')).toHaveTextContent('v2.3.0');
  });

  it('renders nothing when no update is available', () => {
    mockPage.mockReturnValue({ props: { cms: { version: '1.0.0', updateAvailable: null } } });
    const { container } = render(<UpdateBanner />);
    expect(container).toBeEmptyDOMElement();
  });

  it('renders nothing when the cms prop is absent (non-admin)', () => {
    mockPage.mockReturnValue({ props: {} });
    const { container } = render(<UpdateBanner />);
    expect(container).toBeEmptyDOMElement();
  });
});
