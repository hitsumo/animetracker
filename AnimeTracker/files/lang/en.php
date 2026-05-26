<?php

/**
 * Anime Tracker - English UI Translations
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * ---
 *
 * English translation dictionary. Mirror of lang/tr.php with the same
 * keys and English values. See lang/tr.php for the key naming
 * convention and how to add a new translation.
 *
 * Keys missing from this file fall back to the Turkish value
 * automatically (the t() helper handles that), so an in-progress
 * translation never leaves the user with a blank string or a raw
 * dictionary key.
 */

return [

    // -----------------------------------------------------------------
    // Header navigation
    // -----------------------------------------------------------------

    'nav.what_to_watch'   => 'What Should I Watch?',
    'nav.recent_edits'    => 'Recently Edited',
    'nav.list_settings'   => 'List Settings',
    'nav.statistics'      => 'Statistics',
    'nav.help'            => 'Help',
    'nav.about'           => 'About',

    // -----------------------------------------------------------------
    // Language switcher
    // -----------------------------------------------------------------

    'lang.tr_label'       => 'TR',
    'lang.en_label'       => 'EN',
    'lang.aria_label'     => 'Change language',

    // -----------------------------------------------------------------
    // index.php - the main anime list page
    // -----------------------------------------------------------------

    'index.page_title'              => 'Anime Watch List',
    'index.list_title'              => 'Anime Watch List',

    'index.search.placeholder'      => 'Search anime...',
    'index.search.submit'           => 'Search',
    'index.search.clear'            => 'Clear',

    'index.filter.genre'            => 'Filter by Genre:',
    'index.filter.watch_status'     => 'Filter by Watch Status:',
    'index.filter.broadcast'        => 'Filter by Broadcast Status:',
    'index.filter.letter'           => 'Filter by Letter',
    'index.filter.per_page'         => 'Show per Page:',
    'index.filter.all'              => 'All',
    'index.filter.show_all'         => 'All',
    'index.filter.submit'           => 'Filter',

    // Broadcast status labels. The DB stores the Turkish strings as
    // free-text values in animes.status (legacy), so the lookup is
    // done by exact string match in PHP before the label is shown.
    'index.broadcast.ongoing'       => 'Currently Airing',
    'index.broadcast.finished'      => 'Finished Airing',

    'index.add_anime'               => 'Add New Anime',

    'index.col.anime'               => 'Anime',
    'index.col.status'              => 'Status',
    'index.col.watched_episodes'    => 'Watched Episodes',
    'index.col.image'               => 'Image',
    'index.col.next_episode'        => 'Next Episode',
    'index.col.action'              => 'Action',

    'index.row.title_tooltip'       => 'Click to see full title',
    'index.row.ep_minus_tooltip'    => 'Back one episode',
    'index.row.ep_plus_tooltip'     => 'Forward one episode',
    'index.row.ep_aired_badge'      => '(airing)',
    'index.row.more_button'         => 'More',
    'index.row.edit_button'         => 'Edit',
    'index.row.delete_button'       => 'Delete',
    'index.row.delete_confirm'      => 'Are you sure you want to delete this anime?',
    'index.row.no_results'          => 'No anime found.',

    'index.pagination.info'         => '%d anime, page %d/%d (%d-%d)',
    'index.pagination.prev'         => '&laquo; Previous',
    'index.pagination.next'         => 'Next &raquo;',

    'index.js.update_failed'        => 'Episode could not be updated. Please refresh and try again.',
    'index.js.network_error'        => 'Server unreachable. Please check your internet connection.',

    // -----------------------------------------------------------------
    // anime_details.php
    // -----------------------------------------------------------------

    'anime_details.title_suffix'         => 'Details',

    'anime_details.label.status'         => 'Status:',
    'anime_details.label.total_episodes' => 'Total Episodes:',
    'anime_details.label.unknown'        => 'Unknown',
    'anime_details.label.aired_episodes' => 'Aired Episodes:',
    'anime_details.label.release_date'   => 'Release Date:',
    'anime_details.label.end_date'       => 'End Date:',
    'anime_details.label.unset'          => 'Not set',
    'anime_details.label.broadcast_attribution' => 'Broadcast time data from %s',
    'anime_details.label.watched_episodes' => 'Watched Episodes:',
    'anime_details.label.synopsis'       => 'Synopsis:',
    'anime_details.label.user_synopsis'  => 'Personal Synopsis:',
    'anime_details.label.genres'         => 'Genres:',
    'anime_details.label.watch_status'   => 'Watch Status:',
    'anime_details.label.emotion'        => 'Emotion:',
    'anime_details.label.broadcast_day'  => 'Broadcast Day:',
    'anime_details.label.broadcast_time' => 'Broadcast Time:',
    'anime_details.label.next_episode'   => 'Next Episode:',
    'anime_details.label.notes'          => 'Notes:',

    'anime_details.btn.chronology'       => 'Chronology',
    'anime_details.btn.series_chronology' => 'Series Chronology',
    'anime_details.btn.edit'             => 'Edit',
    'anime_details.btn.back'             => 'Back',

    'anime_details.section.external_sites' => 'Anime Sites',
    'anime_details.section.next_up'      => 'Next Up',
    'anime_details.section.related'      => 'Related Anime',
    'anime_details.section.related_other_type' => 'Other',
    'anime_details.section.chronology'   => 'Chronology Notes',

    'anime_details.alert.watch_after'    => 'Watch after episode %d:',

    'anime_details.marker.after_episode' => 'After episode %d',
    'anime_details.marker.delete_tooltip' => 'Delete',
    'anime_details.marker.delete_confirm' => 'Are you sure you want to delete this chronology note?',

    'anime_details.marker_form.title'    => 'Add New Chronology Note',
    'anime_details.marker_form.after_episode' => 'After episode:',
    'anime_details.marker_form.after_episode_placeholder' => 'e.g. 23',
    'anime_details.marker_form.target_anime' => 'Anime to watch:',
    'anime_details.marker_form.choose'   => 'Choose...',
    'anime_details.marker_form.note'     => 'Note (optional):',
    'anime_details.marker_form.note_placeholder' => 'e.g. Canonical chronology',
    'anime_details.marker_form.submit'   => 'Add',

    'anime_details.js.operation_failed'  => 'Operation failed.',
    'anime_details.js.connection_error'  => 'Connection error. Please try again.',

    'anime_details.error.not_found'      => 'Anime not found.',

    // -----------------------------------------------------------------
    // edit_anime.php
    // -----------------------------------------------------------------

    'edit_anime.page_title'              => 'Edit Anime',
    'edit_anime.back_to_list'            => 'Anime Watch List',

    'edit_anime.validation.anidb_required' => 'AniDB link is required.',
    'edit_anime.validation.anidb_invalid'  => 'AniDB link is invalid. Must be an anidb.net address. Example: https://anidb.net/anime/12345 or https://anidb.net/episode/212772',
    'edit_anime.validation.release_date_format' => 'Release date has invalid format. Correct format: YYYY-MM-DD (e.g. 2026-04-08)',
    'edit_anime.validation.end_date_format'     => 'End date has invalid format. Correct format: YYYY-MM-DD (e.g. 2026-09-15)',
    'edit_anime.validation.next_date_format'    => 'Next episode date has invalid format.',

    'edit_anime.error_page.form_title'   => 'Form Error',
    'edit_anime.error_page.upload_title' => 'Upload Error',
    'edit_anime.error_page.upload_h1'    => 'Image Upload Error',
    'edit_anime.error_page.go_back_fix'  => 'Go back and fix',
    'edit_anime.error_page.go_back_retry' => 'Go back and try again',
    'edit_anime.error_page.duplicate_title' => 'Duplicate Data',
    'edit_anime.error_page.duplicate_h1' => 'Duplicate Data Error',
    'edit_anime.error_page.dup_used_in_another' => 'is in use in another record.',
    'edit_anime.error_page.dup_conflicting_record' => 'The conflicting record:',
    'edit_anime.error_page.dup_go_to_record' => 'Go to conflicting record',
    'edit_anime.error_page.dup_go_to_list' => 'Go to anime list',
    'edit_anime.error_page.dup_field_catalog_uuid' => 'Catalog UUID',
    'edit_anime.error_page.dup_field_undefined'    => 'undefined UNIQUE field',

    'edit_anime.label.anime_name'        => 'Anime Name:',
    'edit_anime.label.alt_titles'        => 'Alternative Titles:',
    'edit_anime.btn.add_alt_title'       => 'Add Alternative Title',
    'edit_anime.placeholder.alt_title'   => 'Alternative title',
    'edit_anime.label.synopsis'          => 'Synopsis:',
    'edit_anime.placeholder.synopsis'    => 'Write the anime synopsis',
    'edit_anime.help.synopsis_readonly'  => 'comes from server, updated via sync',
    'edit_anime.label.user_synopsis'     => 'Personal Synopsis:',
    'edit_anime.placeholder.user_synopsis' => 'Your own commentary, translation, summary',
    'edit_anime.help.user_synopsis'      => 'personal synopsis - if deleted, sync will not restore it',

    'edit_anime.label.total_episodes'    => 'Total Episode Count:',
    'edit_anime.placeholder.total_unknown' => 'Leave blank if unknown',
    'edit_anime.label.aired_episodes'    => 'Aired Episode Count:',
    'edit_anime.placeholder.aired'       => 'Episodes aired so far',
    'edit_anime.btn.sync_aired'          => 'Synchronize',

    'edit_anime.label.release_date'      => 'Release Date:',
    'edit_anime.label.end_date'          => 'End Date:',

    'edit_anime.label.broadcast_status'  => 'Broadcast Status:',
    'edit_anime.help.status_locked'      => 'This anime has finished airing, status cannot be changed.',
    'edit_anime.option.choose'           => 'Choose...',
    'edit_anime.label.episode_interval'  => 'Episode Interval (Days):',
    'edit_anime.label.broadcast_day'     => 'Broadcast Day:',
    'edit_anime.label.broadcast_time'    => 'Broadcast Time:',
    'edit_anime.label.broadcast_timezone' => 'Broadcast Timezone:',

    'edit_anime.day.monday'              => 'Monday',
    'edit_anime.day.tuesday'             => 'Tuesday',
    'edit_anime.day.wednesday'           => 'Wednesday',
    'edit_anime.day.thursday'            => 'Thursday',
    'edit_anime.day.friday'              => 'Friday',
    'edit_anime.day.saturday'            => 'Saturday',
    'edit_anime.day.sunday'              => 'Sunday',

    'edit_anime.tz.jp'                   => 'Japan (Tokyo) - JST',
    'edit_anime.tz.tr'                   => 'Turkey (Istanbul) - TRT',
    'edit_anime.tz.utc'                  => 'UTC',
    'edit_anime.tz.us_east'              => 'USA East (New York) - ET',
    'edit_anime.tz.us_west'              => 'USA West (Los Angeles) - PT',
    'edit_anime.tz.uk'                   => 'United Kingdom (London)',

    'edit_anime.label.watch_status'      => 'Watch Status:',
    'edit_anime.label.watched_episodes'  => 'Watched Episode Count:',

    'edit_anime.label.genres'            => 'Genres:',
    'edit_anime.option.pick_existing_genre' => 'Pick from Existing Genres',
    'edit_anime.placeholder.new_genre'   => 'Add new genre',
    'edit_anime.btn.add'                 => 'Add',

    'edit_anime.label.tags'              => 'Tags:',
    'edit_anime.placeholder.tag'         => 'Add tag (e.g. Set at school, Sports theme)...',
    'edit_anime.help.tags'               => 'Matches appear as you type. Press Enter to create a new tag if none match.',
    'edit_anime.link.manage_tags'        => 'Manage tags',

    'edit_anime.label.notes'             => 'Notes:',
    'edit_anime.help.notes'              => 'notes - if deleted, sync will not restore them',

    'edit_anime.label.series_name'       => 'Series Name (optional):',
    'edit_anime.placeholder.series_name' => 'e.g. Detective Conan, Spy x Family',
    'edit_anime.help.series_name'        => 'Anime in the same franchise share this name.',
    'edit_anime.label.media_type'        => 'Media Type (optional):',
    'edit_anime.label.next_in_series'    => 'Next Anime (optional):',
    'edit_anime.help.next_in_series'     => 'Anime to watch after finishing this one. ★ = same series.',

    'edit_anime.label.anidb_link'        => 'AniDB Link:',
    'edit_anime.placeholder.anidb_link'  => 'https://anidb.net/anime/12345 or /episode/12345',
    'edit_anime.label.mal_link'          => 'MyAnimeList Link:',
    'edit_anime.placeholder.mal_link'    => 'https://myanimelist.net/anime/12345',
    'edit_anime.label.schedule_link'     => 'AnimeSchedule Link:',
    'edit_anime.placeholder.schedule_link' => 'https://animeschedule.net/anime/...',
    'edit_anime.btn.auto_fill'           => 'Auto Fill',

    'edit_anime.label.upload_image'      => 'Upload Image:',
    'edit_anime.btn.choose_file'         => 'Choose File',
    'edit_anime.placeholder.no_file'     => 'No file selected',

    'edit_anime.btn.submit'              => 'Update',
    'edit_anime.btn.cancel'              => 'Cancel',

    'edit_anime.js.genre_add_failed'     => 'Failed to add genre',
    'edit_anime.js.url_required'         => 'Enter the AnimeSchedule URL first.',
    'edit_anime.js.fetching_schedule'    => 'Fetching data from AnimeSchedule...',
    'edit_anime.js.fetching_aired'       => 'Fetching episode count from AnimeSchedule...',
    'edit_anime.js.unknown_error'        => 'Unknown error.',
    'edit_anime.js.field_not_found'      => '%s (field not found)',
    'edit_anime.js.no_empty_fields'      => 'No empty fields to fill (all fields are populated).',
    'edit_anime.js.fields_filled'        => '%d field(s) filled: %s.',
    'edit_anime.js.request_failed'       => 'Request failed: %s',
    'edit_anime.js.this_week'            => ' (this week)',
    'edit_anime.js.last_week'            => ' (last week)',
    'edit_anime.js.weeks_ago'            => ' (%d weeks ago)',
    'edit_anime.js.updated_value'        => 'Updated: %s -> %s%s',
    'edit_anime.js.already_up_to_date'   => 'Current value is up to date: %s%s',

];
