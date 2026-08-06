import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  useForm: () => ({ data: { code: '' }, setData: () => {}, post: () => {}, processing: false, errors: {} }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
vi.mock('@/layouts/AuthLayout', () => ({ AuthLayout: ({ children }: any) => <div>{children}</div> }));

import Challenge from './TwoFactorChallenge';

describe('TwoFactorChallenge', () => {
  it('renders the code input and submit', () => {
    render(<Challenge />);
    expect(screen.getByTestId('twofactor-challenge-code')).toBeInTheDocument();
    expect(screen.getByTestId('twofactor-challenge-submit')).toBeInTheDocument();
  });
});
