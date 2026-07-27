import { renderHook, act } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { useLfmPicker } from './lfm';

describe('useLfmPicker', () => {
  beforeEach(() => { (window as any).open = vi.fn(() => ({ close: vi.fn() })); delete (window as any).SetUrl; });
  it('opens the LFM popup and receives the picked url via SetUrl', () => {
    const onPick = vi.fn();
    const { result } = renderHook(() => useLfmPicker(onPick));
    act(() => result.current.open('Images', 'thumbnail'));
    expect(window.open).toHaveBeenCalledWith(
      expect.stringContaining('/filemanager?type=Images&field_name=thumbnail'),
      expect.any(String), expect.any(String));
    expect(typeof (window as any).SetUrl).toBe('function');
    act(() => (window as any).SetUrl('/storage/x.jpg'));
    expect(onPick).toHaveBeenCalledWith('/storage/x.jpg');
  });
});
