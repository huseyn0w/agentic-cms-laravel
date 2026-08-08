import type { Ability } from '@/lib/types';

export type { Ability };

export interface NavItem {
  key: string;          // i18n key, e.g. 'cpanel/menu.categories'
  fallback: string;     // English fallback if key missing
  href: string;
  component: string;    // Inertia component prefix to match for "active"
  ability: Ability;
}
export interface NavGroup { labelKey: string; fallback: string; items: NavItem[] }

const A = '/agentic-cms-laravel-admin';

export const NAV_GROUPS: NavGroup[] = [
  { labelKey: 'cpanel/menu.main', fallback: 'Main', items: [
    { key: 'cpanel/menu.dashboard', fallback: 'Dashboard', href: `${A}`, component: 'cpanel/Dashboard', ability: 'see_admin_panel' },
  ]},
  { labelKey: 'cpanel/menu.content', fallback: 'Content', items: [
    { key: 'cpanel/menu.posts', fallback: 'Posts', href: `${A}/posts`, component: 'cpanel/posts', ability: 'manage_posts' },
    { key: 'cpanel/menu.pages', fallback: 'Pages', href: `${A}/pages`, component: 'cpanel/pages', ability: 'manage_pages' },
    { key: 'cpanel/menu.services', fallback: 'Services', href: `${A}/services`, component: 'cpanel/services', ability: 'manage_services' },
    { key: 'cpanel/menu.categories', fallback: 'Categories', href: `${A}/categories`, component: 'cpanel/categories', ability: 'manage_post_categories' },
    { key: 'cpanel/menu.comments', fallback: 'Comments', href: `${A}/comments`, component: 'cpanel/comments', ability: 'manage_comments' },
    { key: 'cpanel/menu.menus', fallback: 'Menus', href: `${A}/menus`, component: 'cpanel/menus', ability: 'manage_menus' },
  ]},
  { labelKey: 'cpanel/menu.settings', fallback: 'Settings', items: [
    { key: 'cpanel/menu.settings', fallback: 'Settings', href: `${A}/general-settings`, component: 'cpanel/settings', ability: 'manage_general_settings' },
    { key: 'cpanel/menu.security', fallback: 'Security', href: `${A}/security`, component: 'cpanel/security', ability: 'manage_general_settings' },
    { key: 'cpanel/menu.users', fallback: 'Users', href: `${A}/users`, component: 'cpanel/users', ability: 'manage_users' },
    { key: 'cpanel/menu.roles', fallback: 'Roles', href: `${A}/roles`, component: 'cpanel/roles', ability: 'manage_user_roles' },
    { key: 'cpanel/menu.newsletter', fallback: 'Newsletter', href: `${A}/newsletter`, component: 'cpanel/newsletter', ability: 'manage_newsletter' },
    { key: 'cpanel/menu.mcp', fallback: 'MCP', href: `${A}/mcp`, component: 'cpanel/mcp', ability: 'manage_general_settings' },
    { key: 'cpanel/menu.updates', fallback: 'Updates', href: `${A}/updates`, component: 'cpanel/updates', ability: 'manage_updates' },
  ]},
];
