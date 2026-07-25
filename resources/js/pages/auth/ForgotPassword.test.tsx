import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    useForm: () => ({
        data: { email: '' },
        setData: vi.fn(),
        post: mocks.post,
        processing: false,
        errors: {},
    }),
    Head: () => null,
    Link: ({ children, href }: { children: React.ReactNode; href: string }) => <a href={href}>{children}</a>,
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

import ForgotPassword from './ForgotPassword';

describe('ForgotPassword page', () => {
    it('renders the email field and posts to /password/email', () => {
        render(<ForgotPassword status={null} />);

        expect(screen.getByText('custom-passwords.reset_page_headline')).toBeInTheDocument();
        expect(screen.getByText('custom-passwords.reset_password')).toBeInTheDocument();
        expect(screen.getByLabelText('custom-passwords.email', { exact: false })).toBeInTheDocument();
        expect(screen.getByText('custom-passwords.send_password_link')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'login.login' })).toHaveAttribute('href', '/login');
        expect(screen.queryByRole('status')).not.toBeInTheDocument();

        fireEvent.submit(screen.getByText('custom-passwords.send_password_link').closest('form')!);

        expect(mocks.post).toHaveBeenCalledWith('/password/email');
    });

    it('shows the status banner when a status message is present', () => {
        render(<ForgotPassword status="We have emailed your password reset link." />);

        expect(screen.getByRole('status')).toHaveTextContent('We have emailed your password reset link.');
    });
});
