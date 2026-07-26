import { render } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { FlashBanner } from './FlashBanner';

let flash: { success?: string | null; status?: string | null; error?: string | null } = {};

vi.mock('@inertiajs/react', () => ({
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  usePage: () => ({ props: { flash } }),
}));

describe('FlashBanner', () => {
  it('renders nothing when flash has no success/status/error', () => {
    flash = {};
    const { container } = render(<FlashBanner />);
    expect(container).toBeEmptyDOMElement();
  });

  it('uses role="alert" for an error message', () => {
    flash = { error: 'Something broke' };
    const { getByRole, queryByRole } = render(<FlashBanner />);
    expect(getByRole('alert')).toHaveTextContent('Something broke');
    expect(queryByRole('status')).not.toBeInTheDocument();
  });

  it('uses role="status" for a success message', () => {
    flash = { success: 'Saved' };
    const { getByRole, queryByRole } = render(<FlashBanner />);
    expect(getByRole('status')).toHaveTextContent('Saved');
    expect(queryByRole('alert')).not.toBeInTheDocument();
  });
});
