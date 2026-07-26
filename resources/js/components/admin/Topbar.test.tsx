import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { Topbar } from './Topbar';

const can = {
  see_admin_panel: true, manage_posts: true, manage_pages: true, manage_services: true,
  manage_post_categories: true, manage_comments: true, manage_menus: true,
  manage_general_settings: true, manage_users: true, manage_user_roles: true,
};

vi.mock('@inertiajs/react', () => ({
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  usePage: () => ({ props: { auth: { user: null, can } } }),
}));

describe('Topbar', () => {
  it('renders the "?" fallback initials without crashing when auth.user is null', () => {
    render(<Topbar />);
    expect(screen.getByText('?')).toBeInTheDocument();
  });
});
