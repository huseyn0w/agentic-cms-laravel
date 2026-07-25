import '@testing-library/jest-dom/vitest';

// jsdom does not implement matchMedia. Several components read
// `prefers-color-scheme` as a theme fallback (see AuthLayout), so stub it
// with a not-matching MediaQueryList by default.
if (typeof window !== 'undefined' && !window.matchMedia) {
    window.matchMedia = (query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: () => {},
        removeListener: () => {},
        addEventListener: () => {},
        removeEventListener: () => {},
        dispatchEvent: () => false,
    });
}
