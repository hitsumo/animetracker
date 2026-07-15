/**
 * Anime Tracker - Select enhancer (1.1.11)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * A native <select> popup cannot be capped to N visible rows via CSS. This
 * progressively enhances every LONG native <select> (more than 8 options)
 * into a custom dropdown whose panel shows at most 8 rows, the rest
 * scrolling. Short selects (<= 8 options) are left untouched - the native
 * popup already fits.
 *
 * Design / safety:
 *   - The native <select> stays the source of truth: it is kept in the DOM
 *     (submitted with the form) and only visually hidden (sr-only clip, NOT
 *     display:none, so constraint validation can still focus a `required`
 *     control). Picking an option sets the native value and dispatches a
 *     native 'change' event, so any existing onchange handler still fires.
 *   - A 'change' listener on the native select re-syncs the button label and
 *     selected row, so code that sets select.value elsewhere stays in sync.
 *   - Only enhances on a fine pointer (desktop/mouse); touch devices keep the
 *     native picker, which is the better mobile UX.
 *   - Opt out any select with `data-no-enhance` (e.g. a picker whose options
 *     change at runtime or which resets its own value).
 *
 * Loaded via a plain <script> tag on every page that has selects.
 */
(function () {
    'use strict';

    // Touch / coarse pointer: keep the native picker.
    if (!(window.matchMedia && window.matchMedia('(pointer: fine)').matches)) {
        return;
    }

    var MAX_ROWS = 8;

    function enhance(sel) {
        if (sel.__cselDone) return;
        sel.__cselDone = true;

        var wrap = document.createElement('div');
        wrap.className = 'csel';
        sel.parentNode.insertBefore(wrap, sel);
        wrap.appendChild(sel);
        sel.tabIndex = -1; // custom button is the tab stop; select stays focusable for validation

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'csel-btn';
        btn.setAttribute('aria-haspopup', 'listbox');
        btn.setAttribute('aria-expanded', 'false');

        var panel = document.createElement('ul');
        panel.className = 'csel-panel';
        panel.setAttribute('role', 'listbox');

        function syncBtn() {
            var o = sel.options[sel.selectedIndex];
            btn.textContent = (o ? o.text.trim() : '') || ' ';
        }
        function updateSelected() {
            for (var i = 0; i < panel.children.length; i++) {
                panel.children[i].setAttribute(
                    'aria-selected', (i === sel.selectedIndex) ? 'true' : 'false'
                );
            }
        }
        function buildOptions() {
            panel.innerHTML = '';
            Array.prototype.forEach.call(sel.options, function (opt, i) {
                var li = document.createElement('li');
                li.className = 'csel-opt';
                li.setAttribute('role', 'option');
                li.textContent = opt.text.trim();
                li.addEventListener('click', function () {
                    sel.selectedIndex = i;
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                    syncBtn();
                    updateSelected();
                    close();
                    btn.focus();
                });
                panel.appendChild(li);
            });
            updateSelected();
        }

        var sized = false;
        function sizePanel() {
            // Cap the visible height at exactly MAX_ROWS rows (measured, so it
            // is correct regardless of font/line-height); the rest scrolls.
            if (sized) return;
            var first = panel.children[0];
            if (!first) return;
            var rowH = first.getBoundingClientRect().height;
            if (rowH > 0) { panel.style.maxHeight = (rowH * MAX_ROWS) + 'px'; sized = true; }
        }
        function open() {
            wrap.classList.add('open');
            sizePanel();
            btn.setAttribute('aria-expanded', 'true');
            var cur = panel.children[sel.selectedIndex];
            if (cur) cur.scrollIntoView({ block: 'nearest' });
            document.addEventListener('mousedown', outside);
            document.addEventListener('keydown', onKey);
        }
        function close() {
            wrap.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
            document.removeEventListener('mousedown', outside);
            document.removeEventListener('keydown', onKey);
        }
        function outside(e) { if (!wrap.contains(e.target)) close(); }
        function onKey(e) {
            if (e.key === 'Escape') { close(); btn.focus(); return; }
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                var ni = sel.selectedIndex + (e.key === 'ArrowDown' ? 1 : -1);
                if (ni < 0) ni = 0;
                if (ni > sel.options.length - 1) ni = sel.options.length - 1;
                sel.selectedIndex = ni;
                syncBtn();
                updateSelected();
                var cur = panel.children[ni];
                if (cur) cur.scrollIntoView({ block: 'nearest' });
            }
            if (e.key === 'Enter') { e.preventDefault(); close(); btn.focus(); }
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            wrap.classList.contains('open') ? close() : open();
        });

        // Keep the button in sync if some other script changes the value.
        sel.addEventListener('change', function () { syncBtn(); updateSelected(); });

        buildOptions();
        syncBtn();
        wrap.appendChild(btn);
        wrap.appendChild(panel);
    }

    function run() {
        var selects = document.querySelectorAll(
            'select:not([multiple]):not([disabled]):not([data-no-enhance])'
        );
        Array.prototype.forEach.call(selects, function (sel) {
            if (sel.options && sel.options.length > MAX_ROWS) {
                enhance(sel);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
