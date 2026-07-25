import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    useForm: () => ({
        data: { email: '', password: '', remember: false },
        setData: vi.fn(),
        post: vi.fn(),
        processing: false,
        errors: {},
    }),
    Head: () => null,
    Link: ({ children, href }: { children: React.ReactNode; href: string }) => <a href={href}>{children}</a>,
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

import Login from './Login';

describe('Login page', () => {
    it('renders the login testids and the google button', () => {
        render(<Login canResetPassword membershipEnabled status={null} />);
        expect(screen.getByTestId('login-username')).toBeInTheDocument();
        expect(screen.getByTestId('login-password')).toBeInTheDocument();
        expect(screen.getByTestId('login-submit')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /google/i })).toHaveAttribute('href', '/login/google');
    });
});
