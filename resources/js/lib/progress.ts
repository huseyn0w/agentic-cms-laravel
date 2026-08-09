import type { Router } from '@inertiajs/core';

/**
 * Top loading bar with a guaranteed-visible minimum duration.
 *
 * Inertia's built-in NProgress skips any visit that resolves faster than its
 * `delay`. With `prefetch="mount"` links every admin navigation is an instant
 * cache-hit, so the bar never showed. Cache-hits don't send an HTTP request, so
 * Inertia fires no `start`/`finish` for them — only `before` (per navigation)
 * and `navigate` (after the page swaps). This drives the bar off that pair and
 * holds it on screen for at least MIN_VISIBLE_MS, so even an instant navigation
 * shows a full sweep — the GitHub/Vercel behaviour.
 *
 * Prefetch requests fire `before` too (with `visit.prefetch === true`); they are
 * background fetches, not navigations, so they never drive the bar.
 */

const MIN_VISIBLE_MS = 350;
const COLOR = '#0070f3';
const BAR_HEIGHT = 3;
// Safety net: if a visit starts but never fires `navigate` (an error page, a
// cancelled visit), complete the bar anyway instead of leaving it at 90%.
const WATCHDOG_MS = 8000;

let bar: HTMLDivElement | null = null;
let active = false;
let startedAt = 0;
let watchdogTimer: ReturnType<typeof setTimeout> | null = null;
let finishTimer: ReturnType<typeof setTimeout> | null = null;
let completeTimer: ReturnType<typeof setTimeout> | null = null;
let resetTimer: ReturnType<typeof setTimeout> | null = null;

function now(): number {
    return typeof performance !== 'undefined' && typeof performance.now === 'function'
        ? performance.now()
        : Date.now();
}

function ensureBar(): HTMLDivElement {
    if (bar && bar.isConnected) {
        return bar;
    }
    const el = document.createElement('div');
    el.className = 'app-progress';
    el.setAttribute('role', 'progressbar');
    el.setAttribute('aria-hidden', 'true');
    const s = el.style;
    s.position = 'fixed';
    s.top = '0';
    s.left = '0';
    s.height = `${BAR_HEIGHT}px`;
    s.width = '0%';
    s.background = COLOR;
    s.boxShadow = `0 0 10px ${COLOR}, 0 0 6px ${COLOR}`;
    s.zIndex = '10500';
    s.opacity = '0';
    s.pointerEvents = 'none';
    document.body.appendChild(el);
    bar = el;
    return el;
}

function clearTimers(): void {
    for (const t of [watchdogTimer, finishTimer, completeTimer, resetTimer]) {
        if (t) {
            clearTimeout(t);
        }
    }
    watchdogTimer = finishTimer = completeTimer = resetTimer = null;
}

/** Tear the bar down and clear all state. Used between tests. */
export function resetProgress(): void {
    clearTimers();
    active = false;
    startedAt = 0;
    bar?.remove();
    bar = null;
}

/** Show the bar and sweep it toward 90% over the guaranteed-visible window. */
export function startProgress(): void {
    clearTimers();
    const el = ensureBar();
    active = true;
    startedAt = now();
    watchdogTimer = setTimeout(finishProgress, WATCHDOG_MS);
    // Jump back to the start of the track without animating.
    el.style.transition = 'none';
    el.style.width = '0%';
    el.style.opacity = '1';
    // Force a reflow so the next transition runs from 0%, not from wherever the
    // previous run left the width.
    void el.offsetWidth;
    el.style.transition = `width ${MIN_VISIBLE_MS}ms cubic-bezier(0.2, 0.6, 0.2, 1)`;
    el.style.width = '90%';
}

/**
 * Complete the bar, but not before it has been on screen for MIN_VISIBLE_MS.
 * On an instant navigation `elapsed` is ~0, so the whole 350ms sweep still
 * plays before the bar snaps to 100% and fades out.
 */
export function finishProgress(): void {
    // Only a `before`-driven start arms the bar; ignore stray `navigate`/
    // `finish` events (prefetch churn, opted-out visits) that never started it.
    if (!active) {
        return;
    }
    active = false;
    const el = ensureBar();
    const remaining = Math.max(0, MIN_VISIBLE_MS - (now() - startedAt));
    clearTimers();
    finishTimer = setTimeout(() => {
        el.style.transition = 'width 140ms ease-out';
        el.style.width = '100%';
        completeTimer = setTimeout(() => {
            el.style.transition = 'opacity 250ms ease';
            el.style.opacity = '0';
            resetTimer = setTimeout(() => {
                el.style.transition = 'none';
                el.style.width = '0%';
            }, 260);
        }, 160);
    }, remaining);
}

/**
 * Wire the bar to a router. `before` fires once per navigation — including
 * instant cache-hits, which send no request and so fire no `start`/`finish`;
 * `navigate` fires after the page swaps. Prefetch and opted-out visits are
 * skipped at start; `finish` is a fallback completion for error/cancel visits
 * that never reach `navigate`.
 */
export function installProgress(router: Pick<Router, 'on'>): void {
    router.on('before', (event) => {
        const visit = event.detail.visit;
        if (visit.prefetch || !visit.showProgress) {
            return;
        }
        startProgress();
    });
    router.on('navigate', () => finishProgress());
    router.on('finish', () => finishProgress());
}
