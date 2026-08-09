import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { installProgress, startProgress, finishProgress, resetProgress } from './progress';

function bar(): HTMLElement | null {
    return document.querySelector<HTMLElement>('.app-progress');
}

// A minimal fake router that records the start/finish handlers so tests can
// fire them with hand-built visit payloads.
function fakeRouter() {
    const handlers: Record<string, (event: { detail: { visit: unknown } }) => void> = {};
    return {
        on: (event: string, cb: (event: { detail: { visit: unknown } }) => void) => {
            handlers[event] = cb;
        },
        fire: (event: string, visit: Record<string, unknown>) =>
            handlers[event]?.({ detail: { visit } }),
    };
}

let mockNow = 0;

beforeEach(() => {
    vi.useFakeTimers();
    mockNow = 0;
    vi.spyOn(performance, 'now').mockImplementation(() => mockNow);
    resetProgress();
});

afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
});

describe('progress bar', () => {
    it('shows and sweeps toward 90% on start', () => {
        startProgress();
        const el = bar();
        expect(el).not.toBeNull();
        expect(el!.style.opacity).toBe('1');
        expect(el!.style.width).toBe('90%');
    });

    it('holds the bar for the minimum duration on an instant navigation', () => {
        startProgress(); // mockNow = 0
        finishProgress(); // elapsed 0 -> remaining 350ms
        // Just before the minimum window: still sweeping, not complete.
        vi.advanceTimersByTime(349);
        expect(bar()!.style.width).toBe('90%');
        // Crossing the window completes the bar.
        vi.advanceTimersByTime(1);
        expect(bar()!.style.width).toBe('100%');
    });

    it('completes immediately when the visit already outlasted the window', () => {
        startProgress();
        mockNow = 500; // slow visit, already past 350ms
        finishProgress();
        vi.advanceTimersByTime(0);
        expect(bar()!.style.width).toBe('100%');
    });

    it('fades out and resets the track after completing', () => {
        startProgress();
        finishProgress();
        vi.advanceTimersByTime(350 + 160);
        expect(bar()!.style.opacity).toBe('0');
        vi.advanceTimersByTime(260);
        expect(bar()!.style.width).toBe('0%');
    });

    it('ignores prefetch visits', () => {
        const r = fakeRouter();
        installProgress(r as never);
        r.fire('before', { prefetch: true, showProgress: true });
        expect(bar()).toBeNull();
    });

    it('ignores visits that opt out of the progress bar', () => {
        const r = fakeRouter();
        installProgress(r as never);
        r.fire('before', { prefetch: false, showProgress: false });
        expect(bar()).toBeNull();
    });

    it('drives the bar for an instant cache-hit (before + navigate, no finish)', () => {
        const r = fakeRouter();
        installProgress(r as never);
        r.fire('before', { prefetch: false, showProgress: true });
        expect(bar()!.style.width).toBe('90%');
        // Cache-hit fires no start/finish — only navigate ends it.
        r.fire('navigate', {});
        vi.advanceTimersByTime(350);
        expect(bar()!.style.width).toBe('100%');
    });

    it('ignores a stray navigate that no before started', () => {
        const r = fakeRouter();
        installProgress(r as never);
        r.fire('navigate', {});
        expect(bar()).toBeNull();
    });

    it('completes on finish when a visit never reaches navigate (error/cancel)', () => {
        const r = fakeRouter();
        installProgress(r as never);
        r.fire('before', { prefetch: false, showProgress: true });
        r.fire('finish', { prefetch: false, showProgress: true });
        vi.advanceTimersByTime(350);
        expect(bar()!.style.width).toBe('100%');
    });
});
