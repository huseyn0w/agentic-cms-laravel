import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ post: vi.fn(), useForm: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    useForm: (initial: Record<string, unknown>) => {
        mocks.useForm(initial);
        return {
            data: initial,
            setData: vi.fn(),
            post: mocks.post,
            processing: false,
            errors: {},
        };
    },
    Head: () => null,
    Link: ({ children, href }: { children: React.ReactNode; href: string }) => <a href={href}>{children}</a>,
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

import ResetPassword from './ResetPassword';

describe('ResetPassword page', () => {
    it('seeds the form with token/email and posts to /password/reset', () => {
        render(<ResetPassword token="abc123" email="jane@example.com" />);

        expect(mocks.useForm).toHaveBeenCalledWith({
            token: 'abc123',
            email: 'jane@example.com',
            password: '',
            password_confirmation: '',
        });

        const emailInput = screen.getByLabelText('custom-passwords.email', { exact: false }) as HTMLInputElement;
        expect(emailInput.value).toBe('jane@example.com');
        expect(screen.getByLabelText('custom-passwords.password', { exact: false })).toBeInTheDocument();
        expect(screen.getByLabelText('custom-passwords.confirm_password', { exact: false })).toBeInTheDocument();
        expect(screen.getByText('custom-passwords.reset_password_btn')).toBeInTheDocument();

        fireEvent.submit(screen.getByText('custom-passwords.reset_password_btn').closest('form')!);

        expect(mocks.post).toHaveBeenCalledWith('/password/reset');
    });
});
