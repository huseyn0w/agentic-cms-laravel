import type { ReactNode } from 'react';

// Semantic status chip for admin list tables (Mono/Vercel). Tone drives the
// token colours; the caller supplies the already-translated label so i18n
// stays per-screen.
export type PillTone = 'success' | 'muted' | 'warning';

const TONES: Record<PillTone, string> = {
  success: 'bg-success-bg text-success',
  warning: 'bg-warning-bg text-warning',
  muted: 'bg-surface-3 text-subtle',
};

export function StatusPill({ tone, label }: { tone: PillTone; label: ReactNode }) {
  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11.5px] font-semibold ${TONES[tone]}`}
    >
      <span className="h-1.5 w-1.5 rounded-full bg-current opacity-80" />
      {label}
    </span>
  );
}
