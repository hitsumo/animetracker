<?php

/**
 * Anime Tracker - Synopsis Helpers (inline anime links in plot summaries)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.19. Lets a synopsis reference another anime with an
 * inline, clickable link using a safe shortcode:
 *
 *     [[anime:52991|Frieren]]  ->  <a href="anime_details.php?id=<local>">Frieren</a>
 *     [[anime:52991]]          ->  link labelled with the target's own title
 *
 * WHY A SHORTCODE AND NOT RAW HTML: the synopsis is user/curator text and
 * synopsis_tr/synopsis_en travel over the catalog wire to every member.
 * Allowing raw <a> would open stored XSS and let arbitrary external URLs
 * ride the catalog. The shortcode carries only a MAL id, everything else
 * stays htmlspecialchars-escaped, and the link target is always a local
 * anime_details.php row - never an attacker-chosen URL.
 *
 * WHY MAL ID AND NOT THE LOCAL animes.id: animes is the CENTRAL CATALOG.
 * The same synopsis text is served to every instance, but animes.id is
 * assigned per-instance (AUTO_INCREMENT), so a raw local id would point at
 * a different (or missing) row elsewhere. mal_id is globally stable and
 * UNIQUE, so render_synopsis() resolves it to THIS instance's local id at
 * display time. If no local row carries that mal_id (the referenced anime
 * is not in this catalog), the link degrades to the author's plain-text
 * label so the sentence still reads.
 *
 * Loaded via the functions.php loader. Rendering surfaces:
 *   - anime_details.php: render_synopsis() (full text, clickable links)
 *   - recommendations.php: synopsis_plain() (truncated teaser, no links)
 */

/**
 * Shortcode grammar: [[anime:<mal_id>]] or [[anime:<mal_id>|label]].
 * mal_id is digits; label is any run without a closing bracket. The
 * pattern is applied to htmlspecialchars-escaped text, which is safe
 * because none of the delimiters ([ ] : |) are altered by escaping.
 */
const SYNOPSIS_ANIME_SHORTCODE = '/\[\[anime:(\d+)(?:\|([^\]]*))?\]\]/';

/**
 * Render a synopsis as safe HTML, turning [[anime:<mal_id>|label]]
 * shortcodes into links to the local anime_details.php row.
 *
 * The whole string is htmlspecialchars-escaped FIRST, so every piece of
 * free text is XSS-safe; the shortcode delimiters survive escaping intact,
 * so the replacement still matches. Newlines become <br> last, mirroring
 * the previous nl2br(htmlspecialchars(...)) behaviour for plain synopses.
 *
 * @param PDO         $pdo
 * @param string|null $text  Raw synopsis text.
 * @return string  HTML-safe markup (may contain <a> and <br>).
 */
function render_synopsis($pdo, $text) {
    $text = (string) $text;
    if ($text === '') {
        return '';
    }

    // Escape first: all author free text is now inert. The shortcode
    // delimiters [[ ]] : | are untouched by htmlspecialchars, so the
    // pattern below still matches on the escaped string, and any label
    // captured from it is ALREADY escaped (must not be escaped again).
    $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    if (preg_match_all(SYNOPSIS_ANIME_SHORTCODE, $escaped, $ms) && !empty($ms[1])) {
        $malIds = array_values(array_unique(array_map('intval', $ms[1])));
        $placeholders = implode(',', array_fill(0, count($malIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT id, mal_id, title, alternative_titles FROM animes WHERE mal_id IN ($placeholders)"
        );
        $stmt->execute($malIds);
        $byMalId = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $byMalId[(int) $row['mal_id']] = $row;
        }

        $escaped = preg_replace_callback(
            SYNOPSIS_ANIME_SHORTCODE,
            function ($m) use ($byMalId) {
                $malId = (int) $m[1];
                $label = isset($m[2]) ? trim($m[2]) : '';   // already escaped
                $row   = $byMalId[$malId] ?? null;

                if ($row) {
                    if ($label === '') {
                        // display_title() reads a RAW DB value -> escape here.
                        $label = htmlspecialchars(display_title($row), ENT_QUOTES, 'UTF-8');
                    }
                    // href is a fixed path plus an integer id: no user data.
                    $href = 'anime_details.php?id=' . (int) $row['id'];
                    return '<a class="synopsis-link" href="' . $href . '">' . $label . '</a>';
                }

                // Not in this catalog: fall back to the author's label as
                // plain text so the sentence stays readable. A label-less
                // unresolved shortcode simply disappears.
                return $label;
            },
            $escaped
        );
    }

    return nl2br($escaped);
}

/**
 * Flatten a synopsis to plain text for previews/teasers: strips the
 * shortcode markup down to its label (or nothing when label-less) WITHOUT
 * any DB lookup or escaping. Callers still escape the result themselves,
 * exactly as they did before this feature existed.
 *
 * Used where the synopsis is truncated to a snippet (e.g. the surprise
 * recommendation card), so a shortcode never shows its raw [[...]] form
 * and truncation never slices a shortcode in half.
 *
 * @param string|null $text  Raw synopsis text.
 * @return string  Plain text with shortcodes reduced to their labels.
 */
function synopsis_plain($text) {
    $text = (string) $text;
    if ($text === '') {
        return '';
    }
    return preg_replace_callback(
        SYNOPSIS_ANIME_SHORTCODE,
        function ($m) {
            return isset($m[2]) ? trim($m[2]) : '';
        },
        $text
    );
}
