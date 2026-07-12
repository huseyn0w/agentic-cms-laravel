/*
 * AgenticCms-Laravel — accessible menu-builder reordering (DESIGN_SYSTEM §5).
 *
 * The menu builder used to be drag-only (jQuery UI nestedSortable loaded from a
 * googleapis CDN). §5 makes keyboard-accessible reordering MANDATORY and bans
 * CDN scripts (§7). This module replaces both:
 *
 *   - Keyboard path (primary, always works): every row gets Move up / Move down
 *     buttons (labelled) plus arrow-key support on the drag handle. Order changes
 *     are announced through an aria-live region.
 *   - Drag path (progressive enhancement): native HTML5 drag-and-drop — no
 *     third-party library, so nothing loads from a CDN. If a browser/AT does not
 *     support pointer drag, the keyboard path is fully sufficient.
 *
 * It does NOT touch the data-title / data-link / data-type attributes or the
 * nested <ul> structure that menu.js's buildJSON()/FetchChild() serialise on
 * save, so the existing save round-trip persists the new order unchanged.
 */
(function () {
    'use strict';

    function t(key, fallback) {
        return (window.menuReorderStrings && window.menuReorderStrings[key]) || fallback;
    }

    function ensureLiveRegion() {
        var region = document.getElementById('menu-reorder-live');
        if (!region) {
            region = document.createElement('div');
            region.id = 'menu-reorder-live';
            region.setAttribute('aria-live', 'polite');
            region.setAttribute('role', 'status');
            region.className = 'sr-only';
            document.body.appendChild(region);
        }
        return region;
    }

    function announce(message) {
        var region = ensureLiveRegion();
        // Clear then set so repeated identical messages are still announced.
        region.textContent = '';
        window.requestAnimationFrame(function () {
            region.textContent = message;
        });
    }

    function itemLabel(li) {
        return li.getAttribute('data-title') || t('item', 'Item');
    }

    function positionText(li) {
        var siblings = Array.prototype.filter.call(
            li.parentNode.children,
            function (n) { return n.tagName === 'LI'; }
        );
        var index = siblings.indexOf(li);
        return (index + 1) + ' ' + t('of', 'of') + ' ' + siblings.length;
    }

    function move(li, direction) {
        if (direction === 'up') {
            var prev = li.previousElementSibling;
            while (prev && prev.tagName !== 'LI') { prev = prev.previousElementSibling; }
            if (!prev) {
                announce(itemLabel(li) + ' ' + t('at_top', 'is already first.'));
                return false;
            }
            li.parentNode.insertBefore(li, prev);
        } else {
            var next = li.nextElementSibling;
            while (next && next.tagName !== 'LI') { next = next.nextElementSibling; }
            if (!next) {
                announce(itemLabel(li) + ' ' + t('at_bottom', 'is already last.'));
                return false;
            }
            li.parentNode.insertBefore(next, li);
        }
        announce(
            itemLabel(li) + ' ' + t('moved', 'moved to position') + ' ' + positionText(li) + '.'
        );
        return true;
    }

    function makeButton(label, glyph, onClick) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'menu-reorder-btn';
        btn.setAttribute('aria-label', label);
        btn.title = label;
        btn.textContent = glyph;
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            onClick();
        });
        return btn;
    }

    function enhance(li) {
        if (li.__reorderEnhanced) return;
        li.__reorderEnhanced = true;

        var anchor = li.querySelector(':scope > a');
        if (!anchor) return;

        // Controls container (kept as the first children of the row anchor so the
        // grip reads before the label, matching the §5 "leading drag handle").
        var controls = document.createElement('span');
        controls.className = 'menu-reorder-controls';

        // Drag handle — labelled, keyboard-operable (arrow keys reorder).
        var handle = document.createElement('span');
        handle.className = 'menu-reorder-handle';
        handle.setAttribute('role', 'button');
        handle.setAttribute('tabindex', '0');
        handle.setAttribute('aria-label', t('reorder', 'Reorder') + ' ' + itemLabel(li));
        handle.textContent = '☰'; // ☰ grip glyph
        handle.setAttribute('draggable', 'true');
        handle.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowUp') { e.preventDefault(); move(li, 'up'); handle.focus(); }
            else if (e.key === 'ArrowDown') { e.preventDefault(); move(li, 'down'); handle.focus(); }
        });

        var upBtn = makeButton(
            t('move_up', 'Move up') + ': ' + itemLabel(li), '↑',
            function () { move(li, 'up'); upBtn.focus(); }
        );
        var downBtn = makeButton(
            t('move_down', 'Move down') + ': ' + itemLabel(li), '↓',
            function () { move(li, 'down'); downBtn.focus(); }
        );

        controls.appendChild(handle);
        controls.appendChild(upBtn);
        controls.appendChild(downBtn);
        anchor.insertBefore(controls, anchor.firstChild);

        // --- Native HTML5 drag (progressive enhancement, no library) ---------
        handle.addEventListener('dragstart', function (e) {
            li.classList.add('menu-reorder-dragging');
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', ''); } catch (err) { /* IE */ }
            window.__menuDragged = li;
        });
        handle.addEventListener('dragend', function () {
            li.classList.remove('menu-reorder-dragging');
            window.__menuDragged = null;
            announce(itemLabel(li) + ' ' + t('moved', 'moved to position') + ' ' + positionText(li) + '.');
        });
        li.addEventListener('dragover', function (e) {
            var dragged = window.__menuDragged;
            if (!dragged || dragged === li) return;
            // Only reorder within the same list (same nesting level).
            if (dragged.parentNode !== li.parentNode) return;
            e.preventDefault();
            var rect = li.getBoundingClientRect();
            var after = (e.clientY - rect.top) > rect.height / 2;
            li.parentNode.insertBefore(dragged, after ? li.nextElementSibling : li);
        });
    }

    function enhanceAll(root) {
        (root || document).querySelectorAll('.menu-list li').forEach(enhance);
    }

    function boot() {
        var container = document.querySelector('.menu-box');
        if (!container) return;

        ensureLiveRegion();
        enhanceAll(container);

        // menu.js appends rows dynamically (add-to-menu / custom link); enhance
        // them as they arrive so the keyboard path always covers every row.
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.matches && node.matches('li')) enhance(node);
                    if (node.querySelectorAll) node.querySelectorAll('li').forEach(enhance);
                });
            });
        });
        observer.observe(container, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
