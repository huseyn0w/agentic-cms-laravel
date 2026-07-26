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
