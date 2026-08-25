<?php

/**
 * Anime Tracker - IndexNow (change notification for Bing + Yandex)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.32.
 *
 * WHY THIS FILE EXISTS
 *
 * 1.1.30 gave the site a sitemap. A sitemap answers "what exists here" and
 * is re-read on the crawler's own schedule - days, sometimes weeks.
 * IndexNow answers the other question, "what changed just now", by pushing
 * a single HTTP request the moment a page appears, changes or disappears.
 * It is a Microsoft-originated protocol that Bing and Yandex share (submit
 * to one endpoint, every participant receives it); Google does not take
 * part, so this is an addition to the sitemap, never a replacement for it.
 *
 * WHY A QUEUE INSTEAD OF PINGING FROM THE SAVE HANDLER
 *
 * The obvious implementation - call the endpoint right there in
 * add_anime.php - is wrong in three separate ways:
 *
 *   1. It puts a third-party network round trip inside the curator's save.
 *      A slow endpoint would be felt as a slow form.
 *   2. A failed request is simply lost. There is no retry, and the URL is
 *      never announced.
 *   3. A catalog import touching 500 rows would fire 500 requests, and
 *      editing one anime five times in a minute would fire five - which is
 *      exactly the behaviour the protocol asks submitters NOT to have.
 *
 * So the write path only ever does an INSERT into indexnow_queue (dedup is
 * the UNIQUE key on loc: one page changed ten times is still one row), and
 * indexnow_ping.php - a CLI script for cron, exactly like sync_aired.php -
 * drains that queue in batches. Nobody has to decide when to ping.
 *
 * WHICH URLS ARE ELIGIBLE
 *
 * Whatever the sitemap would list, and nothing else. That rule lives in
 * seo_anime_locs() in seo_helpers.php (next to the sitemap query it
 * mirrors) so there is ONE definition of "a public catalog URL" rather
 * than two that can drift: adult rows excluded, chronology.php only when
 * the anime has markers, series_timeline.php only for the id that heads
 * its series.
 *
 * The list page (index.php) is deliberately NOT queued when an anime is
 * added. It changes on nearly every write, crawlers visit it often anyway,
 * and a new anime is discovered through its own pinged detail URL - so
 * announcing it every time would spend the site's submission budget on the
 * one page that needs it least.
 *
 * WHAT SWITCHES IT ON
 *
 * Two conditions, both required:
 *   - seo_indexing_allowed() - online/multi-user mode. A self-host install
 *     publishes "Disallow: /" and no sitemap (1.1.30); announcing its URLs
 *     to a search engine would contradict that, so nothing is even queued.
 *   - INDEXNOW_KEY in config.php - the shared secret-that-is-not-secret the
 *     protocol uses to prove the submitter owns the host. A missing key
 *     leaves the whole feature inert, in the same "optional constant"
 *     shape as ANIMESCHEDULE_API_KEY.
 *
 * Loaded via the functions.php loader.
 */

// =====================================================================
// SECTION: configuration constants
// =====================================================================

/**
 * The shared endpoint. Submitting here reaches every participating engine
 * (Bing, Yandex, Seznam, Naver); there is no reason to also POST to the
 * engine-specific hosts, and doing so is what the protocol itself calls
 * out as duplicate submission.
 */
define('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/indexnow');

/**
 * URLs per request. The protocol allows 10 000; 1 000 keeps a single POST
 * body around 60-70 KB, which is comfortable for every proxy in between
 * and still drains a large import in a handful of requests.
 */
define('INDEXNOW_BATCH', 1000);

/**
 * Requests per CLI run. A full catalog import can queue thousands of rows;
 * without a loop an hourly cron would need hours to catch up. With the
 * ceiling, one run announces at most 10 000 URLs and then stops - a bound
 * that exists so a runaway queue cannot turn into an endless job.
 */
define('INDEXNOW_MAX_BATCHES', 10);

/**
 * How many failed sends a row survives before it stops being retried.
 *
 * Failures are per BATCH, not per URL (the endpoint answers once for the
 * whole list), so a permanently rejected batch - a wrong key, a host that
 * does not match - would otherwise be retried forever and block every
 * newer URL behind it. After this many attempts the rows are left in
 * place but skipped, which keeps them inspectable;
 * "indexnow_ping.php --retry" clears the counter once the cause is fixed.
 */
define('INDEXNOW_MAX_ATTEMPTS', 5);

// =====================================================================
// SECTION: mode + key
// =====================================================================

/**
 * The configured IndexNow key, or '' when there is none to use.
 *
 * The protocol requires 8-128 characters from [A-Za-z0-9-]. The shape is
 * checked here rather than trusted, because the value ends up in a URL
 * (the key file address) and in a JSON body; a malformed constant should
 * disable the feature, not produce a request that is rejected 1 000 URLs
 * at a time.
 *
 * @return string
 */
function indexnow_key() {
    if (!defined('INDEXNOW_KEY')) {
        return '';
    }
    $key = trim((string)INDEXNOW_KEY);
    return preg_match('/^[A-Za-z0-9-]{8,128}$/', $key) ? $key : '';
}

/**
 * May this installation announce anything at all?
 *
 * @return bool
 */
function indexnow_enabled() {
    return seo_indexing_allowed() && indexnow_key() !== '';
}

/**
 * Absolute base URL to build submitted addresses from, or null.
 *
 * Over HTTP this is just seo_base_url(). On the COMMAND LINE it cannot be:
 * there is no Host header and no meaningful SCRIPT_NAME, so seo_base_url()
 * would happily reconstruct "http://localhost" and every submitted URL
 * would be rejected for not belonging to the host. SITE_URL is therefore
 * REQUIRED for the CLI drain, and its absence is reported as a
 * configuration error instead of being papered over.
 *
 * @return string|null Without a trailing slash.
 */
function indexnow_base_url() {
    if (PHP_SAPI !== 'cli') {
        return seo_base_url();
    }
    if (!defined('SITE_URL')) {
        return null;
    }
    $configured = trim((string)SITE_URL);
    if ($configured === '' || !preg_match('#^https?://[^\s/?\#]+#i', $configured)) {
        return null;
    }
    return rtrim($configured, '/');
}

/**
 * Public address of the key file this installation claims ownership with.
 *
 * It sits next to index.php, so a subdirectory install produces a
 * subdirectory keyLocation - which the protocol accepts, with the effect
 * that only URLs under that directory may be submitted. Every URL we
 * submit is built from the same base, so the two always agree.
 *
 * @param string $base From indexnow_base_url().
 * @return string
 */
function indexnow_key_location($base) {
    return rtrim((string)$base, '/') . '/' . indexnow_key() . '.txt';
}

// =====================================================================
// SECTION: the queue - write side (called from page handlers)
// =====================================================================

/**
 * Queue application-relative paths for announcement.
 *
 * Every caller of this runs inside a user-facing save, so it is written to
 * be UNABLE to break one: disabled installs return immediately, and a DB
 * error is logged and swallowed. Announcing a change is housekeeping; the
 * change itself is already committed.
 *
 * Re-queueing an address that is already waiting is a no-op (UNIQUE key on
 * loc), and deliberately does NOT refresh queued_at: the row is going out
 * on the next run either way, and keeping the original timestamp is what
 * makes "how long has this been stuck" readable.
 *
 * @param PDO   $pdo
 * @param array $locs e.g. ['anime_details.php?id=5']
 * @return int  Rows newly queued.
 */
function indexnow_queue_locs($pdo, array $locs) {
    if (!indexnow_enabled() || empty($locs)) {
        return 0;
    }

    $queued = 0;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO indexnow_queue (loc) VALUES (?)
            ON DUPLICATE KEY UPDATE id = id
        ");
        foreach ($locs as $loc) {
            $loc = ltrim(trim((string)$loc), '/');
            // 191 is the column width (see the migration for why); a longer
            // path would be truncated by MySQL and announce a wrong URL.
            if ($loc === '' || strlen($loc) > 191) {
                continue;
            }
            $stmt->execute([$loc]);
            $queued += $stmt->rowCount() > 0 ? 1 : 0;
        }
    } catch (PDOException $e) {
        error_log('[anime_tracker] indexnow_queue_locs: ' . $e->getMessage());
    }

    return $queued;
}

/**
 * Queue every public URL of one anime.
 *
 * The URL set comes from seo_anime_locs(), i.e. the sitemap's own rule.
 *
 * $forceChronology exists for a single case: deleting the LAST chronology
 * marker of an anime. After that delete the anime has no markers, so the
 * rule no longer produces chronology.php?id=N - yet that address was
 * indexable a second ago and now only redirects. Forcing it into the queue
 * is what gets it re-crawled and dropped.
 *
 * Call this BEFORE deleting an anime row, not after: the rule needs the
 * row (series name, markers, adult flag) to know which addresses existed.
 *
 * @param PDO  $pdo
 * @param int  $animeId
 * @param bool $forceChronology
 * @return int
 */
function indexnow_queue_anime($pdo, $animeId, $forceChronology = false) {
    if (!indexnow_enabled()) {
        return 0;
    }
    return indexnow_queue_locs($pdo, seo_anime_locs($pdo, $animeId, $forceChronology));
}

// =====================================================================
// SECTION: the queue - drain side (called from indexnow_ping.php)
// =====================================================================

/**
 * How many rows are waiting and still retryable.
 *
 * @param PDO $pdo
 * @return int
 */
function indexnow_queue_size($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM indexnow_queue WHERE attempts < ?");
        $stmt->execute([INDEXNOW_MAX_ATTEMPTS]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('[anime_tracker] indexnow_queue_size: ' . $e->getMessage());
        return 0;
    }
}

/**
 * How many rows have exhausted their attempts and are being skipped.
 *
 * @param PDO $pdo
 * @return int
 */
function indexnow_stuck_count($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM indexnow_queue WHERE attempts >= ?");
        $stmt->execute([INDEXNOW_MAX_ATTEMPTS]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('[anime_tracker] indexnow_stuck_count: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Oldest waiting rows, up to $limit.
 *
 * Ordered by id so the queue is FIFO and the paging is stable. The limit
 * is cast into the SQL rather than bound, because a LIMIT placeholder is
 * not accepted with emulated prepares turned off (db.php) - the same
 * reason seo_sitemap_anime_entries() does it that way.
 *
 * @param PDO $pdo
 * @param int $limit
 * @return array List of ['id', 'loc'].
 */
function indexnow_take_batch($pdo, $limit = INDEXNOW_BATCH) {
    $limit = max(1, min(10000, (int)$limit));
    try {
        $stmt = $pdo->prepare("
            SELECT id, loc FROM indexnow_queue
             WHERE attempts < ?
             ORDER BY id
             LIMIT " . $limit);
        $stmt->execute([INDEXNOW_MAX_ATTEMPTS]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $rows ? $rows : [];
    } catch (PDOException $e) {
        error_log('[anime_tracker] indexnow_take_batch: ' . $e->getMessage());
        return [];
    }
}

/**
 * Remove rows that were accepted.
 *
 * @param PDO   $pdo
 * @param array $ids
 * @return int Rows removed.
 */
function indexnow_drop_rows($pdo, array $ids) {
    if (empty($ids)) {
        return 0;
    }
    try {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM indexnow_queue WHERE id IN ($ph)");
        $stmt->execute(array_map('intval', $ids));
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log('[anime_tracker] indexnow_drop_rows: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Count a failed send against the rows it carried.
 *
 * @param PDO   $pdo
 * @param array $ids
 * @return void
 */
function indexnow_bump_attempts($pdo, array $ids) {
    if (empty($ids)) {
        return;
    }
    try {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE indexnow_queue SET attempts = attempts + 1 WHERE id IN ($ph)");
        $stmt->execute(array_map('intval', $ids));
    } catch (PDOException $e) {
        error_log('[anime_tracker] indexnow_bump_attempts: ' . $e->getMessage());
    }
}

/**
 * Clear every attempt counter, so exhausted rows are retried once more.
 *
 * @param PDO $pdo
 * @return int Rows reset.
 */
function indexnow_reset_attempts($pdo) {
    try {
        $stmt = $pdo->query("UPDATE indexnow_queue SET attempts = 0 WHERE attempts > 0");
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log('[anime_tracker] indexnow_reset_attempts: ' . $e->getMessage());
        return 0;
    }
}

// =====================================================================
// SECTION: the request
// =====================================================================

/**
 * POST one batch to the shared endpoint.
 *
 * The bulk (JSON) form is used even for a single URL, so there is one code
 * path instead of two and the key never has to travel in a query string.
 *
 * Return shape: ['ok' => bool, 'status' => int, 'error' => string,
 * 'permanent' => bool]. "permanent" separates the two failure families,
 * because they deserve opposite reactions:
 *
 *   200 OK / 202 Accepted   accepted (202 = key not verified yet, which is
 *                           normal on the very first submission)
 *   400 / 403 / 422         the request itself is wrong - bad JSON, a key
 *                           the key file does not confirm, URLs that do
 *                           not belong to the host. Retrying it unchanged
 *                           cannot help; a human has to fix config.php.
 *   429                     too many submissions - back off, try later.
 *   5xx / network           the other end is unwell - try later.
 *
 * @param array  $urls        Absolute URLs.
 * @param string $host        Bare host name.
 * @param string $key
 * @param string $keyLocation Absolute URL of the key file.
 * @return array
 */
function indexnow_submit(array $urls, $host, $key, $keyLocation) {
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'status' => 0, 'permanent' => true,
                'error' => 'PHP curl extension is not available'];
    }

    $body = json_encode([
        'host'        => $host,
        'key'         => $key,
        'keyLocation' => $keyLocation,
        'urlList'     => array_values($urls),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($body === false) {
        return ['ok' => false, 'status' => 0, 'permanent' => true,
                'error' => 'JSON encode failed: ' . json_last_error_msg()];
    }

    $ch = curl_init(INDEXNOW_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $status   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'status' => 0, 'permanent' => false,
                'error' => 'connection failed: ' . $curlErr];
    }

    if ($status === 200 || $status === 202) {
        return ['ok' => true, 'status' => $status, 'permanent' => false, 'error' => ''];
    }

    return [
        'ok'        => false,
        'status'    => $status,
        'permanent' => in_array($status, [400, 403, 422], true),
        // The body carries the engine's own explanation; it is short, but
        // truncate anyway so a stray HTML error page cannot flood the log.
        'error'     => trim(substr((string)$response, 0, 300)),
    ];
}

/**
 * Drain the queue: take rows, submit them, delete what was accepted.
 *
 * Options: ['dry_run' => bool, 'max_batches' => int, 'batch' => int].
 *
 * A dry run stops after ONE batch and returns the URLs it would have sent
 * in ['preview']. It cannot loop, because nothing is removed from the
 * queue and the second pass would pick up the very same rows.
 *
 * The first failure ends the run. The queue is FIFO and the failure is
 * about the request as a whole, so pushing the next batch at a service
 * that just answered 429 - or at a key it just rejected - only makes it
 * worse. The next cron run picks up where this one stopped.
 *
 * @param PDO   $pdo
 * @param array $opts
 * @return array ['ok','sent','batches','left','stuck','preview','errors']
 */
function indexnow_flush($pdo, array $opts = []) {
    $dryRun     = !empty($opts['dry_run']);
    $maxBatches = isset($opts['max_batches']) ? max(1, (int)$opts['max_batches']) : INDEXNOW_MAX_BATCHES;
    $batchSize  = isset($opts['batch']) ? (int)$opts['batch'] : INDEXNOW_BATCH;

    $result = ['ok' => true, 'sent' => 0, 'batches' => 0, 'left' => 0,
               'stuck' => 0, 'preview' => [], 'errors' => []];

    if (!seo_indexing_allowed()) {
        $result['ok'] = false;
        $result['errors'][] = 'self-host mode (MULTI_USER_MODE = false) - nothing is announced';
        return $result;
    }
    $key = indexnow_key();
    if ($key === '') {
        $result['ok'] = false;
        $result['errors'][] = 'INDEXNOW_KEY is missing or malformed in config.php';
        return $result;
    }
    $base = indexnow_base_url();
    if ($base === null) {
        $result['ok'] = false;
        $result['errors'][] = 'SITE_URL must be defined in config.php, as a full http(s) address, for command-line runs';
        return $result;
    }
    $host = parse_url($base, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        $result['ok'] = false;
        $result['errors'][] = 'could not read a host name out of ' . $base;
        return $result;
    }

    $keyLocation = indexnow_key_location($base);

    for ($i = 0; $i < $maxBatches; $i++) {
        $rows = indexnow_take_batch($pdo, $batchSize);
        if (empty($rows)) {
            break;
        }

        $ids  = [];
        $urls = [];
        foreach ($rows as $row) {
            $ids[]  = (int)$row['id'];
            $urls[] = $base . '/' . ltrim((string)$row['loc'], '/');
        }

        if ($dryRun) {
            $result['preview'] = $urls;
            $result['batches']++;
            break;
        }

        $res = indexnow_submit($urls, $host, $key, $keyLocation);
        $result['batches']++;

        if ($res['ok']) {
            indexnow_drop_rows($pdo, $ids);
            $result['sent'] += count($ids);
            continue;
        }

        indexnow_bump_attempts($pdo, $ids);
        $result['ok'] = false;
        $result['errors'][] = 'HTTP ' . $res['status']
            . ($res['permanent'] ? ' (permanent)' : ' (temporary)')
            . ($res['error'] !== '' ? ': ' . $res['error'] : '');
        error_log('[anime_tracker] indexnow submit failed: HTTP ' . $res['status']
            . ' - ' . $res['error']);
        break;
    }

    if ($result['sent'] > 0) {
        set_setting($pdo, 'last_indexnow_ping', gmdate('Y-m-d H:i:s'));
        set_setting($pdo, 'last_indexnow_count', (string)$result['sent']);
    }

    $result['left']  = indexnow_queue_size($pdo);
    $result['stuck'] = indexnow_stuck_count($pdo);

    return $result;
}
