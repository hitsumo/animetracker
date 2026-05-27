<?php

/**
 * Anime Tracker - Admin UI Translations (English)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * ---
 *
 * Admin-side English translations. Mirror of lang/admin_tr.php
 * with the same keys. Loaded by lang_init_admin($pdo) on admin
 * pages only (admin.php, admin_pending.php, admin_sync.php).
 *
 * Missing keys fall back to admin_tr.php and ultimately to the
 * key itself, so an in-progress translation never leaves the
 * admin with a blank string.
 */

return [

    // -----------------------------------------------------------------
    // admin.php - admin dashboard (localhost-only)
    // -----------------------------------------------------------------
    'admin.page_title'                       => 'Admin Dashboard - Anime Tracker',
    'admin.heading'                          => 'Admin Dashboard',
    'admin.subtitle'                         => 'localhost only - regular users cannot access this',
    'admin.intro'                            => 'This page hosts admin tools for the catalog owner. The file is in <code>.gitignore</code>, so it is never bundled with the repo or the <code>.exe</code> installer.',
    'admin.tool.sync.h3'                     => 'Push Catalog to Server',
    'admin.tool.sync.desc'                   => 'Pushes the local catalog to the server. Newly added anime and chronology markers are delivered via HMAC-signed POST. Personal data (watch progress, notes) is not sent.',
    'admin.tool.sync.link.disabled'          => 'Setup incomplete',
    'admin.tool.sync.link.open'              => 'Open sync page',
    'admin.tool.sync.missing_files'          => 'Missing files:',
    'admin.tool.sync.pending_warning'        => 'There are %d pending anime. They are not included in the admin_sync payload because <code>source=\'local\'</code> (silently skipped). First promote the selected ones from the <strong>Pending Anime</strong> card, then push from here.',
    'admin.tool.sync.status_ok'              => 'Setup ready',
    'admin.tool.pending.h3'                  => 'Pending Anime',
    'admin.tool.pending.desc'                => 'Newly added anime are created with <code>source=\'local\'</code> by default and do not go to the server. Use this tool to promote selected anime to <code>source=\'catalog\'</code>, then push them via admin_sync.',
    'admin.tool.pending.link.count'          => '%d pending anime',
    'admin.tool.pending.link.open'           => 'Open list',
    'admin.tool.pending.status_ok'           => 'No pending items',
    'admin.tool.pending.missing_file'        => 'Missing file:',
    'admin.back_to_home'                     => 'Back to home',

    // -----------------------------------------------------------------
    // admin_pending.php - source='local' anime promotion to catalog
    // -----------------------------------------------------------------
    'admin_pending.localhost_only'           => 'This page is only accessible via localhost.',
    'admin_pending.error.csrf'               => 'Invalid CSRF token.',
    'admin_pending.error.no_selection'       => 'No anime selected.',
    'admin_pending.error.invalid_id'         => 'Invalid anime ID.',
    'admin_pending.error.unknown_action'     => 'Unknown action.',
    'admin_pending.success.promoted_some'    => '%d anime promoted to the catalog. Use admin_sync.php to push them to the server.',
    'admin_pending.success.promoted_all'     => '%d anime promoted to the catalog.',
    'admin_pending.success.demoted'          => 'Anime removed from the catalog (set back to local).',
    'admin_pending.page_title'               => 'Admin: Pending Anime - Anime Tracker',
    'admin_pending.heading'                  => 'Pending Anime',
    'admin_pending.subtitle'                 => 'source=\'local\' - not promoted to catalog, not sent to server',
    'admin_pending.badge.catalog'            => 'Catalog:',
    'admin_pending.badge.local'              => 'Local:',
    'admin_pending.empty'                    => 'No pending anime. All local entries are already in the catalog.<br><br>New anime show up here after you add them. Select them and click "Promote to Catalog", then push them to the server via admin_sync.php.',
    'admin_pending.btn.select_all'           => 'Select All',
    'admin_pending.btn.clear_selection'      => 'Clear Selection',
    'admin_pending.btn.promote_selected'     => 'Promote Selected',
    'admin_pending.btn.promote_all'          => 'Promote All',
    'admin_pending.confirm.promote_all'      => 'Are you sure you want to promote ALL %d anime to the catalog?',
    'admin_pending.col.title'                => 'Title',
    'admin_pending.col.broadcast_status'     => 'Broadcast Status',
    'admin_pending.col.watch_status'         => 'Watch Status',
    'admin_pending.col.external_ids'         => 'MAL / AniDB',
    'admin_pending.col.added'                => 'Added',
    'admin_pending.back_to_dashboard'        => 'Admin dashboard',

    // -----------------------------------------------------------------
    // admin_sync.php (admin_sync_example.php sablonu) - sunucuya push
    // -----------------------------------------------------------------
    'admin_sync.error.csrf'                  => 'Invalid CSRF token.',
    'admin_sync.error.no_secret'             => 'ADMIN_PUSH_SECRET is not defined in config.php.',
    'admin_sync.error.curl'                  => 'cURL error: %s',
    'admin_sync.error.invalid_response'      => 'Invalid server response (HTTP %d): %s',
    'admin_sync.error.server'                => 'Server error (HTTP %d): %s',
    'admin_sync.page_title'                  => 'Admin Sync - Anime Tracker',
    'admin_sync.heading'                     => 'Admin Sync',
    'admin_sync.intro'                       => 'Pushes your local catalog to the server. Used only by you (the admin). Your personal watch data (watched, status, notes) is NOT sent - only catalog info (titles, synopsis, links, chronology) is transferred.',
    'admin_sync.pending.title'               => 'There are %d pending anime',
    'admin_sync.pending.body'                => 'There are still %d anime with <code>source=\'local\'</code> in the local DB - this push <strong>will not send them to the server</strong>. First promote them via the <a href="admin_pending.php">Pending Anime</a> page, then push from here.',
    'admin_sync.setup.title'                 => 'Setup required',
    'admin_sync.setup.body'                  => 'Create an <code>admin_secret.php</code> file in the project root (never commit it to GitHub - it is listed in <code>.gitignore</code>):',
    'admin_sync.setup.match_note'            => 'The same secret must match exactly with <code>ADMIN_SECRET</code> in the server\'s <code>private/admin_push_config.php</code> file.',
    'admin_sync.box.error_title'             => 'Error',
    'admin_sync.box.success_title'           => 'Server updated',
    'admin_sync.stat.inserted'               => 'new anime added',
    'admin_sync.stat.updated'                => 'existing anime updated',
    'admin_sync.stat.markers'                => 'chronology marker(s)',
    'admin_sync.summary'                     => 'Sent: %d anime, %d chronology marker(s).',
    'admin_sync.reminder'                    => '<strong>Reminder:</strong> Do not forget to upload poster images for newly added anime to the server\'s <code>uploads/</code> folder via FTP. Otherwise the first user sync will get a 404 trying to download the poster.',
    'admin_sync.btn.push'                    => 'Push to Server',
    'admin_sync.confirm.push'                => 'The local catalog will be pushed to the server. Continue?',
    'admin_sync.back_to_settings'            => 'Back to List Settings',

];
