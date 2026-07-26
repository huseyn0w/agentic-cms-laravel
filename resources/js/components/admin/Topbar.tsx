import type { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import type { SharedProps } from '@/lib/types';

export function Topbar({ breadcrumb }: { breadcrumb?: ReactNode }) {
  const { auth } = usePage<SharedProps>().props;
  const initials = (auth.user?.name ?? '?').slice(0, 2).toUpperCase();
  return (
    <div className="sticky top-0 z-10 flex h-14 items-center gap-3.5 px-5 backdrop-blur-md bg-surface/70 border-b admin-sep">
      <div className="text-[13px] text-muted">{breadcrumb}</div>
      <div className="ml-auto flex items-center gap-3">
        <div className="grid h-[30px] w-[30px] place-items-center rounded-full bg-primary text-primary-contrast text-[11.5px] font-semibold">
          {initials}
        </div>
      </div>
    </div>
  );
}
