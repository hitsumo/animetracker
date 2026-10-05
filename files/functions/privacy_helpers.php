<?php
/**
 * Anime Tracker - Privacy helpers
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.2.4 together with privacy.php.
 *
 * WHAT LIVES HERE
 *
 * 1. The IP retention rule. invite_requests.ip and suggestions.ip exist for
 *    one job only: the "at most N submissions per IP in the trailing hour"
 *    spam limit (auth_helpers.php invite_request_submit, suggest.php). An
 *    address older than an hour no longer does anything, so it is not kept
 *    forever: after PRIVACY_IP_RETENTION_DAYS it is set to NULL. The row
 *    itself (email, reason, note) stays - only the address goes.
 *
 *    There is no cron in this application, so the purge runs where the
 *    tables are touched anyway: right before a new invite request or
 *    suggestion is stored, and when an admin opens the page that lists
 *    them. A site that receives nothing and is never administered keeps
 *    its old addresses until the next visit to one of those places; the
 *    next one clears them all in one statement.
 *
 *    `updated_at = updated_at` keeps the ON UPDATE timestamp untouched - the
 *    purge is housekeeping, not an edit, and the admin lists sort by it.
 *
 * 2. The site's contact address: privacy.php prints it for data / account
 *    requests and help.php shows it at the top (1.2.4; it used to be a
 *    fixed address in the language file, which every installation then
 *    showed). settings.privacy_contact_email, set on
 *    admin/admin_capabilities.php ("Contact"). When empty the privacy page
 *    says "contact the operator of this site" without an address and help
 *    shows no contact line. There is deliberately NO fallback to the
 *    invite notification address: that one receives invite requests, it is
 *    not the operator's public contact.
 */

/** Days an invite-request / suggestion IP address is kept. */
const PRIVACY_IP_RETENTION_DAYS = 30;

/**
 * Clear IP addresses older than PRIVACY_IP_RETENTION_DAYS.
 *
 * Safe to call on every submission: the WHERE clause matches nothing on a
 * site that is already clean, and idx_ip_created narrows the scan.
 *
 * @param PDO $pdo
 * @return void
 */
function privacy_purge_old_ips($pdo) {
    $days = (int)PRIVACY_IP_RETENTION_DAYS;
    foreach (['invite_requests', 'suggestions'] as $table) {
        try {
            $pdo->exec(
                "UPDATE `$table`
                    SET ip = NULL, updated_at = updated_at
                  WHERE ip IS NOT NULL
                    AND created_at < (NOW() - INTERVAL $days DAY)"
            );
        } catch (PDOException $e) {
            error_log('[anime_tracker] privacy_purge_old_ips(' . $table . '): ' . $e->getMessage());
        }
    }
}

/**
 * The address privacy.php shows for data and account requests.
 *
 * @param PDO $pdo
 * @return string A valid email address, or '' when none is configured.
 */
function privacy_contact_email($pdo) {
    $value = trim((string)get_setting($pdo, 'privacy_contact_email', ''));
    if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return $value;
    }
    return '';
}
