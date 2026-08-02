<?php

/**
 * Anime Tracker - Asset Helpers (version-stamped css/js URLs)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.24.
 *
 * WHY: every page used to link its assets with a bare, never-changing
 * URL (<link href="style.css">, <script src="js/select_enhance.js">).
 * A browser that already has those URLs cached keeps serving the OLD
 * file after an upgrade, so new markup meets old CSS - which is exactly
 * what produced the broken layout seen right after the 1.1.20 deploy.
 * Adding ?v={version} to every local asset URL changes the URL whenever
 * the version changes, so the browser is forced to refetch. Nothing is
 * cached less aggressively than before: within one version the URL is
 * stable and stays cacheable forever.
 *
 * WHY THE MODULES ARE LINKED ONE BY ONE: style.css is only a loader -
 * the real rules live in css/*.css and are pulled in with @import (see
 * the split in 0.6.7). Stamping the loader alone would NOT help: the
 * browser re-reads style.css, sees the same unstamped @import URLs and
 * happily serves css/components.css from cache. So asset_styles() emits
 * one stamped <link> per module instead, in the loader's own import
 * order. The module list is READ FROM style.css at runtime, so adding a
 * module there remains the only step needed - there is no second list
 * to keep in sync.
 *
 * Loaded via the functions.php loader. Pages that do not include
 * functions.php (setup/install/ai_notice) require this file directly -
 * it is deliberately self-contained: no DB, no i18n, no config, so it
 * works before the app is even installed.
 */

/**
 * The stamp appended to local asset URLs: the installed code version.
 *
 * Source is files/version.txt, NOT the settings.version DB row. The
 * assets being stamped are FILES, so they must be paired with the file
 * version: a deploy where the files landed but the migration has not
 * run yet still has to serve fresh CSS. version.txt also works with no
 * database at all, which setup.php needs.
 *
 * The value is validated before use - it lands in a URL, and a stray
 * newline or quote from a hand-edited version.txt should not be able to
 * reach the page. Anything unexpected is treated as "no version".
 *
 * @return string Version string, or '' if version.txt is unusable.
 */
function asset_version() {
    static $version = null;
    if ($version !== null) {
        return $version;
    }

    $version = '';
    $file = dirname(__DIR__) . '/version.txt';
    if (is_readable($file)) {
        $raw = trim((string)file_get_contents($file));
        if ($raw !== '' && preg_match('/^[0-9A-Za-z._-]{1,32}$/', $raw)) {
            $version = $raw;
        }
    }

    return $version;
}

/**
 * Build a version-stamped URL for a local asset.
 *
 * @param string $path Asset path relative to the files/ root,
 *                     e.g. 'js/select_enhance.js' or 'css/base.css'.
 * @param string $base Prefix that gets from the CALLING page to the
 *                     files/ root: '' for pages in files/, '../' for
 *                     pages in files/admin/ and files/help/.
 * @return string      Ready-to-print URL.
 */
function asset_url($path, $base = '') {
    $stamp = asset_version();

    if ($stamp === '') {
        // No usable version.txt (dev checkout, or a half-finished
        // upload). Fall back to the asset's own mtime so an edited file
        // still gets a fresh URL instead of silently serving a stale
        // cached copy. If even that fails, link the asset unstamped -
        // a missing stamp must never cost us the stylesheet itself.
        $local = dirname(__DIR__) . '/' . $path;
        $mtime = is_file($local) ? @filemtime($local) : false;
        if ($mtime === false) {
            return $base . $path;
        }
        $stamp = (string)$mtime;
    }

    return $base . $path . '?v=' . rawurlencode($stamp);
}

/**
 * The stylesheet modules to link, in cascade order.
 *
 * Read straight out of style.css's @import list, which stays the single
 * source of truth for both the order and the set of modules. Import
 * order is significant (it mirrors the original single-file top-to-
 * bottom order), so the parsed order is preserved as-is.
 *
 * If style.css cannot be read or contains no @import at all, the loader
 * itself is returned as the only module: the page then falls back to
 * the pre-1.1.24 behavior (styles load, module URLs unstamped) instead
 * of rendering unstyled.
 *
 * @return array List of paths relative to the files/ root.
 */
function asset_style_modules() {
    static $modules = null;
    if ($modules !== null) {
        return $modules;
    }

    $modules = [];
    $loader = dirname(__DIR__) . '/style.css';
    if (is_readable($loader)) {
        $css = (string)file_get_contents($loader);
        // Line-anchored on purpose: only real @import statements match,
        // never the word "import" inside the file's header comment.
        if (preg_match_all(
                '/^\s*@import\s+url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/mi',
                $css,
                $matches
            )) {
            foreach ($matches[1] as $path) {
                $path = trim($path);
                // Absolute / remote imports are left to the loader -
                // they are not ours to stamp.
                if ($path !== ''
                    && strpos($path, '//') === false
                    && strpos($path, ':') === false) {
                    $modules[] = $path;
                }
            }
        }
    }

    if (!$modules) {
        $modules = ['style.css'];
    }

    return $modules;
}

/**
 * Print-ready <link> tags for the whole stylesheet set, stamped.
 *
 * Replaces the single <link rel="stylesheet" href="style.css"> that
 * every page carried before 1.1.24. Call it where that line stood so
 * the cascade position stays identical:
 *
 *   <link rel="stylesheet" href="style.css">          (before)
 *   <?php echo asset_styles(); ?>                     (after)
 *   <?php echo asset_styles('../'); ?>                (admin/, help/)
 *
 * Tags are joined with the four-space indent used in every page head,
 * so the generated HTML source stays readable. The trailing newline is
 * there because PHP swallows the one newline that follows a closing
 * "?>" tag - without it the next <link> in the page would be glued to
 * the last generated one.
 *
 * @param string $base Prefix to the files/ root - see asset_url().
 * @return string      One or more <link> tags.
 */
function asset_styles($base = '') {
    $tags = [];
    foreach (asset_style_modules() as $module) {
        $href = htmlspecialchars(asset_url($module, $base), ENT_QUOTES, 'UTF-8');
        $tags[] = '<link rel="stylesheet" href="' . $href . '">';
    }

    return implode("\n    ", $tags) . "\n";
}
