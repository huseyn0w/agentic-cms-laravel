import type { ReactElement, ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import { Sidebar } from '@/components/admin/Sidebar';
import { Topbar } from '@/components/admin/Topbar';
import { FlashBanner } from '@/components/admin/FlashBanner';
import { UpdateBanner } from '@/components/admin/UpdateBanner';
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
        <UpdateBanner />
        <FlashBanner />
        <main className="flex-1 overflow-auto p-6">{children}</main>
      </div>
    </div>
  );
}

/**
 * Wires a page into the persistent-layout idiom:
 *   Page.layout = applyPersistentAdminLayout;
 * equivalent to `(page) => <AdminLayout>{page}</AdminLayout>`.
 */
export function applyPersistentAdminLayout(page: ReactNode): ReactElement {
  return <AdminLayout>{page}</AdminLayout>;
}
