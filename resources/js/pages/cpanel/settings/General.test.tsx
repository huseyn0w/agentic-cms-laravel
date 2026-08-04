import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const post = vi.fn();
const setData = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  useForm: (initial: any) => ({ data: initial, errors: {}, processing: false, setData, post }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import General from './General';

const entity = {
  website_name: 'My Site', tagline: 'Tag', contact_email: 'a@b.io',
  membership: true, email_verification: false,
  active_template_name: 'default', posts_per_page: 10, comments_per_page: 5,
};
const props = (over = {}) => ({ general_settings: entity, templates: ['default', 'blog'], ...over });

describe('General settings', () => {
  it('prefills fields and toggles, posts to the general-settings endpoint', () => {
    render(<General {...props()} />);
    expect(screen.getByTestId('website_name')).toHaveValue('My Site');
    expect(screen.getByLabelText('membership')).toBeChecked();
    expect(screen.getByLabelText('email_verification')).not.toBeChecked();

    fireEvent.submit(screen.getByTestId('website_name').closest('form')!);
    expect(post).toHaveBeenCalledWith('/agentic-cms-laravel-admin/general-settings', expect.anything());
  });
});
