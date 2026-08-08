import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { Topbar } from './Topbar';

const can = {
  see_admin_panel: true, manage_posts: true, manage_pages: true, manage_services: true,
  manage_post_categories: true, manage_comments: true, manage_menus: true,
  manage_general_settings: true, manage_users: true, manage_user_roles: true,
};

const visit = vi.fn();

vi.mock('@inertiajs/react', () => ({
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  router: { visit: (...args: unknown[]) => visit(...args) },
  usePage: () => ({
    props: {
      auth: { user: null, can },
      locale: { current: 'en', available: { en: 'English', de: 'Deutsch', ru: 'Russian' } },
    },
  }),
}));

describe('Topbar', () => {
  it('renders the "?" fallback initials without crashing when auth.user is null', () => {
    render(<Topbar />);
    expect(screen.getByText('?')).toBeInTheDocument();
  });

  it('links to the public site home', () => {
    render(<Topbar />);
    expect(screen.getByText('View site').closest('a')).toHaveAttribute('href', '/');
  });

  it('offers a logout control that posts to /logout', () => {
    render(<Topbar />);
    const logout = screen.getByText('Log out');
    expect(logout).toHaveAttribute('href', '/logout');
    expect(logout).toHaveAttribute('method', 'post');
  });

  it('switches the admin language through the locale route', () => {
    render(<Topbar />);
    fireEvent.change(screen.getByLabelText('Language'), { target: { value: 'de' } });
    expect(visit).toHaveBeenCalledWith('/agentic-cms-laravel-admin/locale/de');
  });
});
