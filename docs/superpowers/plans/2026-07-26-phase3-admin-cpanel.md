# Phase 3 — Admin cpanel on Inertia (shell + Dashboard + Categories) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the admin shell, Dashboard home, and the Categories resource from Blade to Inertia+React, on the monochrome (Vercel) palette, with instant client-side navigation.

**Architecture:** Strangler — Inertia pages render alongside the still-live Blade admin. A persistent `AdminLayout` (Inertia `Page.layout`) holds the sidebar/topbar/flash so navigation swaps only the content region. Thin controllers return `Inertia::render(...)` with the same data the Blade views received; Service→Repository layering is untouched. Design tokens in `resources/css/tokens.css` are redefined to monochrome, flipping the whole system (admin + Phase 2 auth) at once.

**Tech Stack:** Laravel 12 / PHP 8.3, inertiajs/inertia-laravel, React 19 + TypeScript, Vite, Tailwind (semantic token bridge), react-i18next, Vitest + RTL + jsdom, Pest (Feature `AssertableInertia`, browser).

## Global Constraints

Every task's requirements implicitly include this section. Values are copied verbatim from the design spec (`docs/superpowers/specs/2026-07-26-phase3-admin-cpanel-design.md`).

- **Monochrome palette (light), exact hexes:** `--bg:#ffffff`, `--surface:#ffffff`, `--surface-2:#fafafa`, `--surface-3:#f4f4f5`, `--text:#0a0a0a`, `--text-muted:#3f3f46`, `--text-subtle:#71717a`, `--text-faint:#a1a1aa`, `--primary:#0a0a0a`, `--primary-hover:#242427`, `--primary-contrast:#ffffff`, `--accent:#0a0a0a`, `--border:#e4e4e7`, `--border-strong:#d4d4d8`, `--ring:#0a0a0a`, `--error:#dc2626`, `--success:#15803d`. Dark: near-black surfaces `#0a0a0a`/`#17171a`/`#202024`, zinc borders `#2a2a2e`/`#3a3a40`, white becomes accent (`--primary:#ffffff`, `--primary-contrast:#0a0a0a`).
- **Red/green are semantic only** — delete/error/success. Never decoration. No violet/blue/terracotta anywhere.
- **Premium edges (NOT flat 1px grey):** gradient-bevel border `linear-gradient(180deg, rgba(9,9,11,.11), rgba(9,9,11,.045) 40%, rgba(9,9,11,.03))` via padding-box/border-box; floating shadow `0 1px 2px rgba(9,9,11,.04), 0 10px 30px -12px rgba(9,9,11,.14)`; inset top highlight `inset 0 1px 0 rgba(255,255,255,.7)`; faint table separators `rgba(9,9,11,.06)`.
- **Backend contracts preserved:** route names and controller method names unchanged; only `view(...)` → `Inertia::render(...)`. Controller → Service → Repository layering intact (`tests/Arch/LayeringTest.php` must stay green — controllers never touch repositories). Reuse `CategoryRequest` / `CategoryListRequest` unchanged.
- **Permission gating** reads shared `auth.can` (already provided by `HandleInertiaRequests`), keyed on POLICY ability names: `manage_post_categories`, `manage_posts`, `manage_pages`, `manage_services`, `manage_comments`, `manage_menus`, `manage_user_roles`, `manage_users`, `manage_general_settings`, `see_admin_panel`.
- **Preserve testids:** `data-testid="admin-sidebar"` (sidebar), `data-testid="bulk-delete-confirm"` (bulk delete confirm), Phase 2 login testids untouched. New category testids follow the `<page>-<field>` convention: `category-title`, `category-slug`, `category-submit`.
- **Content is translatable** → all category content (title/slug/description/meta) writes to `category_translations`. slug is locale-scoped. Locale resolved server-side from session; no hidden locale field in forms.
- **i18n:** use `useTranslation()` from react-i18next, `t('dotted.key')`, keys verbatim from `resources/lang/**`. Every user-facing string is a translation key.
- **Process:** TDD (failing test first), frequent commits, DRY, YAGNI.

---

## File Structure

**Create:**
- `resources/js/layouts/AdminLayout.tsx` — persistent admin shell (sidebar + topbar + flash).
- `resources/js/components/admin/Sidebar.tsx` — permission-gated grouped nav.
- `resources/js/components/admin/Topbar.tsx` — breadcrumb + search + icons + avatar.
- `resources/js/components/admin/FlashBanner.tsx` — renders shared `flash`.
- `resources/js/lib/theme.ts` — `useThemeBootstrap()` hook (localStorage/`matchMedia` → `.dark`), extracted for reuse.
- `resources/js/lib/admin-nav.ts` — the sidebar nav model (groups → items with `ability` + `href` + `routeName`).
- `resources/js/pages/cpanel/Dashboard.tsx`
- `resources/js/pages/cpanel/categories/List.tsx`
- `resources/js/pages/cpanel/categories/Form.tsx`
- Co-located tests: `AdminLayout.test.tsx`, `Sidebar.test.tsx`, `Dashboard.test.tsx`, `categories/List.test.tsx`, `categories/Form.test.tsx`.
- `tests/Feature/CPanel/DashboardInertiaTest.php`, `tests/Feature/CPanel/CategoryInertiaRenderTest.php`.
- `resources/css/admin.css` additions (admin component classes) — or a new `resources/css/admin-components.css` imported by the admin CSS entry.

**Modify:**
- `resources/css/tokens.css` — monochrome values (Task 1).
- `tailwind.config.js` — add `surface-3`, `faint` bridge entries (Task 1).
- `app/Http/Controllers/CPanel/CPanelHomeController.php` — `view` → `Inertia::render` (Task 3).
- `app/Http/Controllers/CPanel/CPanelCategoryController.php` — `index`, `addCategory`, `edit` → `Inertia::render` (Tasks 4-5).
- `tests/Feature/Phase5AdminRenderTest.php` — convert `cpanel_home` / `cpanel_category_list` / `cpanel_add_new_category` assertions to AssertableInertia (Tasks 3-5).

**Untouched (stay green):** `tests/Feature/Admin/CategoryCrudTest.php`, `CategoryTreeTest.php` (transport-agnostic, assert DB/redirects); `CPanelCategoryService`, `CPanelCategoryRepository`, `CategoryRequest`, `CategoryListRequest`; `deleteAjax` endpoint (row-delete reroutes to bulk endpoint instead).

---

## Task 1: Monochrome token unification

**Files:**
- Modify: `resources/css/tokens.css`
- Modify: `tailwind.config.js` (add `surface-3`, `faint`)
- Create: `resources/css/admin-components.css` (premium edge component classes)
- Test: `resources/js/components/admin/tokens.test.tsx` (bridge smoke test)

**Interfaces:**
- Produces: Tailwind color names `bg-surface-3`, `text-faint` resolving to the new CSS vars; CSS classes `.admin-card`, `.admin-bevel`, `.admin-nav-active` for premium edges (consumed by Tasks 2-5).

- [ ] **Step 1: Write the failing test**

Create `resources/js/components/admin/tokens.test.tsx`. A minimal component that uses the new bridge names must render without Tailwind flagging them as unknown; assert the class is applied (the real value check happens in the browser suite — see Step 6).

```tsx
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

function Swatch() {
  return <div data-testid="swatch" className="bg-surface-3 text-faint border-strong" />;
}

describe('monochrome token bridge', () => {
  it('exposes surface-3 and faint bridge names', () => {
    render(<Swatch />);
    const el = screen.getByTestId('swatch');
    expect(el).toHaveClass('bg-surface-3');
    expect(el).toHaveClass('text-faint');
    expect(el).toHaveClass('border-strong');
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npx vitest run resources/js/components/admin/tokens.test.tsx`
Expected: FAIL (file resolves but the bridge names don't exist yet is not a runtime failure — the failure here is the file/import path). If it passes trivially, proceed; the real gate is Step 5 (existing suite green) + Step 6 (browser colors). Note: this test mainly locks the class names in place.

- [ ] **Step 3: Rewrite `resources/css/tokens.css` to monochrome**

```css
/* Canonical design tokens — monochrome (Vercel). Single source of truth. */
:root {
  --bg:#ffffff; --surface:#ffffff; --surface-2:#fafafa; --surface-3:#f4f4f5;
  --text:#0a0a0a; --text-muted:#3f3f46; --text-subtle:#71717a; --text-faint:#a1a1aa;
  --primary:#0a0a0a; --primary-hover:#242427; --primary-contrast:#ffffff;
  --accent:#0a0a0a; --border:#e4e4e7; --border-strong:#d4d4d8; --ring:#0a0a0a;
  --success:#15803d; --success-bg:#e7f6ec; --warning:#a9701a; --warning-bg:#f6eedd;
  --error:#dc2626; --error-bg:#fbeaea;
  /* radius */
  --radius-sm:6px; --radius-md:10px; --radius-lg:16px; --radius-xl:24px; --radius-full:9999px;
  /* spacing */
  --space-1:4px; --space-2:8px; --space-3:12px; --space-4:16px; --space-5:24px;
  --space-6:32px; --space-7:48px; --space-8:64px; --space-9:96px; --space-10:128px;
  /* motion */
  --ease-out:cubic-bezier(0.16,1,0.3,1); --ease-in-out:cubic-bezier(0.65,0,0.35,1);
  --dur-fast:120ms; --dur-base:200ms; --dur-slow:320ms;
  /* containers */
  --container-prose:720px; --container-content:1080px; --container-wide:1280px;
}
.dark {
  --bg:#0a0a0a; --surface:#0a0a0a; --surface-2:#17171a; --surface-3:#202024;
  --text:#fafafa; --text-muted:#a1a1aa; --text-subtle:#71717a; --text-faint:#52525b;
  --primary:#ffffff; --primary-hover:#e4e4e7; --primary-contrast:#0a0a0a;
  --accent:#ffffff; --border:#2a2a2e; --border-strong:#3a3a40; --ring:#ffffff;
  --success:#4ade80; --success-bg:#12251a; --warning:#d6a24c; --warning-bg:#2a2113;
  --error:#f87171; --error-bg:#2a1513;
}
```

- [ ] **Step 4: Add bridge names + premium edge classes**

In `tailwind.config.js` `theme.extend.colors`, add under the existing entries:
```js
'surface-3': 'var(--surface-3)',
faint: 'var(--text-faint)',
```

Create `resources/css/admin-components.css` and `@import` it from the admin CSS entry (`resources/css/admin.css`, after the tokens import):
```css
/* Premium monochrome edges — Phase 3 admin. */
@layer components {
  .admin-card {
    border: 1px solid transparent; border-radius: 13px;
    background:
      linear-gradient(var(--surface), var(--surface)) padding-box,
      linear-gradient(180deg, rgba(9,9,11,.11), rgba(9,9,11,.045) 40%, rgba(9,9,11,.03)) border-box;
    box-shadow: 0 1px 2px rgba(9,9,11,.04), 0 10px 30px -12px rgba(9,9,11,.14), inset 0 1px 0 rgba(255,255,255,.7);
  }
  .admin-bevel {
    border: 1px solid transparent;
    background:
      linear-gradient(#fff,#fbfbfc) padding-box,
      linear-gradient(180deg, rgba(9,9,11,.11), rgba(9,9,11,.045) 40%, rgba(9,9,11,.03)) border-box;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
  }
  .admin-nav-active {
    background:
      linear-gradient(#fff,#fff) padding-box,
      linear-gradient(180deg, rgba(9,9,11,.11), rgba(9,9,11,.045) 40%, rgba(9,9,11,.03)) border-box;
    border: 1px solid transparent;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.7), 0 1px 2px rgba(9,9,11,.06);
  }
  .dark .admin-card, .dark .admin-bevel, .dark .admin-nav-active {
    background: linear-gradient(var(--surface-2), var(--surface-2)) padding-box,
                linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.03)) border-box;
    box-shadow: 0 1px 2px rgba(0,0,0,.4), 0 10px 30px -12px rgba(0,0,0,.6);
  }
  .admin-sep { border-color: rgba(9,9,11,.06); }
}
```

- [ ] **Step 5: Run the full frontend suite — must stay green**

Run: `npx vitest run`
Expected: PASS. Phase 2 auth component tests assert classes/behavior (not hex values), so the token swap must not break them. Fix any test that hard-codes a terracotta hex by pointing it at the token.

- [ ] **Step 6: Update browser-test color expectations**

Run: `grep -rniE 'b23a2e|992f25|c2683c|fbfaf7|f4f1ea|terracotta|rgb\(178' tests/Browser resources/js` to find any computed-style assertions that expect the old palette.
For each hit in `tests/Browser/**`, change the expected color to the monochrome equivalent (primary `rgb(10, 10, 10)`, bg `rgb(255, 255, 255)`). If none found, note "no color assertions to update" in the report. Do NOT invent assertions.

- [ ] **Step 7: Commit**

```bash
git add resources/css/tokens.css tailwind.config.js resources/css/admin-components.css resources/css/admin.css resources/js/components/admin/tokens.test.tsx tests/Browser
git commit -m "feat(phase3): monochrome design tokens (whole-system) + admin edge classes"
```

---

## Task 2: Admin shell — AdminLayout + Sidebar + Topbar + Flash

**Files:**
- Create: `resources/js/lib/theme.ts`, `resources/js/lib/admin-nav.ts`
- Create: `resources/js/components/admin/{Sidebar,Topbar,FlashBanner}.tsx`
- Create: `resources/js/layouts/AdminLayout.tsx`
- Test: `resources/js/layouts/AdminLayout.test.tsx`, `resources/js/components/admin/Sidebar.test.tsx`

**Interfaces:**
- Consumes: shared `auth.can` (from `SharedProps`), `flash`, `useTranslation()`.
- Produces: `AdminLayout({ children, title, breadcrumb }: { children: ReactNode; title?: string; breadcrumb?: ReactNode })`; `applyPersistentAdminLayout(page)` helper so pages do `Page.layout = (page) => <AdminLayout>{page}</AdminLayout>`. `NAV_GROUPS` model. `useThemeBootstrap()`.

- [ ] **Step 1: Write the failing Sidebar test**

`resources/js/components/admin/Sidebar.test.tsx`:
```tsx
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
```

- [ ] **Step 2: Run it to verify it fails**

Run: `npx vitest run resources/js/components/admin/Sidebar.test.tsx`
Expected: FAIL ("Cannot find module './Sidebar'").

- [ ] **Step 3: Create the nav model**

`resources/js/lib/admin-nav.ts` (labels are i18n keys; the component translates them):
```ts
export type Ability =
  | 'see_admin_panel' | 'manage_posts' | 'manage_pages' | 'manage_services'
  | 'manage_post_categories' | 'manage_comments' | 'manage_menus'
  | 'manage_general_settings' | 'manage_users' | 'manage_user_roles';

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
    { key: 'cpanel/menu.menus', fallback: 'Menus', href: `${A}/menu`, component: 'cpanel/menus', ability: 'manage_menus' },
  ]},
  { labelKey: 'cpanel/menu.settings', fallback: 'Settings', items: [
    { key: 'cpanel/menu.settings', fallback: 'Settings', href: `${A}/settings`, component: 'cpanel/settings', ability: 'manage_general_settings' },
    { key: 'cpanel/menu.users', fallback: 'Users', href: `${A}/users`, component: 'cpanel/users', ability: 'manage_users' },
  ]},
];
```

- [ ] **Step 4: Create `Sidebar.tsx`**

```tsx
import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { NAV_GROUPS, type Ability } from '@/lib/admin-nav';

export function Sidebar({ can }: { can: Record<Ability, boolean> }) {
  const { t } = useTranslation();
  const { component } = usePage();
  const label = (key: string, fallback: string) => {
    const s = t(key);
    return s === key ? fallback : s;
  };
  return (
    <aside data-testid="admin-sidebar" className="theme-admin w-[236px] shrink-0 bg-surface-2 p-3 flex flex-col gap-1">
      <div className="flex items-center gap-2 px-2 py-3">
        <div className="h-7 w-7 grid place-items-center rounded-lg bg-primary text-primary-contrast font-bold text-sm">A</div>
        <b className="text-fg tracking-tight">Agentic CMS</b>
      </div>
      {NAV_GROUPS.map((group) => {
        const items = group.items.filter((i) => can[i.ability]);
        if (items.length === 0) return null;
        return (
          <div key={group.labelKey}>
            <div className="px-2 pt-3 pb-1 text-[10.5px] font-semibold uppercase tracking-wider text-faint">
              {label(group.labelKey, group.fallback)}
            </div>
            {items.map((item) => {
              const active = component === item.component || component.startsWith(`${item.component}/`);
              return (
                <Link key={item.key} href={item.href} prefetch
                  className={`flex items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-sm ${
                    active ? 'admin-nav-active font-semibold text-fg' : 'text-muted hover:bg-black/[.035] hover:text-fg'
                  }`}>
                  {label(item.key, item.fallback)}
                </Link>
              );
            })}
          </div>
        );
      })}
    </aside>
  );
}
```

- [ ] **Step 5: Run the Sidebar test — PASS**

Run: `npx vitest run resources/js/components/admin/Sidebar.test.tsx`
Expected: PASS.

- [ ] **Step 6: Create theme hook, Topbar, FlashBanner, AdminLayout**

`resources/js/lib/theme.ts`:
```ts
import { useEffect } from 'react';

export function applyStoredTheme(): void {
  if (typeof window === 'undefined') return;
  const stored = window.localStorage.getItem('agentic-cms-theme');
  const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;
  const dark = stored ? stored === 'dark' : prefersDark;
  document.documentElement.classList.toggle('dark', dark);
}

export function useThemeBootstrap(): void {
  useEffect(() => { applyStoredTheme(); }, []);
}
```

`resources/js/components/admin/FlashBanner.tsx`:
```tsx
import { usePage } from '@inertiajs/react';
import type { SharedProps } from '@/lib/types';

export function FlashBanner() {
  const { flash } = usePage<SharedProps>().props;
  const msg = flash?.success ?? flash?.status;
  const err = flash?.error;
  if (!msg && !err) return null;
  return (
    <div className={`mx-6 mt-4 rounded-lg px-4 py-2.5 text-sm admin-bevel ${err ? 'text-error' : 'text-success'}`}
         role="status">
      {err ?? msg}
    </div>
  );
}
```

`resources/js/components/admin/Topbar.tsx`:
```tsx
import type { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import type { SharedProps } from '@/lib/types';

export function Topbar({ breadcrumb }: { breadcrumb?: ReactNode }) {
  const { auth } = usePage<SharedProps>().props;
  const initials = (auth.user?.name ?? '?').slice(0, 2).toUpperCase();
  return (
    <div className="sticky top-0 z-10 flex h-14 items-center gap-3.5 px-5 backdrop-blur-md bg-surface/70 shadow-[0_1px_0_rgba(9,9,11,.06)]">
      <div className="text-[13px] text-muted">{breadcrumb}</div>
      <div className="ml-auto flex items-center gap-3">
        <div className="grid h-[30px] w-[30px] place-items-center rounded-full bg-primary text-primary-contrast text-[11.5px] font-semibold">
          {initials}
        </div>
      </div>
    </div>
  );
}
```

`resources/js/layouts/AdminLayout.tsx`:
```tsx
import type { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import { Sidebar } from '@/components/admin/Sidebar';
import { Topbar } from '@/components/admin/Topbar';
import { FlashBanner } from '@/components/admin/FlashBanner';
import { useThemeBootstrap } from '@/lib/theme';
import type { SharedProps } from '@/lib/types';

export function AdminLayout({ children, breadcrumb }: { children: ReactNode; breadcrumb?: ReactNode }) {
  useThemeBootstrap();
  const { auth } = usePage<SharedProps>().props;
  return (
    <div className="theme-admin flex min-h-screen bg-bg text-fg font-sans">
      <Sidebar can={auth.can} />
      <div className="flex min-w-0 flex-1 flex-col">
        <Topbar breadcrumb={breadcrumb} />
        <FlashBanner />
        <main className="flex-1 overflow-auto p-6">{children}</main>
      </div>
    </div>
  );
}
```

- [ ] **Step 7: Write the AdminLayout test**

`resources/js/layouts/AdminLayout.test.tsx`:
```tsx
import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { AdminLayout } from './AdminLayout';

const shared = {
  auth: { user: { id: 1, name: 'Elman Admin', email: 'a@b.com' },
    can: { see_admin_panel: true, manage_posts: true, manage_pages: true, manage_services: true,
      manage_post_categories: true, manage_comments: true, manage_menus: true,
      manage_general_settings: true, manage_users: true, manage_user_roles: true } },
  flash: { success: 'Saved' },
};

vi.mock('@inertiajs/react', () => ({
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
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
```

- [ ] **Step 8: Run layout tests — PASS**

Run: `npx vitest run resources/js/layouts/AdminLayout.test.tsx resources/js/components/admin/Sidebar.test.tsx`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add resources/js/lib/theme.ts resources/js/lib/admin-nav.ts resources/js/components/admin resources/js/layouts/AdminLayout.tsx resources/js/layouts/AdminLayout.test.tsx
git commit -m "feat(phase3): persistent AdminLayout shell (sidebar/topbar/flash, permission-gated, monochrome)"
```

---

## Task 3: Dashboard home → Inertia

**Files:**
- Modify: `app/Http/Controllers/CPanel/CPanelHomeController.php`
- Create: `resources/js/pages/cpanel/Dashboard.tsx`, `resources/js/pages/cpanel/Dashboard.test.tsx`
- Create: `tests/Feature/CPanel/DashboardInertiaTest.php`
- Modify: `tests/Feature/Phase5AdminRenderTest.php` (remove `cpanel_home` from the `theme-admin` loop)

**Interfaces:**
- Consumes: `AdminLayout`, `useTranslation()`.
- Produces: Inertia component `cpanel/Dashboard` with props `{ posts: Array<{id:number,title:string}>, users: Array<{id:number,name:string}>, comments: Array<{id:number,body:string}> }`.

- [ ] **Step 1: Write the failing Feature test**

`tests/Feature/CPanel/DashboardInertiaTest.php`:
```php
<?php

namespace Tests\Feature\CPanel;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    public function test_dashboard_renders_inertia_component_with_props(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/agentic-cms-laravel-admin')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/Dashboard')
                ->has('posts')
                ->has('users')
                ->has('comments'));
    }
}
```

- [ ] **Step 2: Run it — FAIL**

Run: `php artisan test --filter=DashboardInertiaTest`
Expected: FAIL (controller still returns the Blade view `cpanel.home`).

- [ ] **Step 3: Flip the controller**

In `app/Http/Controllers/CPanel/CPanelHomeController.php`, change the `index()` return from `view('cpanel.home', compact('posts','users','comments'))` to:
```php
return \Inertia\Inertia::render('cpanel/Dashboard', [
    'posts' => $posts,
    'users' => $users,
    'comments' => $comments,
]);
```
(Keep the existing `$posts/$users/$comments` fetch lines from `CPanelDashboardService` exactly as they are.)

- [ ] **Step 4: Run it — PASS**

Run: `php artisan test --filter=DashboardInertiaTest`
Expected: PASS.

- [ ] **Step 5: Create the Dashboard page**

`resources/js/pages/cpanel/Dashboard.tsx`:
```tsx
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import type { ReactElement } from 'react';

interface DashboardProps {
  posts: Array<{ id: number; title: string }>;
  users: Array<{ id: number; name: string }>;
  comments: Array<{ id: number; body: string }>;
}

function Card({ title, items, render }: { title: string; items: any[]; render: (i: any) => string }) {
  return (
    <section className="admin-card p-4">
      <h3 className="mb-3 text-[13px] font-semibold">{title}</h3>
      <ul className="flex flex-col gap-2 text-sm text-muted">
        {items.length === 0 ? <li className="text-faint">—</li>
          : items.map((i) => <li key={i.id} className="truncate">{render(i)}</li>)}
      </ul>
    </section>
  );
}

export default function Dashboard({ posts, users, comments }: DashboardProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  return (
    <>
      <Head title={tr('cpanel/menu.dashboard', 'Dashboard')} />
      <div className="mb-5">
        <h1 className="text-[22px] font-semibold tracking-tight">{tr('cpanel/menu.dashboard', 'Dashboard')}</h1>
      </div>
      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
        <Card title={tr('cpanel/menu.posts', 'Latest posts')} items={posts} render={(p) => p.title} />
        <Card title={tr('cpanel/menu.users', 'Latest users')} items={users} render={(u) => u.name} />
        <Card title={tr('cpanel/menu.comments', 'Latest comments')} items={comments} render={(c) => c.body} />
      </div>
    </>
  );
}

Dashboard.layout = (page: ReactElement) => <AdminLayout breadcrumb="Admin">{page}</AdminLayout>;
```

- [ ] **Step 6: Write the Dashboard component test**

`resources/js/pages/cpanel/Dashboard.test.tsx`:
```tsx
import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import Dashboard from './Dashboard';

vi.mock('@inertiajs/react', () => ({ Head: () => null }));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));

describe('Dashboard page', () => {
  it('renders the three latest-N cards', () => {
    render(<Dashboard
      posts={[{ id: 1, title: 'Hello world' }]}
      users={[{ id: 1, name: 'Ada' }]}
      comments={[{ id: 1, body: 'Nice' }]} />);
    expect(screen.getByText('Hello world')).toBeInTheDocument();
    expect(screen.getByText('Ada')).toBeInTheDocument();
    expect(screen.getByText('Nice')).toBeInTheDocument();
  });
});
```

- [ ] **Step 7: Run it — PASS**

Run: `npx vitest run resources/js/pages/cpanel/Dashboard.test.tsx`
Expected: PASS.

- [ ] **Step 8: Keep Phase5AdminRenderTest green**

In `tests/Feature/Phase5AdminRenderTest.php`, remove `cpanel_home` from the array looped by `test_admin_index_pages_render_200` (the loop asserts `assertSee('theme-admin', false)`, which an Inertia page no longer emits). The new `DashboardInertiaTest` now covers that route. Run: `php artisan test --filter=Phase5AdminRenderTest` → PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/CPanel/CPanelHomeController.php resources/js/pages/cpanel/Dashboard.tsx resources/js/pages/cpanel/Dashboard.test.tsx tests/Feature/CPanel/DashboardInertiaTest.php tests/Feature/Phase5AdminRenderTest.php
git commit -m "feat(phase3): Dashboard home on Inertia"
```

---

## Task 4: Categories list → Inertia (+ row/bulk delete via router.delete)

**Files:**
- Modify: `app/Http/Controllers/CPanel/CPanelCategoryController.php` (`index` only)
- Create: `resources/js/pages/cpanel/categories/List.tsx`, `List.test.tsx`
- Create: `tests/Feature/CPanel/CategoryInertiaRenderTest.php` (index case)
- Modify: `tests/Feature/Phase5AdminRenderTest.php` (remove `cpanel_category_list` from the loop)

**Interfaces:**
- Consumes: `AdminLayout`, `router` from `@inertiajs/react`, `useTranslation()`.
- Produces: component `cpanel/categories/List` with props `{ categories_list: { data: Array<{id:number,title:string,slug:string,parent_title:string|null}>, current_page:number, last_page:number, total:number } }`.
- Delete: both row and bulk call `router.delete(route('cpanel_category_bulk_delete'), { data: { categories: number[], categories_action: 'delete' } })`. Endpoint already redirects back (Inertia-friendly). `deleteAjax`/`category.js` untouched (become dead for categories, removed in Phase 5).

- [ ] **Step 1: Write the failing Feature test**

`tests/Feature/CPanel/CategoryInertiaRenderTest.php`:
```php
<?php

namespace Tests\Feature\CPanel;

use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CategoryInertiaRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->admin = User::where('username', 'admin')->firstOrFail();
    }

    public function test_list_renders_inertia_component_with_pagination(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/categories')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/categories/List')
                ->has('categories_list.data'));
    }
}
```

- [ ] **Step 2: Run it — FAIL**

Run: `php artisan test --filter=CategoryInertiaRenderTest`
Expected: FAIL (still Blade).

- [ ] **Step 3: Flip `index()`**

In `CPanelCategoryController::index()`:
```php
public function index()
{
    $categories_list = $this->service->list($this->per_page);

    return \Inertia\Inertia::render('cpanel/categories/List', [
        'categories_list' => $categories_list,
    ]);
}
```
`$this->service->list(...)` returns a paginator; Inertia serializes it to `{ data, current_page, last_page, total, ... }`. Each row object exposes `id`, `title`, `slug`, and (from the repository's parent join / accessor) `parent_title`. If `parent_title` is not already present on the row, map it in the controller before rendering:
```php
$categories_list->getCollection()->transform(fn ($c) => [
    'id' => $c->id,
    'title' => $c->title,
    'slug' => $c->slug,
    'parent_title' => $c->parent_title ?? null,
]);
```
(Confirm the shape by dumping one row; keep whichever of the two forms matches the actual paginator contents. Do NOT add a repository method — mapping in the controller is presentation, allowed by the layering test.)

- [ ] **Step 4: Run it — PASS**

Run: `php artisan test --filter=CategoryInertiaRenderTest`
Expected: PASS.

- [ ] **Step 5: Create `List.tsx`**

```tsx
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import type { ReactElement } from 'react';

interface Row { id: number; title: string; slug: string; parent_title: string | null }
interface ListProps {
  categories_list: { data: Row[]; current_page: number; last_page: number; total: number };
}

const BASE = '/agentic-cms-laravel-admin/categories';
const PROTECTED_ID = 1; // seeded root category — never deletable

export default function List({ categories_list }: ListProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const rows = categories_list.data;
  const [selected, setSelected] = useState<number[]>([]);

  const toggle = (id: number) =>
    setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));

  const del = (ids: number[]) => {
    if (ids.length === 0) return;
    if (!window.confirm(tr('cpanel/categories.js_delete_confirmation', 'Delete selected categories?'))) return;
    router.delete(`${BASE}/multipleDelete`, {
      data: { categories: ids, categories_action: 'delete' },
      preserveScroll: true,
      onSuccess: () => setSelected([]),
    });
  };

  return (
    <>
      <Head title={tr('cpanel/menu.categories', 'Categories')} />
      <div className="mb-5 flex items-center">
        <div>
          <h1 className="text-[22px] font-semibold tracking-tight">{tr('cpanel/menu.categories', 'Categories')}</h1>
        </div>
        <Link href={`${BASE}/new`} prefetch
          className="ml-auto inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-primary px-3.5 text-[13px] font-semibold text-primary-contrast">
          + {tr('cpanel/categories.add_new', 'New category')}
        </Link>
      </div>

      <div className="admin-card overflow-hidden">
        {selected.length > 0 && (
          <div className="flex items-center gap-3 border-b admin-sep bg-surface-2 px-4 py-2.5 text-[12.5px]">
            {selected.length} {tr('cpanel/categories.selected', 'selected')}
            <button data-testid="bulk-delete-confirm" onClick={() => del(selected)}
              className="ml-1 inline-flex items-center gap-1.5 font-semibold text-error">
              {tr('cpanel/categories.delete_selected', 'Delete selected')}
            </button>
          </div>
        )}
        <table className="w-full border-collapse text-[13.5px]">
          <thead>
            <tr className="text-left text-[11px] uppercase tracking-wide text-faint">
              <th className="w-[38px] border-b admin-sep px-4 py-2.5"></th>
              <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/categories.title', 'Title')}</th>
              <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/categories.slug', 'Slug')}</th>
              <th className="border-b admin-sep px-4 py-2.5">{tr('cpanel/categories.parent', 'Parent')}</th>
              <th className="w-[120px] border-b admin-sep px-4 py-2.5"></th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.id} className="hover:bg-black/[.022]">
                <td className="border-b admin-sep px-4 py-3">
                  {r.id !== PROTECTED_ID && (
                    <input type="checkbox" aria-label={`select-${r.id}`}
                      checked={selected.includes(r.id)} onChange={() => toggle(r.id)} />
                  )}
                </td>
                <td className="border-b admin-sep px-4 py-3 font-semibold">{r.title}</td>
                <td className="border-b admin-sep px-4 py-3 font-mono text-xs text-muted">{r.slug}</td>
                <td className="border-b admin-sep px-4 py-3 text-muted">{r.parent_title ?? '—'}</td>
                <td className="border-b admin-sep px-4 py-3">
                  <div className="flex gap-3.5 text-[12.5px]">
                    <Link href={`${BASE}/${r.id}/${document.documentElement.lang || 'en'}`} prefetch
                      className="text-muted hover:text-fg">{tr('cpanel/categories.edit', 'Edit')}</Link>
                    {r.id !== PROTECTED_ID && (
                      <button onClick={() => del([r.id])} className="text-muted hover:text-error">
                        {tr('cpanel/categories.delete', 'Delete')}
                      </button>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <div className="flex items-center px-4 py-3 text-[12.5px] text-muted">
          {rows.length} {tr('cpanel/categories.of', 'of')} {categories_list.total}
        </div>
      </div>
    </>
  );
}

List.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Categories">{page}</AdminLayout>
);
```

- [ ] **Step 6: Write `List.test.tsx`**

```tsx
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const del = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  router: { delete: (...a: any[]) => del(...a) },
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import List from './List';

const props = {
  categories_list: {
    data: [
      { id: 1, title: 'Root', slug: 'root', parent_title: null },
      { id: 2, title: 'Travel', slug: 'travel', parent_title: null },
    ],
    current_page: 1, last_page: 1, total: 2,
  },
};

describe('Categories List', () => {
  it('renders rows and hides the checkbox for the protected id=1', () => {
    render(<List {...props} />);
    expect(screen.getByText('Travel')).toBeInTheDocument();
    expect(screen.queryByLabelText('select-1')).not.toBeInTheDocument();
    expect(screen.getByLabelText('select-2')).toBeInTheDocument();
  });

  it('shows the bulk bar with testid after selecting a row and fires router.delete', () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    render(<List {...props} />);
    fireEvent.click(screen.getByLabelText('select-2'));
    const btn = screen.getByTestId('bulk-delete-confirm');
    fireEvent.click(btn);
    expect(del).toHaveBeenCalledWith(
      '/agentic-cms-laravel-admin/categories/multipleDelete',
      expect.objectContaining({ data: { categories: [2], categories_action: 'delete' } }),
    );
  });
});
```

- [ ] **Step 7: Run it — PASS**

Run: `npx vitest run resources/js/pages/cpanel/categories/List.test.tsx`
Expected: PASS.

- [ ] **Step 8: Keep Phase5AdminRenderTest green**

Remove `cpanel_category_list` from the `test_admin_index_pages_render_200` loop in `tests/Feature/Phase5AdminRenderTest.php` (now covered by `CategoryInertiaRenderTest`). Run `php artisan test --filter=Phase5AdminRenderTest` → PASS.

- [ ] **Step 9: Verify CrudTest still green**

Run: `php artisan test --filter=CategoryCrudTest`
Expected: PASS (endpoints and DB behavior unchanged; row-delete now uses the bulk endpoint but the single-delete `deleteAjax` route the test hits is untouched).

- [ ] **Step 10: Commit**

```bash
git add app/Http/Controllers/CPanel/CPanelCategoryController.php resources/js/pages/cpanel/categories/List.tsx resources/js/pages/cpanel/categories/List.test.tsx tests/Feature/CPanel/CategoryInertiaRenderTest.php tests/Feature/Phase5AdminRenderTest.php
git commit -m "feat(phase3): Categories list on Inertia + delete via router.delete"
```

---

## Task 5: Categories form (new + edit) → Inertia

**Files:**
- Modify: `app/Http/Controllers/CPanel/CPanelCategoryController.php` (`addCategory`, `edit`)
- Create: `resources/js/pages/cpanel/categories/Form.tsx`, `Form.test.tsx`
- Modify: `tests/Feature/CPanel/CategoryInertiaRenderTest.php` (add new + edit cases)
- Modify: `tests/Feature/Phase5AdminRenderTest.php` (remove `cpanel_add_new_category` from the create-forms loop)

**Interfaces:**
- Consumes: `AdminLayout`, `useForm` + `Link` from `@inertiajs/react`, `TextField`, `Button`, `useTranslation()`.
- Produces: component `cpanel/categories/Form` with props `{ entity: CategoryEntity | null, parent_options: Array<{category_id:number,title:string,depth:number}>, translation_links: Record<string,string> }` where `CategoryEntity = { id:number, title:string, slug:string, description:string|null, parent_category_id:number|null, meta_description:string|null, meta_keywords:string|null }`.
- Submit: new → `post('/agentic-cms-laravel-admin/categories/new')`; edit → `put(\`/agentic-cms-laravel-admin/categories/${entity.id}/update\`)`. Field names EXACTLY match `CategoryRequest`: `title`, `slug`, `parent_category_id`, `description`, `meta_description`, `meta_keywords`.

- [ ] **Step 1: Write the failing Feature tests (new + edit)**

Add to `tests/Feature/CPanel/CategoryInertiaRenderTest.php`:
```php
    public function test_new_form_renders_inertia_component(): void
    {
        $this->actingAs($this->admin)
            ->get('/agentic-cms-laravel-admin/categories/new')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/categories/Form')
                ->where('entity', null)
                ->has('parent_options'));
    }

    public function test_edit_form_renders_inertia_component_with_entity(): void
    {
        $id = \App\Http\Models\CategoryTranslation::where('locale', 'en')->value('category_id');

        $this->actingAs($this->admin)
            ->get("/agentic-cms-laravel-admin/categories/{$id}/en")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/categories/Form')
                ->has('entity')
                ->has('parent_options')
                ->has('translation_links'));
    }
```
(If no seeded category exists, create one via the existing factory/seeder in an arrange step; reuse whatever `CategoryCrudTest` relies on.)

- [ ] **Step 2: Run it — FAIL**

Run: `php artisan test --filter=CategoryInertiaRenderTest`
Expected: the two new methods FAIL (still Blade).

- [ ] **Step 3: Flip `addCategory()` and `edit()`**

```php
public function addCategory()
{
    $props = [
        'entity' => null,
        'parent_options' => $this->service->parentOptions(),
        'translation_links' => request()->route('lang')
            ? get_entity_translation_links('categories', request()->id)
            : [],
    ];

    return \Inertia\Inertia::render('cpanel/categories/Form', $props);
}

public function edit($id)
{
    $this->result = $this->service->getById($id);

    if (is_null($this->result)) {
        return $this->addCategory();
    }

    return \Inertia\Inertia::render('cpanel/categories/Form', [
        'entity' => [
            'id' => $this->result->id,
            'title' => $this->result->title,
            'slug' => $this->result->slug,
            'description' => $this->result->description,
            'parent_category_id' => $this->result->parent_category_id,
            'meta_description' => $this->result->meta_description ?? null,
            'meta_keywords' => $this->result->meta_keywords ?? null,
        ],
        'parent_options' => $this->service->parentOptions((int) $id),
        'translation_links' => get_entity_translation_links('categories', $id),
    ]);
}
```
Leave `createCategory`, `updateCategory`, `multipleDelete` unchanged (they already redirect back — Inertia-friendly).

- [ ] **Step 4: Run it — PASS**

Run: `php artisan test --filter=CategoryInertiaRenderTest`
Expected: PASS (all three cases).

- [ ] **Step 5: Create `Form.tsx`**

```tsx
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import type { SharedProps } from '@/lib/types';
import type { ReactElement } from 'react';

interface CategoryEntity {
  id: number; title: string; slug: string; description: string | null;
  parent_category_id: number | null; meta_description: string | null; meta_keywords: string | null;
}
interface ParentOption { category_id: number; title: string; depth: number }
interface FormProps {
  entity: CategoryEntity | null;
  parent_options: ParentOption[];
  translation_links: Record<string, string>;
}

const BASE = '/agentic-cms-laravel-admin/categories';

export default function Form({ entity, parent_options, translation_links }: FormProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const { locale } = usePage<SharedProps>().props;

  const form = useForm({
    title: entity?.title ?? '',
    slug: entity?.slug ?? '',
    parent_category_id: entity?.parent_category_id ?? '',
    description: entity?.description ?? '',
    meta_description: entity?.meta_description ?? '',
    meta_keywords: entity?.meta_keywords ?? '',
  });

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    if (entity) form.put(`${BASE}/${entity.id}/update`);
    else form.post(`${BASE}/new`);
  };

  const heading = entity ? tr('cpanel/categories.edit', 'Edit category') : tr('cpanel/categories.add_new', 'New category');

  return (
    <>
      <Head title={heading} />
      <form onSubmit={submit}>
        <div className="mb-5 flex items-center gap-4">
          <h1 className="text-[22px] font-semibold tracking-tight">{heading}</h1>
          <div className="ml-auto flex items-center gap-2.5">
            {Object.entries(translation_links).length > 0 && (
              <div className="inline-flex gap-1 rounded-[10px] admin-bevel p-1">
                <span className="rounded-md bg-primary px-2.5 py-1 text-xs font-semibold text-primary-contrast uppercase">
                  {locale.current}
                </span>
                {Object.entries(translation_links).map(([title, href]) => (
                  <a key={href} href={`/${href}`} className="rounded-md px-2.5 py-1 text-xs font-semibold text-muted uppercase">
                    {title.slice(0, 2)}
                  </a>
                ))}
              </div>
            )}
            <Button href={BASE} variant="outline" size="md">{tr('cpanel/categories.cancel', 'Cancel')}</Button>
            <Button type="submit" variant="primary" size="md" loading={form.processing}>
              {tr('cpanel/categories.save', 'Save')}
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_300px]">
          <section className="admin-card p-[18px] flex flex-col gap-4">
            <TextField name="title" label={tr('cpanel/categories.title', 'Title')} required
              data-testid="category-title" value={form.data.title} error={form.errors.title}
              onChange={(e) => form.setData('title', e.target.value)} />
            <TextField name="slug" label={tr('cpanel/categories.slug', 'Slug')} required
              data-testid="category-slug" value={form.data.slug} error={form.errors.slug}
              onChange={(e) => form.setData('slug', e.target.value)} />
            <div>
              <label htmlFor="parent_category_id" className="mb-1.5 block text-xs font-semibold text-fg">
                {tr('cpanel/categories.parent', 'Parent category')}
              </label>
              <select id="parent_category_id" name="parent_category_id"
                className="field-input w-full" value={form.data.parent_category_id}
                onChange={(e) => form.setData('parent_category_id', e.target.value)}>
                <option value="">{tr('cpanel/categories.no_parent', '— None (top level) —')}</option>
                {parent_options.map((o) => (
                  <option key={o.category_id} value={o.category_id}>
                    {'  '.repeat(o.depth)}{o.title}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label htmlFor="description" className="mb-1.5 block text-xs font-semibold text-fg">
                {tr('cpanel/categories.description', 'Description')}
              </label>
              <textarea id="description" name="description" className="field-input min-h-[88px] w-full"
                value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
            </div>
          </section>

          <section className="admin-card p-[18px] flex flex-col gap-4">
            <h3 className="text-[13px] font-semibold">{tr('cpanel/categories.seo', 'SEO')}</h3>
            <div>
              <label htmlFor="meta_description" className="mb-1.5 block text-xs font-semibold text-fg">
                {tr('cpanel/categories.meta_description', 'Meta description')}
              </label>
              <textarea id="meta_description" name="meta_description" className="field-input min-h-[70px] w-full"
                value={form.data.meta_description} onChange={(e) => form.setData('meta_description', e.target.value)} />
            </div>
            <TextField name="meta_keywords" label={tr('cpanel/categories.meta_keywords', 'Meta keywords')}
              value={form.data.meta_keywords} error={form.errors.meta_keywords}
              onChange={(e) => form.setData('meta_keywords', e.target.value)} />
          </section>
        </div>
      </form>
    </>
  );
}

Form.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Categories / Edit">{page}</AdminLayout>
);
```

- [ ] **Step 6: Write `Form.test.tsx`**

```tsx
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const post = vi.fn();
const put = vi.fn();
const setData = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  usePage: () => ({ props: { locale: { current: 'en' } } }),
  useForm: (initial: any) => ({
    data: initial, errors: {}, processing: false, setData, post, put,
  }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import Form from './Form';

const parent_options = [{ category_id: 5, title: 'Travel', depth: 0 }];

describe('Categories Form', () => {
  it('new: renders required title/slug testids and posts to /new', () => {
    render(<Form entity={null} parent_options={parent_options} translation_links={{}} />);
    expect(screen.getByTestId('category-title')).toBeInTheDocument();
    expect(screen.getByTestId('category-slug')).toBeInTheDocument();
    fireEvent.submit(screen.getByTestId('category-title').closest('form')!);
    expect(post).toHaveBeenCalledWith('/agentic-cms-laravel-admin/categories/new');
  });

  it('edit: prefills and PUTs to /{id}/update', () => {
    const entity = { id: 7, title: 'City guides', slug: 'city-guides', description: 'x',
      parent_category_id: 5, meta_description: null, meta_keywords: null };
    render(<Form entity={entity} parent_options={parent_options} translation_links={{ Deutsch: 'agentic-cms-laravel-admin/categories/7/de' }} />);
    fireEvent.submit(screen.getByTestId('category-title').closest('form')!);
    expect(put).toHaveBeenCalledWith('/agentic-cms-laravel-admin/categories/7/update');
  });
});
```

- [ ] **Step 7: Run it — PASS**

Run: `npx vitest run resources/js/pages/cpanel/categories/Form.test.tsx`
Expected: PASS.

- [ ] **Step 8: Keep Phase5AdminRenderTest green + verify CrudTest**

Remove `cpanel_add_new_category` from the `test_admin_create_forms_render_200` loop in `Phase5AdminRenderTest.php`. Run:
`php artisan test --filter=Phase5AdminRenderTest` → PASS
`php artisan test --filter=CategoryCrudTest` → PASS (create/update field names unchanged, still write `category_translations`).

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/CPanel/CPanelCategoryController.php resources/js/pages/cpanel/categories/Form.tsx resources/js/pages/cpanel/categories/Form.test.tsx tests/Feature/CPanel/CategoryInertiaRenderTest.php tests/Feature/Phase5AdminRenderTest.php
git commit -m "feat(phase3): Categories form (new+edit) on Inertia"
```

---

## Task 6: Instant-navigation proof (browser)

**Files:**
- Create: `tests/Browser/AdminInstantNavTest.php`

**Interfaces:**
- Consumes: the running app + seeded admin. Asserts a `<Link>` navigation from Dashboard → Categories does not reload the document.

- [ ] **Step 1: Write the browser test**

`tests/Browser/AdminInstantNavTest.php` — mirror the existing browser-suite base class and login helper used by other `tests/Browser/*` scenarios (read one sibling test first for the exact login + visit idiom; do not invent the base class). The scenario:
1. Log in as `admin`, visit `/agentic-cms-laravel-admin`.
2. Set a sentinel on the document: execute JS `window.__nav = 'kept'`.
3. Click the sidebar "Categories" `<Link>`.
4. Assert the URL is now `.../categories` AND `window.__nav` is still `'kept'` (a full reload would wipe the global) AND `[data-testid=admin-sidebar]` is still present (shell was not remounted).

```php
// Skeleton — adapt base class/login to match sibling tests in tests/Browser/.
public function test_link_navigation_does_not_full_reload(): void
{
    // arrange: login as admin, visit dashboard (use the suite's helper)
    // $page->script("window.__nav = 'kept'");
    // $page->click('[data-testid=admin-sidebar] a[href$="/categories"]');
    // $page->assertPathIs('/agentic-cms-laravel-admin/categories');
    // $this->assertSame('kept', $page->script('return window.__nav')[0]);
    // $page->assertPresent('[data-testid=admin-sidebar]');
}
```

- [ ] **Step 2: Run it (gated)**

Run: `BROWSER_TESTS=1 ./vendor/bin/pest tests/Browser/AdminInstantNavTest.php`
Expected: PASS once Vite assets are built (`npm run build`) and the app is served. If the browser suite cannot run in the worker environment, mark the scenario skipped with a clear reason and note it in the report — do NOT delete it.

- [ ] **Step 3: Commit**

```bash
git add tests/Browser/AdminInstantNavTest.php
git commit -m "test(phase3): browser proof that admin Link nav does not full-reload"
```

---

## Task 7: Full regression + manual verification

**Files:** none (verification task).

- [ ] **Step 1: Backend suite**

Run: `php artisan test`
Expected: PASS. Pay attention to `LayeringTest` (controllers must not touch repositories — we only mapped presentation data), `Phase5AdminRenderTest`, `CategoryCrudTest`, `CategoryTreeTest`.

- [ ] **Step 2: Frontend suite + types + build**

Run: `npx vitest run` → PASS; `npx tsc --noEmit` → 0 errors; `npm run build` → OK.

- [ ] **Step 3: Manual pass (all 3 locales)**

`php artisan serve` + `npm run dev`. As admin, walk Dashboard → Categories list → New → Edit on en/de/ru. Confirm: monochrome look with premium edges, instant navigation (no white flash / full reload), flash after save/delete, permission-gated sidebar (log in as a role without `manage_post_categories` → Categories hidden), auth screens (Phase 2) now render in monochrome and still work.

- [ ] **Step 4: Commit any fixes, then hand to final whole-branch review**

Dispatch the final code-reviewer (superpowers:requesting-code-review) over the Phase 3 range, then update the durable ledger.

---

## Self-Review Notes (author)

- **Spec coverage:** shell (T2), Dashboard (T3), Categories list (T4), form (T5), monochrome tokens/whole-system (T1), instant-nav (T2 links + T6 proof), tests converted (T3-T5), auth re-theme verified (T1 Step 5-6 + T7 Step 3). Public-site theming intentionally deferred to Phase 4 (spec Out-of-scope).
- **Type consistency:** `categories_list.data` row shape `{id,title,slug,parent_title}` is produced in T4 Step 3 and consumed in T4 List; `CategoryEntity` produced in T5 Step 3 controller and consumed in T5 Form. `parent_options` object shape `{category_id,title,depth}` consistent across controller + Form. Field names (`title/slug/parent_category_id/description/meta_description/meta_keywords`) match `CategoryRequest` rules verbatim.
- **Known verification point (flagged for implementer):** T4 Step 3 — confirm the paginator row actually exposes `parent_title`; if the repository doesn't join it, map it in the controller (presentation only, no repo change) or drop the Parent column to `parent_category_id`. The reviewer should check this against the real `CPanelCategoryRepository` output.
- **Delete semantics:** row + bulk both hit `cpanel_category_bulk_delete` (redirects back). `deleteAjax` + legacy `category.js` untouched, become dead for categories (Phase 5 cleanup). `CategoryCrudTest`'s `DELETE /{id}/delete` case still passes because that endpoint is unchanged.
