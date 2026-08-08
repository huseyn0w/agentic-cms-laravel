import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { SharedProps } from '@/lib/types';

const AUTO_DISMISS_MS = 4500;

/**
 * Flash feedback as a fixed toast in the top-right corner (not an inline banner),
 * so a save/update confirmation is visible without scrolling back to the top.
 * Auto-dismisses after a few seconds; a new flash message (after a redirect)
 * revives it, and it can be closed manually.
 */
export function FlashBanner() {
  const { flash } = usePage<SharedProps>().props;
  const err = flash?.error;
  const text = err ?? flash?.success ?? flash?.status;
  const [dismissed, setDismissed] = useState(false);

  useEffect(() => {
    if (!text) return;
    setDismissed(false);
    const id = setTimeout(() => setDismissed(true), AUTO_DISMISS_MS);
    return () => clearTimeout(id);
  }, [text]);

  if (!text || dismissed) return null;

  return (
    <div className="pointer-events-none fixed right-4 top-4 z-50 flex max-w-[92vw] justify-end">
      <div
        role={err ? 'alert' : 'status'}
        className={`admin-bevel pointer-events-auto flex items-start gap-3 rounded-lg bg-surface px-4 py-3 text-sm shadow-lg ${
          err ? 'text-error' : 'text-success'
        }`}
      >
        <span className="max-w-xs">{text}</span>
        <button
          type="button"
          aria-label="Dismiss"
          onClick={() => setDismissed(true)}
          className="-mr-1 shrink-0 leading-none text-muted transition-colors hover:text-fg"
        >
          ×
        </button>
      </div>
    </div>
  );
}
