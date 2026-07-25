import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    useForm: () => ({
        data: { name: '', email: '', username: '', password: '', password_confirmation: '' },
        setData: vi.fn(),
        post: mocks.post,
        processing: false,
        errors: {},
    }),
    Head: () => null,
    Link: ({ children, href }: { children: React.ReactNode; href: string }) => <a href={href}>{children}</a>,
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

import Register from './Register';

describe('Register page', () => {
    it('renders the registration fields and posts to /register', () => {
        render(<Register />);

        expect(screen.getByText('registration.register_page_headline')).toBeInTheDocument();
        expect(screen.getByLabelText('registration.name', { exact: false })).toBeInTheDocument();
        expect(screen.getByLabelText('registration.email', { exact: false })).toBeInTheDocument();
        expect(screen.getByLabelText('registration.username', { exact: false })).toBeInTheDocument();
        expect(screen.getByLabelText('registration.password', { exact: false })).toBeInTheDocument();
        expect(screen.getByLabelText('registration.confirm_password', { exact: false })).toBeInTheDocument();
        expect(screen.getByText('registration.register_btn')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'login.login' })).toHaveAttribute('href', '/login');

        fireEvent.submit(screen.getByText('registration.register_btn').closest('form')!);

        expect(mocks.post).toHaveBeenCalledWith('/register');
    });
});
