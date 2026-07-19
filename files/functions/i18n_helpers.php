<?php

/**
 * Anime Tracker - Settings Store + UI Translations (i18n)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Split out of functions.php in 0.6.7 (code reorganization,
 * no behavior change). Loaded via the functions.php loader.
 */

// =====================================================================
// SECTION: Generic application settings (key-value store)
// ---------------------------------------------------------------------
// The settings table is a generic name -> value store (see schema.sql).
// Until 0.6.2 each caller wrote its own SELECT / INSERT against it,
// which meant the INSERT ... ON DUPLICATE KEY UPDATE pattern was
// repeated in several places. These two helpers collect that into one
// spot so future callers (including the i18n layer below) do not have
// to know the SQL shape.
//
// Existing direct-SQL callers (check_update.php, list_settings.php,
// update.php, the last_aired_sync writer in this file) are intentionally
// left alone in this release - changing them is a separate cleanup pass
// and the helpers + the legacy code coexist without conflict because
// they both speak the same INSERT ... ON DUPLICATE pattern.
// =====================================================================

/**
 * Read a value from the settings key-value table.
 *
 * Returns the stored string for $name, or $default if the row does not
 * exist. Treats DB errors as "row absent" - a transient read failure
 * should never crash a page, and the caller is expected to supply a
 * safe default (e.g. 'tr' for display_language).
 *
 * @param PDO         $pdo
 * @param string      $name     Settings key (e.g. 'version', 'display_language').
 * @param string|null $default  Returned when the row is absent.
 * @return string|null
 */
function get_setting($pdo, $name, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return $default;
        }
        return $row['value'];
    } catch (PDOException $e) {
        error_log('[anime_tracker] get_setting(' . $name . '): ' . $e->getMessage());
        return $default;
    }
}

/**
 * Write a value to the settings key-value table.
 *
 * Uses INSERT ... ON DUPLICATE KEY UPDATE so the row is created on
 * first use and overwritten on subsequent writes. Returns true on
 * success, false on a DB error (logged via error_log).
 *
 * Callers that need to react to a write failure should check the
 * return value; callers that just want "best effort" persistence can
 * ignore it.
 *
 * @param PDO    $pdo
 * @param string $name
 * @param string $value
 * @return bool
 */
function set_setting($pdo, $name, $value) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (name, value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value)
        ");
        $stmt->execute([$name, $value]);
        return true;
    } catch (PDOException $e) {
        error_log('[anime_tracker] set_setting(' . $name . '): ' . $e->getMessage());
        return false;
    }
}

// =====================================================================
// SECTION: UI translations (i18n)
// ---------------------------------------------------------------------
// Three-function family, parallel to the watch_status_* / emotion_*
// helpers: one function loads state, one reports state, one renders.
//
//   lang_init($pdo)     Reads display_language from settings, loads the
//                       matching dictionary into a static cache. Called
//                       once at the top of each page that uses t().
//   current_lang()      Returns 'tr' or 'en'. Falls back to 'tr' if
//                       lang_init() was not called yet.
//   t($key)             Returns the translated string. Falls back to
//                       Turkish if the English entry is missing, then
//                       to $key itself if even Turkish is missing -
//                       a defensive choice so a missing key shows up
//                       as a visible token instead of a blank cell.
//
// Adding a new language: drop a lang/<code>.php file with the same
// keys, append the code to $allowed in lang_init(), done. The CSS
// classes used elsewhere in the project are already ASCII suffixes
// (watched / huzunlendirdi / ...) so they are language-independent.
// =====================================================================

/**
 * Internal cache shared by lang_init / current_lang / t.
 *
 * Pulled out into its own accessor so all three helpers see the same
 * state without each one re-implementing the static. Callers should
 * not invoke this directly.
 *
 * @param array|null $write  When non-null, replaces the cached state.
 * @return array{lang: string, dict: array, fallback: array}
 */
function _lang_cache($write = null) {
    static $cache = [
        'lang'     => 'tr',
        'dict'     => null,
        'fallback' => null,
    ];
    if ($write !== null) {
        $cache = $write;
    }
    return $cache;
}

/**
 * Initialize the i18n layer for the current request.
 *
 * Reads display_language from the settings table, loads the matching
 * dictionary from lang/<code>.php, and also pre-loads lang/tr.php as
 * a fallback so the per-key fallback in t() does not need to touch
 * the filesystem on every miss.
 *
 * Should be called once at the top of each page that uses t(), right
 * after the db.php / functions.php requires. Subsequent calls in the
 * same request are no-ops.
 *
 * @param PDO $pdo
 * @return void
 */
function lang_init($pdo) {
    $cache = _lang_cache();
    if ($cache['dict'] !== null) {
        return; // already initialised this request
    }

    $allowed = ['tr', 'en'];
    // display_language is a per-user preference (user_pref, 1.0.1), read for
    // the current user (id 1 when MULTI_USER_MODE is off).
    //
    // Guests (1.1.16): in multi-user mode an anonymous visitor has no user id
    // and therefore no user_pref row, so their choice cannot be stored there.
    // For them the preference lives in the session instead (written by
    // set_language.php the same way). current_user_id() is null ONLY for an
    // anonymous multi-user visitor - self-host always returns 1 - so this
    // branch never affects self-host. The '??' guards against a missing
    // $_SESSION on CLI (cron), where no session is started.
    $uid = current_user_id();
    if ($uid === null) {
        $lang = $_SESSION['guest_display_language'] ?? 'tr';
    } else {
        $lang = get_user_pref($pdo, $uid, 'display_language', 'tr');
    }
    if (!in_array($lang, $allowed, true)) {
        $lang = 'tr';
    }

    $dict     = _lang_load($lang);
    $fallback = ($lang === 'tr') ? $dict : _lang_load('tr');

    _lang_cache([
        'lang'     => $lang,
        'dict'     => $dict,
        'fallback' => $fallback,
    ]);
}

/**
 * Load a translation dictionary from disk.
 *
 * Returns an empty array if the file is missing or does not return
 * an array - the t() helper will then either fall back to Turkish or
 * to the raw key, so a missing dictionary degrades gracefully rather
 * than crashing the page.
 *
 * @param string $lang
 * @return array
 */
function _lang_load($lang) {
    $path = dirname(__DIR__) . '/lang/' . $lang . '.php';
    if (!is_file($path)) {
        error_log('[anime_tracker] lang file missing: ' . $path);
        return [];
    }
    $data = include $path;
    return is_array($data) ? $data : [];
}

/**
 * Return the active language code for the current request.
 *
 * Returns 'tr' if lang_init() has not been called yet - this keeps
 * pages that have not been translated yet rendering in their
 * original Turkish wording instead of throwing.
 *
 * @return string  'tr' or 'en'.
 */
function current_lang() {
    $cache = _lang_cache();
    return $cache['lang'];
}

/**
 * Translate a UI string key into the active language.
 *
 * Lookup order:
 *   1. Active language dictionary (loaded by lang_init).
 *   2. Turkish dictionary (fallback - English may have gaps while
 *      translation is in progress).
 *   3. The key itself, returned unchanged - a visible token tells
 *      the developer which entry is missing without leaving the
 *      user with a blank screen.
 *
 * @param string $key  Dot-namespaced key, e.g. 'nav.statistics'.
 * @return string
 */
function t($key) {
    $cache = _lang_cache();

    if ($cache['dict'] !== null && isset($cache['dict'][$key])) {
        return $cache['dict'][$key];
    }
    if ($cache['fallback'] !== null && isset($cache['fallback'][$key])) {
        return $cache['fallback'][$key];
    }
    return $key;
}

/**
 * Initialise the language dictionary for an ADMIN page.
 *
 * Loads the user dictionary first (lang/tr.php or lang/en.php) so
 * shared keys like nav.about, lang.tr_label, etc. work as usual,
 * then merges the admin-only dictionary (lang/admin_tr.php or
 * lang/admin_en.php) on top.
 *
 * This split keeps the user-side dictionary lean - admin keys
 * (admin.*, admin_pending.*, admin_sync.*) are never loaded for
 * regular users.
 *
 * Should be called once at the top of each admin page (admin.php,
 * admin_pending.php, admin_sync.php), right after the db.php /
 * functions.php requires.
 *
 * @param PDO $pdo
 * @return void
 */
function lang_init_admin($pdo) {
    // Load user dictionary first (idempotent - sets dict + fallback)
    lang_init($pdo);

    // Merge admin dictionary on top
    $cache = _lang_cache();
    $lang = $cache['lang'];

    $adminDict     = _lang_load_admin($lang);
    $adminFallback = ($lang === 'tr') ? $adminDict : _lang_load_admin('tr');

    $cache['dict']     = array_merge($cache['dict'],     $adminDict);
    $cache['fallback'] = array_merge($cache['fallback'], $adminFallback);
    _lang_cache($cache);
}

/**
 * Load an admin translation dictionary from disk.
 *
 * Mirror of _lang_load() but for the admin-side dictionary file
 * (lang/admin_<code>.php). Returns an empty array if the file is
 * missing - the t() helper will then fall back to Turkish or to
 * the raw key, so a missing admin file degrades gracefully.
 *
 * @param string $lang
 * @return array
 */
function _lang_load_admin($lang) {
    $path = dirname(__DIR__) . '/lang/admin_' . $lang . '.php';
    if (!is_file($path)) {
        error_log('[anime_tracker] admin lang file missing: ' . $path);
        return [];
    }
    $data = include $path;
    return is_array($data) ? $data : [];
}

/**
 * Render an inline TR / EN language switcher for anonymous visitors (1.1.16).
 *
 * Logged-in users pick their interface language from list_settings.php (the
 * "Arayuz Dili" section, 1.1.4) and self-host has a single always-present
 * owner - neither needs an inline switcher, so this returns an empty string
 * for them and the surrounding markup is unchanged. It renders the control
 * ONLY for a guest in multi-user mode, whose choice cannot be stored in
 * user_pref (no user id) and instead lives in the session; the form posts to
 * the unchanged set_language.php, which routes the guest write to the session.
 *
 * The markup mirrors the settings-page switcher: a <select> with onchange
 * auto-submit, plus a <noscript> save button for JS-off browsers. It reuses
 * the same translation keys so there is no new copy to maintain. Callers echo
 * the return value where they want the control to appear (the auth pages:
 * login / register / request_invite).
 *
 * @return string  HTML for the switcher, or '' when it should not show.
 */
function guest_lang_switcher()
{
    // Guest = multi-user mode AND not logged in. is_logged_in() already
    // encodes the self-host "always logged in" rule, so this single check
    // covers both non-guest cases.
    if (!MULTI_USER_MODE || is_logged_in()) {
        return '';
    }

    $cur     = current_lang();
    $csrf    = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    $aria    = htmlspecialchars(t('list_settings.section.language'), ENT_QUOTES, 'UTF-8');
    $trLabel = htmlspecialchars(t('list_settings.language.option_tr'), ENT_QUOTES, 'UTF-8');
    $enLabel = htmlspecialchars(t('list_settings.language.option_en'), ENT_QUOTES, 'UTF-8');
    $save    = htmlspecialchars(t('list_settings.language.save'), ENT_QUOTES, 'UTF-8');
    $trSel   = $cur === 'tr' ? ' selected' : '';
    $enSel   = $cur === 'en' ? ' selected' : '';

    return '<form method="post" action="set_language.php" class="guest-lang-switcher">'
        . '<input type="hidden" name="csrf_token" value="' . $csrf . '">'
        . '<select name="lang" onchange="this.form.submit()" aria-label="' . $aria . '">'
        . '<option value="tr"' . $trSel . '>' . $trLabel . '</option>'
        . '<option value="en"' . $enSel . '>' . $enLabel . '</option>'
        . '</select>'
        . '<noscript><button type="submit">' . $save . '</button></noscript>'
        . '</form>';
}
