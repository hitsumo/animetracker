<?php

/**
 * Anime Tracker - CLI Aired-Episode Sync (1.0.19)
 * https://www.sicakcikolata.com
 *
 * Command-line entry point for the once-a-day aired-episode sync. It calls
 * the SAME syncAllOngoingAiredEpisodes() the web page uses (single source);
 * this script only adds a CLI-callable wrapper so the sync can run from
 * cron (Linux) or Task Scheduler (Windows) without anyone opening a page.
 *
 * Why a separate file (the two CLI rules):
 *   - No HTTP context in CLI: there is no session and no logged-in user, so
 *     this script does NOT call can($pdo, 'moderate'). The web trigger keeps
 *     its moderator gate; cron is already a trusted/privileged environment.
 *     Aired sync is catalog-level (per anime, not per user), so it needs no
 *     current user.
 *   - Path-independent loading: uses __DIR__ so config.php / db.php /
 *     functions.php resolve no matter which directory cron invokes the
 *     script from. config.php is where ANIMESCHEDULE_API_KEY is defined.
 *
 * The web page (list_settings.php) still runs the same sync once per day on
 * open. The shared settings.last_aired_sync 'Y-m-d' (UTC) gate prevents a
 * double run if both the page and cron fire on the same day.
 *
 * Usage (run from the application directory or with a full path):
 *   php sync_aired.php
 *
 * Cron (Linux) - every day at 04:00, log appended:
 *   0 4 * * * php /path/to/anime_tracker/sync_aired.php >> /var/log/anime_aired.log 2>&1
 *
 * Windows Task Scheduler - run program:
 *   C:\xampp\php\php.exe
 *   with argument:
 *   C:\xampp\htdocs\anime_tracker\sync_aired.php
 */

// Refuse to run over HTTP. This script is for the command line only; without
// this guard, a browser request to the file could trigger the sync.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Catalog-level sync. No user / no moderator check (see the file docblock).
// syncAllOngoingAiredEpisodes() updates settings.last_aired_sync on a
// successful run and logs failures via error_log (no emoji), exactly like
// the web path.
$stats = syncAllOngoingAiredEpisodes($pdo, 3);

// Short, plain ASCII summary so cron logs stay useful.
$summary = sprintf(
    "aired sync done: updated=%d unchanged=%d finished=%d not_in_table=%d no_slug=%d errors=%d%s\n",
    (int)($stats['updated'] ?? 0),
    (int)($stats['unchanged'] ?? 0),
    (int)($stats['finished'] ?? 0),
    (int)($stats['not_in_table'] ?? 0),
    (int)($stats['no_slug'] ?? 0),
    (int)($stats['errors'] ?? 0),
    isset($stats['global_error']) ? ' global_error=' . $stats['global_error'] : ''
);
fwrite(STDOUT, $summary);

// Non-zero exit on a global API failure so cron / monitoring can detect it.
// Per-anime soft results (not_in_table, no_slug) are normal and do not fail.
exit(isset($stats['global_error']) ? 1 : 0);
