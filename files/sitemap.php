<?php

/**
 * Anime Tracker - XML sitemap generator
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.30.
 *
 * Served as /sitemap.xml through the rewrite in files/.htaccess, and
 * reachable directly as sitemap.php in installs that live in a
 * subdirectory (where the domain root is not ours to claim).
 *
 * WHY A PHP FILE AND NOT A GENERATED .xml
 *
 * Two reasons, and the first one alone settles it:
 *   1. The content depends on the mode. A self-host install must publish
 *      NO sitemap at all (see seo_helpers.php), and a static file cannot
 *      know which mode it is in.
 *   2. The catalog changes whenever an anime is added or imported. A file
 *      would need a regeneration step that someone has to remember; a
 *      generator is always current.
 *
 * OUTPUT SHAPES
 *
 *   sitemap.php          small catalog  -> one <urlset>
 *                        large catalog  -> a <sitemapindex> pointing at
 *                                          the chunks below
 *   sitemap.php?p=N      chunk N (1-based) as a <urlset>
 *
 * The index links its children as sitemap.php?p=N rather than pretty
 * sitemap-N.xml URLs: a query string is valid in a sitemap index and
 * works in every install layout, whereas the pretty form would depend on
 * a rewrite that a subdirectory install does not have.
 *
 * NOTHING HERE IS USER-SPECIFIC. The URLs come from the animes table,
 * which is the shared catalog; per-user watch state (user_anime) is never
 * touched, so the same sitemap is correct for every visitor and for an
 * anonymous crawler.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Self-host mode publishes no sitemap. 404 rather than 403: there is
// genuinely no such document on this installation, and a crawler drops a
// 404 from its schedule instead of retrying it.
if (!seo_indexing_allowed()) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "No sitemap on this installation.\n";
    exit;
}

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

$total  = seo_sitemap_anime_count($pdo);
$chunks = max(1, (int)ceil($total / SEO_SITEMAP_CHUNK));

// ?p= is 1-based. 0 / absent means "the entry document".
$page = isset($_GET['p']) ? (int)$_GET['p'] : 0;
if ($page < 0 || $page > $chunks) {
    $page = 0;
}

/**
 * Print the <url> blocks of one entry - one per language it is published
 * in (1.2.0).
 *
 * The loc is escaped: it carries a query string, and a bare '&' would
 * make the document invalid XML.
 *
 * 'langs' lists the languages of the entry; absent means every language
 * (static pages, chronology, series). When there is more than one, each
 * block carries the full set of xhtml:link alternates plus x-default -
 * the same set the page itself prints in its <head> (seo_head()), since
 * search engines ignore alternates that do not point back at each other.
 * A detail page published in Turkish only gets a single plain block.
 */
function sitemap_url_node(array $entry) {
    $langs = isset($entry['langs']) ? $entry['langs'] : seo_langs();
    if (!$langs) {
        return;
    }

    $alternates = [];
    if (count($langs) > 1) {
        foreach (seo_alternate_locs($entry['loc']) as $hl => $path) {
            if ($hl === 'x-default' || in_array($hl, $langs, true)) {
                $alternates[$hl] = htmlspecialchars(seo_url($path), ENT_QUOTES, 'UTF-8');
            }
        }
    }

    foreach ($langs as $lang) {
        sitemap_url_block($entry, seo_lang_path($entry['loc'], $lang), $alternates);
    }
}

/**
 * Print a single <url> block.
 */
function sitemap_url_block(array $entry, $path, array $alternates) {
    $loc = htmlspecialchars(seo_url($path), ENT_QUOTES, 'UTF-8');

    echo "  <url>\n";
    echo "    <loc>" . $loc . "</loc>\n";
    foreach ($alternates as $hl => $href) {
        echo '    <xhtml:link rel="alternate" hreflang="' . htmlspecialchars($hl, ENT_QUOTES, 'UTF-8')
            . '" href="' . $href . '"/>' . "\n";
    }
    if (!empty($entry['lastmod'])) {
        echo "    <lastmod>" . htmlspecialchars($entry['lastmod'], ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
    }
    if (!empty($entry['changefreq'])) {
        echo "    <changefreq>" . htmlspecialchars($entry['changefreq'], ENT_QUOTES, 'UTF-8') . "</changefreq>\n";
    }
    if (!empty($entry['priority'])) {
        echo "    <priority>" . htmlspecialchars($entry['priority'], ENT_QUOTES, 'UTF-8') . "</priority>\n";
    }
    echo "  </url>\n";
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

// ---------------------------------------------------------------------
// Sitemap index (only when the catalog outgrew a single chunk)
// ---------------------------------------------------------------------
if ($page === 0 && $chunks > 1) {
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    for ($i = 1; $i <= $chunks; $i++) {
        $loc = htmlspecialchars(seo_url('sitemap.php?p=' . $i), ENT_QUOTES, 'UTF-8');
        echo "  <sitemap>\n";
        echo "    <loc>" . $loc . "</loc>\n";
        echo "  </sitemap>\n";
    }
    echo '</sitemapindex>' . "\n";
    exit;
}

// ---------------------------------------------------------------------
// A urlset: either the whole site (small catalog) or one chunk
// ---------------------------------------------------------------------
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
    . ' xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

// The static pages ride along with the first chunk (and with the single
// urlset of a small catalog) so they appear exactly once.
if ($page <= 1) {
    foreach (seo_sitemap_static_entries() as $entry) {
        sitemap_url_node($entry);
    }
}

$offset = $page > 0 ? ($page - 1) * SEO_SITEMAP_CHUNK : 0;
$limit  = $page > 0 ? SEO_SITEMAP_CHUNK : max(SEO_SITEMAP_CHUNK, $total);

foreach (seo_sitemap_anime_entries($pdo, $offset, $limit) as $entry) {
    sitemap_url_node($entry);
}

echo '</urlset>' . "\n";
