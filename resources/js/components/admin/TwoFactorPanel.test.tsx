import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
  useForm: (initial: any) => ({ data: initial, setData: () => {}, post: () => {}, delete: () => {}, processing: false, errors: {} }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

import { TwoFactorPanel } from './TwoFactorPanel';

const base = { is_self: true, setup: null, recovery_codes: null };

describe('TwoFactorPanel', () => {
  it('shows the enable action when disabled', () => {
    render(<TwoFactorPanel two_factor={{ ...base, status: 'disabled' }} />);
    expect(screen.getByTestId('twofactor-enable')).toBeInTheDocument();
  });

  it('shows the QR and a confirm input when pending', () => {
    render(<TwoFactorPanel two_factor={{ ...base, status: 'pending', setup: { secret: 'ABCDEF', qr_svg: '<svg></svg>' } }} />);
    expect(screen.getByTestId('twofactor-confirm-code')).toBeInTheDocument();
    expect(screen.getByText('ABCDEF')).toBeInTheDocument();
  });

  it('shows disable + recovery when enabled', () => {
    render(<TwoFactorPanel two_factor={{ ...base, status: 'enabled' }} />);
    expect(screen.getByTestId('twofactor-disable')).toBeInTheDocument();
  });

  it('renders the recovery codes when present', () => {
    render(<TwoFactorPanel two_factor={{ ...base, status: 'enabled', recovery_codes: ['aa-bb', 'cc-dd'] }} />);
    expect(screen.getByTestId('twofactor-recovery')).toBeInTheDocument();
    expect(screen.getByText('aa-bb')).toBeInTheDocument();
  });

  it('renders nothing for another user', () => {
    const { container } = render(<TwoFactorPanel two_factor={{ ...base, is_self: false, status: 'disabled' }} />);
    expect(container).toBeEmptyDOMElement();
  });
});
