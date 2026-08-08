import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { Sidebar } from './Sidebar';

vi.mock('@inertiajs/react', () => ({
  // Expose Inertia's prefetch/cacheFor as clean data attributes so tests can
  // lock the instant-nav strategy without leaking unknown props onto the <a>.
  Link: ({ children, prefetch, cacheFor, ...p }: any) => (
    <a data-prefetch={String(prefetch)} data-cache-for={String(cacheFor)} {...p}>{children}</a>
  ),
  usePage: () => ({
    url: '/agentic-cms-laravel-admin/categories',
    component: 'cpanel/categories/List',
    props: { cms: { version: '1.0.0' } },
  }),
}));

const can = (overrides = {}) => ({
  see_admin_panel: true, manage_posts: true, manage_pages: true, manage_services: true,
  manage_post_categories: true, manage_comments: true, manage_menus: true,
  manage_general_settings: true, manage_users: true, manage_user_roles: true,
  manage_newsletter: true, manage_messages: true, manage_content: true, manage_updates: true, ...overrides,
});

describe('Sidebar', () => {
  it('renders the admin-sidebar testid and Dashboard/Categories/Users labels', () => {
    render(<Sidebar can={can()} />);
    expect(screen.getByTestId('admin-sidebar')).toBeInTheDocument();
    expect(screen.getByText('Dashboard')).toBeInTheDocument();
    expect(screen.getByText('Categories')).toBeInTheDocument();
    expect(screen.getByText('Users')).toBeInTheDocument();
    expect(screen.getByText('Roles')).toBeInTheDocument();
  });

  it('hides items the user lacks permission for', () => {
    render(<Sidebar can={can({ manage_users: false })} />);
    expect(screen.queryByText('Users')).not.toBeInTheDocument();
    expect(screen.getByText('Categories')).toBeInTheDocument();
  });

  it('marks the active item as a solid pill from the current component', () => {
    render(<Sidebar can={can()} />);
    const link = screen.getByText('Categories').closest('a')!;
    expect(link).toHaveClass('bg-primary');
    expect(link).toHaveClass('text-primary-contrast');
  });

  it('hides the group label when every item in the group is filtered out', () => {
    render(<Sidebar can={can({ manage_general_settings: false, manage_users: false, manage_user_roles: false, manage_newsletter: false, manage_messages: false, manage_updates: false })} />);
    expect(screen.queryByText('Settings')).not.toBeInTheDocument();
    expect(screen.getByText('Content')).toBeInTheDocument();
  });

  it('shows the core version from the shared cms prop', () => {
    render(<Sidebar can={can()} />);
    expect(screen.getByTestId('admin-version')).toHaveTextContent('v1.0.0');
  });

  it('nav links prefetch on mount and cache for 15s (instant navigation)', () => {
    render(<Sidebar can={can()} />);
    const link = screen.getByText('Categories').closest('a')!;
    expect(link).toHaveAttribute('data-prefetch', 'mount');
    expect(link).toHaveAttribute('data-cache-for', '15s');
  });
});
