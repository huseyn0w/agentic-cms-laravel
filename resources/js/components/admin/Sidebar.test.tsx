import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { Sidebar } from './Sidebar';

vi.mock('@inertiajs/react', () => ({
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  usePage: () => ({ url: '/agentic-cms-laravel-admin/categories', component: 'cpanel/categories/List' }),
}));

const can = (overrides = {}) => ({
  see_admin_panel: true, manage_posts: true, manage_pages: true, manage_services: true,
  manage_post_categories: true, manage_comments: true, manage_menus: true,
  manage_general_settings: true, manage_users: true, manage_user_roles: true, ...overrides,
});

describe('Sidebar', () => {
  it('renders the admin-sidebar testid and Dashboard/Categories/Users labels', () => {
    render(<Sidebar can={can()} />);
    expect(screen.getByTestId('admin-sidebar')).toBeInTheDocument();
    expect(screen.getByText('Dashboard')).toBeInTheDocument();
    expect(screen.getByText('Categories')).toBeInTheDocument();
    expect(screen.getByText('Users')).toBeInTheDocument();
  });

  it('hides items the user lacks permission for', () => {
    render(<Sidebar can={can({ manage_users: false })} />);
    expect(screen.queryByText('Users')).not.toBeInTheDocument();
    expect(screen.getByText('Categories')).toBeInTheDocument();
  });

  it('marks the active item from the current component', () => {
    render(<Sidebar can={can()} />);
    expect(screen.getByText('Categories').closest('a')).toHaveClass('admin-nav-active');
  });
});
