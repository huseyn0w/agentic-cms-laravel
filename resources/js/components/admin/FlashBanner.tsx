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
