import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { AdminLayout } from './AdminLayout';

const shared = {
  auth: { user: { id: 1, name: 'Elman Admin', email: 'a@b.com' },
    can: { see_admin_panel: true, manage_posts: true, manage_pages: true, manage_services: true,
      manage_post_categories: true, manage_comments: true, manage_menus: true,
      manage_general_settings: true, manage_users: true, manage_user_roles: true, manage_newsletter: true } },
  locale: { current: 'en', available: { en: 'English', de: 'Deutsch', ru: 'Russian' } },
  flash: { success: 'Saved' },
};

vi.mock('@inertiajs/react', () => ({
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  router: { visit: () => undefined },
  usePage: () => ({ props: shared, url: '/agentic-cms-laravel-admin', component: 'cpanel/Dashboard' }),
}));

describe('AdminLayout', () => {
  it('renders shell, avatar initials, flash, and children', () => {
    render(<AdminLayout breadcrumb={<span>Admin</span>}>content</AdminLayout>);
    expect(screen.getByTestId('admin-sidebar')).toBeInTheDocument();
    expect(screen.getByText('EL')).toBeInTheDocument();
    expect(screen.getByText('Saved')).toBeInTheDocument();
    expect(screen.getByText('content')).toBeInTheDocument();
  });
});
