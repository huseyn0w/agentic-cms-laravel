import { useCallback } from 'react';

/**
 * Bridge to laravel-filemanager's picker popup. LFM's picker page calls
 * `window.SetUrl(url)` on its opener window when a file is chosen, so we
 * install that callback on `window` before opening the popup and route it
 * into `onPick`.
 */
export function useLfmPicker(onPick: (url: string) => void) {
  const open = useCallback((type: 'Images' | 'Files' = 'Images', field = 'thumbnail') => {
    (window as any).SetUrl = (url: string) => { onPick(url); popup?.close?.(); };
    const popup = window.open(
      `/filemanager?type=${type}&field_name=${field}`,
      'lfm', `width=${Math.round(window.innerWidth * 0.7)},height=${Math.round(window.innerHeight * 0.7)}`,
    );
  }, [onPick]);
  return { open };
}
