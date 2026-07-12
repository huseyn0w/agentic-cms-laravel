/**
 * AgenticCms-Laravel — admin shell runtime (Phase 5 / Phase 6 dark-toggle update).
 *
 * Loaded via Vite in the admin <head>. Responsibilities:
 *   1. Alpine for the app shell (sidebar toggle, topbar user/language menus).
 *   2. Lightweight replacements for the Bootstrap JS we dropped:
 *        - modal show/hide  (data-toggle="modal" / data-dismiss / $.fn.modal)
 *        - collapse         (data-toggle="collapse")
 *      The legacy jQuery module scripts (custom-fields/*.js, left-nav, menu
 *      accordion) still target the same selectors / call $('#x').modal('hide').
 *   3. A Tailwind toast system that backs the global showNotification() the
 *      legacy scripts call via $.notify (bootstrap-notify replacement).
 *   4. Dark-mode toggle (Phase 6): same localStorage key as front.js (`agentic-cms-theme`).
 *      No-FOUC inline script in header-styles.blade.php applies .dark before paint;
 *      this file wires the topbar toggle button at boot time.
 *
 * jQuery itself is still served (admin/js/core/jquery) and loaded in the
 * footer. This file therefore defers all jQuery-dependent wiring until the
 * DOM is ready, by which point the footer <script> tags have parsed.
 */

import Alpine from 'alpinejs';

/* -------------------------------------------------------------------------
 | Alpine — app shell only. Kept minimal; the heavy lifting stays in jQuery
 | land to avoid rewriting the battle-tested module scripts.
 | ------------------------------------------------------------------------- */
window.Alpine = Alpine;
Alpine.start();

/* -------------------------------------------------------------------------
 | Toast system (Tailwind). Exposed as window.adminToast and wired into the
 | $.notify shim below so showNotification() keeps working unchanged.
 | ------------------------------------------------------------------------- */
function ensureToastStack() {
    let stack = document.querySelector('.toast-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.className = 'toast-stack';
        stack.setAttribute('aria-live', 'polite');
        stack.setAttribute('aria-atomic', 'false');
        document.body.appendChild(stack);
    }
    return stack;
}

const TOAST_ICONS = {
    success:
        '<svg class="h-5 w-5 shrink-0 text-success-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.1 3.1 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>',
    error:
        '<svg class="h-5 w-5 shrink-0 text-danger-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.7 7.3a1 1 0 0 0-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 1 0 1.4 1.4L10 11.4l1.3 1.3a1 1 0 0 0 1.4-1.4L11.4 10l1.3-1.3a1 1 0 0 0-1.4-1.4L10 8.6 8.7 7.3Z" clip-rule="evenodd"/></svg>',
    info:
        '<svg class="h-5 w-5 shrink-0 text-info-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm1-11a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm-1 3a1 1 0 0 0-1 1v3a1 1 0 1 0 2 0v-3a1 1 0 0 0-1-1Z" clip-rule="evenodd"/></svg>',
};

function adminToast(message, kind = 'info', timeout = 4000) {
    const stack = ensureToastStack();
    const cls = kind === 'success' ? 'toast-success' : kind === 'error' ? 'toast-error' : 'toast-info';
    const el = document.createElement('div');
    el.className = `toast-item ${cls}`;
    el.setAttribute('role', kind === 'error' ? 'alert' : 'status');
    el.innerHTML = `${TOAST_ICONS[kind] || TOAST_ICONS.info}<div class="min-w-0 flex-1">${message}</div>`;
    stack.appendChild(el);

    const dismiss = () => {
        el.classList.add('is-leaving');
        el.addEventListener('animationend', () => el.remove(), { once: true });
    };
    el.addEventListener('click', dismiss);
    if (timeout) setTimeout(dismiss, timeout);
}
window.adminToast = adminToast;

/* -------------------------------------------------------------------------
 | Modal controller (Bootstrap-compatible surface).
 | ------------------------------------------------------------------------- */
function openModal(modal) {
    if (!modal) return;
    if (!modal.querySelector('.modal-backdrop-el')) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop-el';
        backdrop.addEventListener('click', () => closeModal(modal));
        modal.prepend(backdrop);
    }
    modal.classList.add('is-open');
    modal.removeAttribute('aria-hidden');
    const focusable = modal.querySelector('input, textarea, select, button');
    if (focusable) setTimeout(() => focusable.focus(), 50);
}

function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
}

function wireModals(document) {
    // Open triggers: <button data-toggle="modal" data-target="#id">
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-toggle="modal"]');
        if (trigger) {
            e.preventDefault();
            const sel = trigger.getAttribute('data-target');
            if (sel) openModal(document.querySelector(sel));
            return;
        }
        const dismiss = e.target.closest('[data-dismiss="modal"]');
        if (dismiss) {
            e.preventDefault();
            closeModal(dismiss.closest('.modal'));
        }
    });

    // Esc closes the top-most open modal.
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const open = document.querySelector('.modal.is-open');
            if (open) closeModal(open);
        }
    });
}

/* -------------------------------------------------------------------------
 | Collapse controller — data-toggle="collapse" data-target="#id"
 | (also supports the legacy href="#id" form used by left-nav).
 | ------------------------------------------------------------------------- */
function wireCollapse(document) {
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-toggle="collapse"]');
        if (!trigger) return;
        e.preventDefault();
        const sel = trigger.getAttribute('data-target') || trigger.getAttribute('href');
        if (!sel || sel === '#') return;
        const target = document.querySelector(sel);
        if (!target) return;
        const open = target.classList.toggle('is-open');
        trigger.classList.toggle('collapsed', !open);
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
}

/* -------------------------------------------------------------------------
 | jQuery shims — installed once jQuery is available. The legacy module
 | scripts call $('#modal').modal('hide') and $.notify(...). We back both.
 | ------------------------------------------------------------------------- */
function installJqueryShims($) {
    if (!$ || $.fn.modal) return;

    $.fn.modal = function (action) {
        return this.each(function () {
            if (action === 'hide') closeModal(this);
            else if (action === 'show') openModal(this);
            else if (action === 'toggle') {
                this.classList.contains('is-open') ? closeModal(this) : openModal(this);
            }
        });
    };

    // bootstrap-notify replacement. showNotification() (in agentic-cms-laravel.js) calls
    // $.notify({ message }, { type, placement }). Map type -> toast kind.
    $.notify = function (content, options) {
        const message = (content && content.message) || '';
        const type = (options && options.type) || 'info';
        const kind = type === 'success' ? 'success' : type === 'danger' || type === 'error' ? 'error' : 'info';
        const timeout = (options && options.timer) || 4000;
        adminToast(message, kind, timeout);
    };
}

/* -------------------------------------------------------------------------
 | Dark-mode toggle (DESIGN_SYSTEM §5 / Phase 6).
 | Shares the same localStorage key `agentic-cms-theme` as front.js so toggling
 | in either shell persists across the whole site.
 | The no-FOUC inline script in header-styles.blade.php applies .dark before
 | first paint; this wires the topbar toggle button.
 | ------------------------------------------------------------------------- */
const THEME_KEY = 'agentic-cms-theme';

function adminResolveTheme() {
    const stored = localStorage.getItem(THEME_KEY);
    if (stored === 'dark' || stored === 'light') return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function adminApplyTheme(theme) {
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    return theme;
}

function initAdminDarkToggle() {
    const btn = document.querySelector('[data-dark-toggle]');
    if (!btn) return;

    function syncBtn(theme) {
        btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    }

    // Sync initial state from what the no-FOUC script already applied.
    syncBtn(adminResolveTheme());

    btn.addEventListener('click', () => {
        const current = adminResolveTheme();
        const next = current === 'dark' ? 'light' : 'dark';
        localStorage.setItem(THEME_KEY, next);
        adminApplyTheme(next);
        syncBtn(next);
    });

    // Honour OS-level changes when no explicit preference is stored.
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (!localStorage.getItem(THEME_KEY)) {
            const theme = adminResolveTheme();
            adminApplyTheme(theme);
            syncBtn(theme);
        }
    });
}

/* -------------------------------------------------------------------------
 | Boot
 | ------------------------------------------------------------------------- */
function boot() {
    wireModals(document);
    wireCollapse(document);
    initAdminDarkToggle();

    // `type` is the colour-name array the legacy showNotification(...) indexes
    // into (type[color]). bootstrap-notify's demo defined it globally; recreate
    // it so the call signature stays valid.
    if (typeof window.type === 'undefined') {
        window.type = ['', 'info', 'success', 'warning', 'danger', 'rose', 'primary'];
    }

    // jQuery is loaded in the footer; it may or may not be present yet.
    if (window.jQuery) {
        installJqueryShims(window.jQuery);
    } else {
        // Poll briefly for the footer-loaded jQuery, then install shims.
        let tries = 0;
        const iv = setInterval(() => {
            if (window.jQuery) {
                installJqueryShims(window.jQuery);
                clearInterval(iv);
            } else if (++tries > 100) {
                clearInterval(iv);
            }
        }, 30);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
