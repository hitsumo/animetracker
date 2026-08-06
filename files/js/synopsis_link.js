/**
 * Anime Tracker - Synopsis link picker (1.1.26)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Writing aid for the synopsis shortcode introduced in 1.1.19:
 *
 *     [[anime:52991|Frieren]]  ->  a link to that anime's detail page
 *
 * Until now the curator had to type that code by hand and know the target's
 * MAL number. This attaches a "anime baglantisi ekle" button to every
 * textarea marked data-synopsis-link, searches the catalog as you type
 * (anime_link_search.php) and inserts the finished shortcode at the caret.
 *
 * Design / safety:
 *   - PROGRESSIVE ENHANCEMENT. The textarea is untouched if this file fails
 *     to load or the endpoint errors: the curator can still type the code
 *     by hand exactly as in 1.1.19..1.1.25. Nothing here is required to
 *     save the form.
 *   - The panel writes into the textarea and NOTHING else. It never submits,
 *     never touches another field, and never rewrites text the curator
 *     already wrote - it only splices at the caret (or at the end when the
 *     textarea was never focused).
 *   - Result rows are built with textContent / createElement, never innerHTML,
 *     so a catalog title containing markup cannot become live DOM here.
 *   - Labels are sanitized before they enter the shortcode: `]` terminates
 *     the pattern (`[^\]]*`, a known limit recorded in KARARLAR_4 sec.69) and
 *     `|` would open a second field, so both are stripped rather than
 *     silently producing a broken code.
 *   - All user-facing text comes from LANG (see the pages' LANG block), so
 *     the picker follows the interface language like every other surface.
 *
 * Loaded via a plain <script src> on add_anime.php and edit_anime.php.
 */
(function () {
    'use strict';

    // Wait this long after the last keystroke before asking the server. Long
    // enough that typing a title is one request, short enough to feel live.
    var DEBOUNCE_MS = 250;

    // Mirrors ANIME_LINK_SEARCH_MIN in anime_link_search.php. Kept here too
    // so a too-short query never leaves the browser at all.
    var MIN_CHARS = 2;

    // Fallback strings, used only if a page forgets a LANG key. English on
    // purpose: a missing translation should read as untranslated, not crash.
    var FALLBACK = {
        synlink_btn: 'Add anime link',
        synlink_search_ph: 'Search anime...',
        synlink_searching: 'Searching...',
        synlink_no_results: 'No matching anime',
        synlink_failed: 'Search failed',
        synlink_hint: 'Pick an anime to insert a link into the text.',
        synlink_close: 'Close'
    };

    function txt(key) {
        // The pages declare their strings as `const LANG = {...}` at the top
        // level of an inline <script>. A top-level `const` lives in the global
        // LEXICAL scope, NOT on `window` - so `window.LANG` is undefined here
        // and only the bare name resolves. Guarded with typeof because a page
        // that never declares LANG would otherwise throw a ReferenceError.
        var L = (typeof LANG !== 'undefined' && LANG) ? LANG : {};
        return (typeof L[key] === 'string' && L[key] !== '') ? L[key] : FALLBACK[key];
    }

    /**
     * Reduce a catalog title to something that is safe INSIDE the shortcode.
     *
     * `]` would end the pattern early (the label is `[^\]]*`, a known limit
     * recorded in KARARLAR_4 sec.69) and `|` would look like a second field,
     * so both are dropped. `[` is dropped WITH its partner even though it is
     * harmless to the pattern: keeping it alone turns "Ghost in the Shell
     * [2.0]" into the visibly broken label "Ghost in the Shell [2.0". Losing
     * both brackets reads as ordinary text; losing one reads as a bug.
     *
     * Whitespace is collapsed so a title carrying a stray newline cannot
     * split the shortcode across two lines.
     */
    function safeLabel(title) {
        return String(title || '')
            .replace(/[\[\]|]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    /**
     * Insert `text` at the caret of `ta`, keeping undo history where the
     * browser supports it (execCommand) and falling back to a value splice.
     * The caret ends up after the inserted text either way, so the curator
     * can keep typing the sentence.
     */
    function insertAtCaret(ta, text) {
        ta.focus();

        // execCommand is deprecated but is still the only way to write into a
        // textarea WITHOUT destroying the browser's undo stack. If it is gone
        // or refuses, the manual splice below does the same edit; the only
        // thing lost is Ctrl+Z granularity.
        var ok = false;
        try {
            ok = document.execCommand && document.execCommand('insertText', false, text);
        } catch (e) {
            ok = false;
        }
        if (ok) return;

        var start = ta.selectionStart;
        var end   = ta.selectionEnd;
        if (typeof start !== 'number' || typeof end !== 'number') {
            // No caret information (never focused): append.
            ta.value += text;
            return;
        }
        ta.value = ta.value.slice(0, start) + text + ta.value.slice(end);
        var pos = start + text.length;
        ta.setSelectionRange(pos, pos);
        // Fire input so anything listening for edits (autosave, dirty flags)
        // sees this the way it sees typing.
        ta.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function buildPicker(ta) {
        var wrap = document.createElement('div');
        wrap.className = 'synlink';

        var btn = document.createElement('button');
        btn.type = 'button';                 // never submits the form
        btn.className = 'synlink-btn';
        btn.textContent = txt('synlink_btn');

        var panel = document.createElement('div');
        panel.className = 'synlink-panel';
        panel.hidden = true;

        var hint = document.createElement('small');
        hint.className = 'synlink-hint';
        hint.textContent = txt('synlink_hint');

        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'synlink-input';
        input.placeholder = txt('synlink_search_ph');
        // Not a form control we want submitted or restored by the browser.
        input.autocomplete = 'off';

        var list = document.createElement('ul');
        list.className = 'synlink-results';

        var status = document.createElement('div');
        status.className = 'synlink-status';

        panel.appendChild(hint);
        panel.appendChild(input);
        panel.appendChild(status);
        panel.appendChild(list);
        wrap.appendChild(btn);
        wrap.appendChild(panel);

        // Sits directly under the textarea, inside the same .input-area, so
        // the picker travels with the field it belongs to.
        ta.parentNode.insertBefore(wrap, ta.nextSibling);

        var timer   = null;
        var lastReq = 0;   // request sequence: a slow answer never overwrites a newer one

        function clearList() {
            while (list.firstChild) list.removeChild(list.firstChild);
        }

        function setStatus(msg) {
            status.textContent = msg || '';
        }

        function choose(item) {
            var label = safeLabel(item.title);
            var code = label !== ''
                ? '[[anime:' + item.mal_id + '|' + label + ']]'
                : '[[anime:' + item.mal_id + ']]';
            insertAtCaret(ta, code);
            close();
        }

        function render(results) {
            clearList();
            if (!results.length) {
                setStatus(txt('synlink_no_results'));
                return;
            }
            setStatus('');
            results.forEach(function (item) {
                var li = document.createElement('li');
                var row = document.createElement('button');
                row.type = 'button';
                row.className = 'synlink-result';

                var name = document.createElement('span');
                name.className = 'synlink-result-title';
                name.textContent = item.title;      // textContent: no markup can enter
                row.appendChild(name);

                // Year / type disambiguate remakes and movie versions that
                // share a title; the mal_id is shown because it is what the
                // code actually carries and the curator may want to read it.
                var bits = [];
                if (item.year) bits.push(item.year);
                if (item.media_type) bits.push(item.media_type);
                bits.push('MAL ' + item.mal_id);

                var meta = document.createElement('span');
                meta.className = 'synlink-result-meta';
                meta.textContent = bits.join(' · ');
                row.appendChild(meta);

                row.addEventListener('click', function () { choose(item); });
                li.appendChild(row);
                list.appendChild(li);
            });
        }

        function search() {
            var q = input.value.trim();
            if (q.length < MIN_CHARS) {
                clearList();
                setStatus('');
                return;
            }

            var seq = ++lastReq;
            setStatus(txt('synlink_searching'));

            fetch('anime_link_search.php?q=' + encodeURIComponent(q), {
                credentials: 'same-origin'
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (seq !== lastReq) return;   // a newer query is already in flight
                    if (!data || !data.success) {
                        clearList();
                        setStatus(txt('synlink_failed'));
                        return;
                    }
                    render(data.results || []);
                })
                .catch(function () {
                    if (seq !== lastReq) return;
                    clearList();
                    setStatus(txt('synlink_failed'));
                });
        }

        function open() {
            panel.hidden = false;
            btn.setAttribute('aria-expanded', 'true');
            input.focus();
        }

        function close() {
            panel.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
        }

        btn.setAttribute('aria-expanded', 'false');
        btn.addEventListener('click', function () {
            if (panel.hidden) { open(); } else { close(); }
        });

        input.addEventListener('input', function () {
            if (timer) clearTimeout(timer);
            timer = setTimeout(search, DEBOUNCE_MS);
        });

        // Enter inside the search box must not submit the whole anime form.
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (timer) clearTimeout(timer);
                search();
            } else if (e.key === 'Escape') {
                close();
                ta.focus();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var fields = document.querySelectorAll('textarea[data-synopsis-link]');
        Array.prototype.forEach.call(fields, function (ta) {
            if (ta.readOnly || ta.disabled) return;   // readonly catalog synopsis (Mod 2)
            buildPicker(ta);
        });
    });
})();
