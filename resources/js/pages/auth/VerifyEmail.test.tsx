import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    useForm: () => ({
        data: {},
        setData: vi.fn(),
        post: mocks.post,
        processing: false,
        errors: {},
    }),
    Head: () => null,
    Link: ({ children, href }: { children: React.ReactNode; href: string }) => <a href={href}>{children}</a>,
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

import VerifyEmail from './VerifyEmail';

describe('VerifyEmail page', () => {
    it('renders the verification copy and posts to /email/resend on resend', () => {
        render(<VerifyEmail status={null} />);

        expect(screen.getByText('email.verify_page_headline')).toBeInTheDocument();
        expect(screen.getByText(/email.check_email/)).toBeInTheDocument();
        expect(screen.getByText(/email.not_receive_email/)).toBeInTheDocument();
        expect(screen.queryByRole('status')).not.toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'email.request_other_email' }));

        expect(mocks.post).toHaveBeenCalledWith('/email/resend');
    });

    it('shows the fresh-link banner when status is resent', () => {
        render(<VerifyEmail status="resent" />);

        expect(screen.getByRole('status')).toHaveTextContent('email.fresh_link');
    });
});
