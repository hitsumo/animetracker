/**
 * Anime Tracker - Series timeline in-page tabs
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * 1.2.2. series_timeline.php draws every list tab (own chain, other
 * chains, air date) as a panel in ONE page; this script switches panels
 * without a reload. The tabs stay real links (?chain= / ?mode=): a middle
 * click, a modified click or a browser without this script still lands on
 * the server-rendered page with that panel open.
 *
 * - The address drops ?chain= / ?mode= and carries the panel as a hash
 *   (#chain-65, #airdate; none for the own chain), so a shared link opens
 *   the same panel. The hash matches no element id - no scroll jump.
 * - Chain <-> air date is the choice 1.1.23 keeps in the session. A click
 *   that changes it is written in the background through the existing
 *   set_series_timeline_mode.php (CSRF). Other-chain tabs are not written
 *   (1.1.25), exactly as before.
 */
(function () {
    'use strict';

    var list = document.querySelector('.st-tabs[role="tablist"]');
    if (!list) {
        return;
    }
    var tabs = list.querySelectorAll('a[data-panel]');
    if (!tabs.length) {
        return;
    }
    var countEl   = document.getElementById('st-count');
    var csrf      = list.getAttribute('data-csrf') || '';
    var savedMode = list.getAttribute('data-saved-mode') || '';

    function panelFor(key) {
        return document.getElementById('st-panel-' + key);
    }

    function activate(key) {
        var panel = panelFor(key);
        if (!panel) {
            return null;
        }
        var mode = null;
        Array.prototype.forEach.call(tabs, function (tab) {
            var on = tab.getAttribute('data-panel') === key;
            tab.classList.toggle('active', on);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            if (on) {
                mode = tab.getAttribute('data-mode') || '';
            }
        });
        Array.prototype.forEach.call(document.querySelectorAll('.st-panel'), function (p) {
            p.classList.toggle('is-active', p === panel);
        });
        if (countEl && panel.getAttribute('data-count-text')) {
            countEl.textContent = panel.getAttribute('data-count-text');
        }
        return mode;
    }

    function rememberMode(mode) {
        if (!mode || mode === savedMode || !csrf || !window.fetch) {
            return;
        }
        savedMode = mode;
        var body = new FormData();
        body.append('csrf_token', csrf);
        body.append('mode', mode);
        // The endpoint answers with a redirect to the referring page; the
        // page is already on screen, so the redirect is not followed.
        // keepalive: a click followed at once by a link to another page
        // would otherwise cancel the request (seen in testing).
        fetch('set_series_timeline_mode.php', {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            redirect: 'manual',
            keepalive: true
        }).catch(function () { /* a lost preference is not worth a message */ });
    }

    function updateAddress(key) {
        if (!window.history || !history.replaceState || !window.URL) {
            return;
        }
        try {
            var url = new URL(window.location.href);
            url.searchParams.delete('mode');
            url.searchParams.delete('chain');
            url.hash = (key === 'chain') ? '' : key;
            history.replaceState(null, '', url.toString());
        } catch (e) { /* the address is a convenience, never a blocker */ }
    }

    Array.prototype.forEach.call(tabs, function (tab) {
        tab.addEventListener('click', function (ev) {
            if (ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) {
                return;
            }
            var key = tab.getAttribute('data-panel');
            var mode = activate(key);
            if (mode === null) {
                return; // no such panel on this page - let the link work
            }
            ev.preventDefault();
            updateAddress(key);
            rememberMode(mode);
        });
    });

    // A shared link: #chain-65 / #airdate opens that panel. Not written to
    // the session - opening someone's link is not choosing a default.
    var hash = window.location.hash.replace(/^#/, '');
    if (hash !== '' && panelFor(hash)) {
        activate(hash);
    }
})();
