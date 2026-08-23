<?php

/**
 * Anime Tracker - SEO Helpers (meta tags, canonical, robots, sitemap)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.30.
 *
 * WHY THIS FILE EXISTS
 *
 * Until 1.1.30 every page head carried a <title> and nothing else: no
 * description, no canonical, no Open Graph tags, and - more importantly -
 * no way to tell a crawler to stay away. There was no robots.txt and no
 * sitemap.xml either.
 *
 * The missing "stay away" half is the reason this landed as a release of
 * its own. In self-host mode (MULTI_USER_MODE = false) the application has
 * NO LOGIN by design: whoever opens the page is user 1. Someone who puts a
 * personal install on a VPS therefore publishes a personal watch list that
 * is not only reachable but indexable. That is a privacy bug, not an SEO
 * gap, and it is fixed here by making indexing MODE-AWARE:
 *
 *   MULTI_USER_MODE = false  ->  noindex on every page, robots.txt says
 *                                "Disallow: /", sitemap.php answers 404.
 *   MULTI_USER_MODE = true   ->  indexable, sitemap served, robots.txt
 *                                only fences off the private endpoints.
 *
 * The switch is deliberately tied to the EXISTING mode constant instead of
 * a new setting: a self-host install should be private without its owner
 * having to discover a checkbox, and an online install is public already.
 *
 * WHY ABSOLUTE URLS NEED A HELPER
 *
 * Canonical links, Open Graph URLs and sitemap entries must be absolute -
 * the rest of the application links relatively ('index.php', '../style.css')
 * and has never needed a base URL. seo_base_url() reconstructs one from
 * SCRIPT_NAME, so an install in a subdirectory (localhost/animetracker/
 * files/) produces correct URLs without any configuration. A hand-written
 * SITE_URL in config.php overrides it, which is the only way to be right
 * behind a proxy/CDN that rewrites the Host header. Old config.php files
 * that do not define it keep working unchanged.
 *
 * Loaded via the functions.php loader.
 */

// =====================================================================
// SECTION: mode + base URL
// =====================================================================

/**
 * May this installation be indexed by search engines at all?
 *
 * True only in online / multi-user mode. The constant is defined by
 * db.php (which defaults it to false for pre-1.1.0 config files); the
 * defined() guard is for the handful of standalone pages that never
 * include db.php - for them "not indexable" is the safe answer.
 *
 * @return bool
 */
function seo_indexing_allowed() {
    return defined('MULTI_USER_MODE') && MULTI_USER_MODE === true;
}

/**
 * Absolute base URL of this installation, without a trailing slash.
 *
 * Resolution order:
 *   1. SITE_URL from config.php, when it is defined and looks like a
 *      http(s) URL. This is the escape hatch for proxy/CDN setups where
 *      the Host header does not name the public site.
 *   2. Reconstructed from the request: scheme + host + the directory the
 *      RUNNING SCRIPT lives in. SCRIPT_NAME is used rather than
 *      REQUEST_URI on purpose - it survives the /robots.txt -> robots.php
 *      and /sitemap.xml -> sitemap.php rewrites and never carries a query
 *      string.
 *
 * $base mirrors the argument of asset_styles(): '' for pages sitting in
 * files/, '../' for pages in files/help/ and files/admin/. Every '../'
 * pops one directory off the reconstructed path, so a help sub-page still
 * reports the application root. It is ignored when SITE_URL is set, since
 * that constant names the root directly.
 *
 * The Host header is client-controlled, so it is shape-checked before use
 * (it ends up inside <link rel="canonical">). Anything unexpected falls
 * back to SERVER_NAME and finally to 'localhost'.
 *
 * @param string $base Prefix from the calling page to the files/ root.
 * @return string      e.g. 'https://example.com' or
 *                     'http://localhost/animetracker/files'
 */
function seo_base_url($base = '') {
    static $cache = [];

    $key = (string)$base;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    if (defined('SITE_URL')) {
        $configured = trim((string)SITE_URL);
        if ($configured !== '' && preg_match('#^https?://[^\s/?\#]+#i', $configured)) {
            return $cache[$key] = rtrim($configured, '/');
        }
    }

    $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    $host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
    if (!preg_match('/^[A-Za-z0-9._-]+(:[0-9]{1,5})?$/', $host)) {
        $host = isset($_SERVER['SERVER_NAME']) ? (string)$_SERVER['SERVER_NAME'] : '';
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $host)) {
            $host = 'localhost';
        }
    }

    // Path of the directory the script lives in. Built by hand instead of
    // with dirname() so the separator stays '/' on every platform.
    $script = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '/index.php';
    $script = str_replace('\\', '/', $script);
    $parts  = array_values(array_filter(explode('/', $script), 'strlen'));
    array_pop($parts);                                  // drop the file name

    $up = substr_count(str_replace('\\', '/', (string)$base), '../');
    for ($i = 0; $i < $up && $parts; $i++) {
        array_pop($parts);
    }

    $path = $parts ? '/' . implode('/', $parts) : '';

    return $cache[$key] = ($https ? 'https' : 'http') . '://' . $host . $path;
}

/**
 * Path component of the base URL ('' or '/animetracker/files').
 *
 * robots.txt speaks in paths, not URLs, so its Disallow lines need this
 * instead of seo_base_url(). Empty string when the application is served
 * from the document root, which is the layout robots.txt actually works
 * in (a subdirectory install cannot own /robots.txt at all).
 *
 * @param string $base See seo_base_url().
 * @return string      Without a trailing slash.
 */
function seo_base_path($base = '') {
    $path = parse_url(seo_base_url($base), PHP_URL_PATH);
    return is_string($path) ? rtrim($path, '/') : '';
}

/**
 * Absolute URL for an application-relative path.
 *
 * @param string $path Relative to the files/ root, e.g. 'anime_details.php?id=5'.
 * @param string $base See seo_base_url().
 * @return string
 */
function seo_url($path, $base = '') {
    return seo_base_url($base) . '/' . ltrim((string)$path, '/');
}

// =====================================================================
// SECTION: <head> output
// =====================================================================

/**
 * Shorten free text into a meta description.
 *
 * Synopsis text may contain [[anime:12|Label]] shortcodes (1.1.26), so it
 * is flattened with synopsis_plain() first - a description must never
 * leak markup. Whitespace (including the newlines a synopsis is full of)
 * collapses to single spaces, then the text is cut on a word boundary.
 *
 * The 160-character target is the usual snippet width; going over is not
 * an error, it is just truncated by the engine, so the cut is soft: text
 * shorter than the limit is returned untouched, and a cut adds an
 * ellipsis so the reader can tell.
 *
 * @param string|null $text
 * @param int         $limit
 * @return string  Plain text, unescaped - the caller escapes it.
 */
function seo_excerpt($text, $limit = 160) {
    $text = (string)$text;
    if ($text === '') {
        return '';
    }

    if (function_exists('synopsis_plain')) {
        $text = synopsis_plain($text);
    }

    $text = strip_tags($text);
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = trim((string)$text);
    if ($text === '') {
        return '';
    }

    if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') <= $limit) {
        return $text;
    }
    if (!function_exists('mb_strlen') && strlen($text) <= $limit) {
        return $text;
    }

    $cut = function_exists('mb_substr')
        ? mb_substr($text, 0, $limit, 'UTF-8')
        : substr($text, 0, $limit);

    // Prefer the last full word, but only if that does not throw away
    // most of the snippet (a single very long "word" would).
    $space = function_exists('mb_strrpos')
        ? mb_strrpos($cut, ' ', 0, 'UTF-8')
        : strrpos($cut, ' ');
    if ($space !== false && $space > $limit * 0.6) {
        $cut = function_exists('mb_substr')
            ? mb_substr($cut, 0, $space, 'UTF-8')
            : substr($cut, 0, $space);
    }

    return rtrim($cut, " \t\n\r\0\x0B.,;:-") . '...';
}

/**
 * Build the SEO block of a page <head>.
 *
 * Emits, in this order: description, canonical, robots (only when the
 * page must not be indexed), Open Graph and Twitter card tags. Call it
 * right below the <title> line:
 *
 *   <?php echo seo_head([
 *       'title'       => $pageTitle,
 *       'description' => $description,
 *       'canonical'   => 'anime_details.php?id=' . $id,
 *       'image'       => $anime['image_path'],
 *   ]); ?>
 *
 * Options (all optional):
 *   title       string  og:title. Falls back to the site name.
 *   description string  Plain text; truncated with seo_excerpt().
 *   canonical   string  Application-relative path. Omit to skip the tag
 *                       (pages whose content depends on session state and
 *                       have no stable address).
 *   image       string  Application-relative image path (a poster).
 *   type        string  og:type, defaults to 'website'.
 *   noindex     bool    Force noindex on a page that IS reachable in
 *                       online mode but has no search value.
 *   base        string  '' or '../' - see seo_base_url().
 *
 * Self-host mode overrides everything: every page gets noindex, nofollow.
 * A page-level noindex uses "noindex, follow" instead, so the crawler
 * still walks through to the detail pages it links to.
 *
 * Tags are joined with the four-space indent used across the page heads
 * and the return value ends with a newline, exactly like asset_styles().
 *
 * @param array $opts
 * @return string HTML.
 */
function seo_head(array $opts = []) {
    $base = isset($opts['base']) ? (string)$opts['base'] : '';

    $siteName = function_exists('t') ? t('seo.site_name') : 'Anime Tracker';
    $title    = isset($opts['title']) && $opts['title'] !== ''
        ? (string)$opts['title']
        : $siteName;

    $description = isset($opts['description']) ? seo_excerpt($opts['description']) : '';
    $type        = isset($opts['type']) && $opts['type'] !== '' ? (string)$opts['type'] : 'website';

    $canonical = '';
    if (!empty($opts['canonical'])) {
        $canonical = seo_url($opts['canonical'], $base);
    }

    // An image_path is normally a local path ('uploads/x.jpg'), but a
    // hand-entered row may hold a full URL - which is already absolute
    // and must not be prefixed with our own base.
    $image = '';
    if (!empty($opts['image'])) {
        $image = preg_match('#^(https?:)?//#i', (string)$opts['image'])
            ? (string)$opts['image']
            : seo_url($opts['image'], $base);
    }

    $tags = [];

    $esc = function ($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    };

    if ($description !== '') {
        $tags[] = '<meta name="description" content="' . $esc($description) . '">';
    }
    if ($canonical !== '') {
        $tags[] = '<link rel="canonical" href="' . $esc($canonical) . '">';
    }

    if (!seo_indexing_allowed()) {
        $tags[] = '<meta name="robots" content="noindex, nofollow">';
    } elseif (!empty($opts['noindex'])) {
        $tags[] = '<meta name="robots" content="noindex, follow">';
    }

    $tags[] = '<meta property="og:type" content="' . $esc($type) . '">';
    $tags[] = '<meta property="og:site_name" content="' . $esc($siteName) . '">';
    $tags[] = '<meta property="og:locale" content="'
        . $esc((function_exists('current_lang') && current_lang() === 'en') ? 'en_US' : 'tr_TR')
        . '">';
    $tags[] = '<meta property="og:title" content="' . $esc($title) . '">';
    if ($description !== '') {
        $tags[] = '<meta property="og:description" content="' . $esc($description) . '">';
    }
    if ($canonical !== '') {
        $tags[] = '<meta property="og:url" content="' . $esc($canonical) . '">';
    }
    if ($image !== '') {
        $tags[] = '<meta property="og:image" content="' . $esc($image) . '">';
    }

    // "summary_large_image" only when there is an image to be large.
    $tags[] = '<meta name="twitter:card" content="'
        . ($image !== '' ? 'summary_large_image' : 'summary') . '">';

    return implode("\n    ", $tags) . "\n";
}

// =====================================================================
// SECTION: sitemap sources
// =====================================================================

/**
 * Anime rows per sitemap chunk.
 *
 * The protocol caps a single sitemap file at 50 000 URLs / 50 MB. One
 * anime contributes up to three URLs (detail + chronology + the series
 * timeline it heads), so 2 000 rows stays far inside the limit while
 * keeping the file small enough to be fetched comfortably.
 */
define('SEO_SITEMAP_CHUNK', 2000);

/**
 * How many catalog rows the sitemap covers.
 *
 * Adult-flagged rows are excluded here and in every query below: the
 * detail page hides them behind an opt-in preference (1.1.2), so an
 * anonymous crawler would only ever receive the neutral notice. Listing
 * a URL that answers with a placeholder is worse than not listing it.
 *
 * @param PDO $pdo
 * @return int
 */
function seo_sitemap_anime_count($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM animes WHERE is_adult = 0");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('[anime_tracker] seo_sitemap_anime_count: ' . $e->getMessage());
        return 0;
    }
}

/**
 * The static, always-present pages of the site.
 *
 * recent / statistics / recommendations are deliberately ABSENT: they
 * carry meta noindex (they change on every visit and answer no search
 * query), and a sitemap entry for a noindex page only spends crawl
 * budget that the detail pages should get instead.
 *
 * @return array List of ['loc', 'changefreq', 'priority'].
 */
function seo_sitemap_static_entries() {
    return [
        ['loc' => 'index.php',              'changefreq' => 'daily',   'priority' => '1.0'],
        ['loc' => 'about.php',              'changefreq' => 'monthly', 'priority' => '0.3'],
        ['loc' => 'help.php',               'changefreq' => 'monthly', 'priority' => '0.4'],
        ['loc' => 'help/help_basics.php',   'changefreq' => 'monthly', 'priority' => '0.3'],
        ['loc' => 'help/help_fields.php',   'changefreq' => 'monthly', 'priority' => '0.3'],
        ['loc' => 'help/help_sync.php',     'changefreq' => 'monthly', 'priority' => '0.3'],
        ['loc' => 'help/help_discovery.php','changefreq' => 'monthly', 'priority' => '0.3'],
        ['loc' => 'help/help_series.php',   'changefreq' => 'monthly', 'priority' => '0.3'],
        ['loc' => 'help/help_timezone.php', 'changefreq' => 'monthly', 'priority' => '0.3'],
    ];
}

/**
 * One chunk of catalog URLs, ordered by id so the paging is stable.
 *
 * Each row can produce three entries:
 *   - anime_details.php?id=N          always
 *   - chronology.php?id=N             only when the anime has markers
 *                                     (without them the page redirects
 *                                     back to the detail page)
 *   - series_timeline.php?id=N        only for the anime that HEADS its
 *                                     series group
 *
 * The series rule needs a word. series_timeline.php draws the same
 * timeline for every member of a series - ?id=12 and ?id=13 of one series
 * render the same content at two addresses. Listing all of them would
 * publish duplicates, so exactly one id per series_name is listed: the
 * smallest. The page itself points its canonical at the same id (see
 * seo_series_head_id), which is what makes the choice consistent rather
 * than arbitrary.
 *
 * @param PDO $pdo
 * @param int $offset
 * @param int $limit
 * @return array List of ['loc', 'lastmod', 'changefreq', 'priority'].
 */
function seo_sitemap_anime_entries($pdo, $offset = 0, $limit = SEO_SITEMAP_CHUNK) {
    $entries = [];

    try {
        $sql = "
            SELECT a.id,
                   a.updated_at,
                   a.series_name,
                   EXISTS(SELECT 1 FROM chronology_markers m
                           WHERE m.anime_id = a.id) AS has_markers,
                   (SELECT MIN(a2.id) FROM animes a2
                     WHERE a2.series_name = a.series_name
                       AND a2.is_adult = 0) AS series_head
              FROM animes a
             WHERE a.is_adult = 0
             ORDER BY a.id
             LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $pdo->query($sql);
    } catch (PDOException $e) {
        error_log('[anime_tracker] seo_sitemap_anime_entries: ' . $e->getMessage());
        return $entries;
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id      = (int)$row['id'];
        $lastmod = !empty($row['updated_at'])
            ? substr((string)$row['updated_at'], 0, 10)
            : '';

        $entries[] = [
            'loc'        => 'anime_details.php?id=' . $id,
            'lastmod'    => $lastmod,
            'changefreq' => 'weekly',
            'priority'   => '0.8',
        ];

        if (!empty($row['has_markers'])) {
            $entries[] = [
                'loc'        => 'chronology.php?id=' . $id,
                'lastmod'    => $lastmod,
                'changefreq' => 'monthly',
                'priority'   => '0.7',
            ];
        }

        if (!empty($row['series_name']) && (int)$row['series_head'] === $id) {
            $entries[] = [
                'loc'        => 'series_timeline.php?id=' . $id,
                'lastmod'    => $lastmod,
                'changefreq' => 'monthly',
                'priority'   => '0.6',
            ];
        }
    }
    $stmt->closeCursor();

    return $entries;
}

/**
 * The id that represents a series in canonical links and the sitemap.
 *
 * Smallest id carrying the same series_name, adult rows excluded. Returns
 * $fallbackId when the anime has no series name, when the lookup fails,
 * or when the row itself is the only member - i.e. the answer is always a
 * usable id.
 *
 * @param PDO         $pdo
 * @param string|null $seriesName
 * @param int         $fallbackId
 * @return int
 */
function seo_series_head_id($pdo, $seriesName, $fallbackId) {
    $seriesName = trim((string)$seriesName);
    if ($seriesName === '') {
        return (int)$fallbackId;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT MIN(id) FROM animes
             WHERE series_name = ? AND is_adult = 0
        ");
        $stmt->execute([$seriesName]);
        $head = $stmt->fetchColumn();
        $stmt->closeCursor();
    } catch (PDOException $e) {
        error_log('[anime_tracker] seo_series_head_id: ' . $e->getMessage());
        return (int)$fallbackId;
    }

    return $head !== false && $head !== null ? (int)$head : (int)$fallbackId;
}
