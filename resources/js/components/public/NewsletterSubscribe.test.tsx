import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { NewsletterSubscribe } from './NewsletterSubscribe';

const post = vi.hoisted(() => vi.fn());
let flash: Record<string, unknown> = {};

vi.mock('@inertiajs/react', () => ({
  usePage: () => ({ props: { flash } }),
  useForm: () => ({
    data: { email: '', website: '' },
    setData: vi.fn(),
    post,
    processing: false,
    reset: vi.fn(),
    errors: {},
  }),
}));

vi.mock('react-i18next', () => ({
  useTranslation: () => ({ t: (k: string) => k }),
}));

describe('NewsletterSubscribe', () => {
  beforeEach(() => {
    post.mockClear();
    flash = {};
  });

  it('renders the form and posts to the subscribe route on submit', () => {
    render(<NewsletterSubscribe />);
    const form = screen.getByTestId('newsletter-form');
    fireEvent.submit(form);
    expect(post).toHaveBeenCalledWith('/newsletter/subscribe', expect.any(Object));
  });

  it('shows the thank-you line when flash.newsletter_status is submitted', () => {
    flash = { newsletter_status: 'submitted' };
    render(<NewsletterSubscribe />);
    expect(screen.getByTestId('newsletter-submitted')).toBeInTheDocument();
    expect(screen.queryByTestId('newsletter-form')).not.toBeInTheDocument();
  });
});
