<?php

/**
 * Anime Tracker - English UI Translations
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
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
    'nav.recent_edits'    => 'Recently Updated',
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
    'index.watch_status.unselected' => 'Not Selected',
    'index.warn.catalog_push_failed' => 'Saved, but the change could not be pushed to the central catalog. It is stored on this installation; saving the anime again will retry the push.',
    'index.filter.broadcast'        => 'Filter by Broadcast Status:',
    'index.filter.letter'           => 'Filter by Letter',
    // 1.1.14: year filter (single or multiple, non-contiguous years)
    'index.filter.year'             => 'Filter by Year',
    'index.filter.year_none'        => 'No release years recorded yet.',
    'index.filter.year_clear'       => 'Clear year selection',
    // 1.1.17: country filter. The dropdown lists ONLY countries entered on
    // at least one anime (DISTINCT over animes.country), so the _none
    // message shows while the catalog has none.
    'index.filter.country'          => 'Filter by Country:',
    'index.filter.country_none'     => 'No countries recorded yet.',
    'index.filter.per_page'         => 'Show per Page:',
    'index.filter.all'              => 'All',
    'index.filter.show_all'         => 'All',
    'index.filter.submit'           => 'Filter',
    // 1.1.5: active emotion-filter chip (arrived from the statistics emotion badge)
    'index.filter.emotion_active'   => 'Emotion filter: %s',
    'index.filter.emotion_clear'    => 'Clear filter',

    // 1.1.13: General / Personal list tabs (between pagination and the table).
    // Personal = anime the user has set a watch status on.
    'index.tab.all'                 => 'General List',
    'index.tab.personal'            => 'Personal List',

    // 1.1.17: country names. animes.country stores an ISO 3166-1 alpha-2
    // CODE (JP, CN, ...); the user never types or sees the code - the form
    // dropdown, the anime detail page and the filter all print these names
    // via country_label(). To add a country: one line in country_codes()
    // in country_helpers.php plus one line here.
    'country.jp'                    => 'Japan',
    'country.cn'                    => 'China',
    'country.kr'                    => 'South Korea',
    'country.tw'                    => 'Taiwan',
    'country.us'                    => 'United States',
    'country.fr'                    => 'France',

    // 1.1.20: alternative-title languages. animes.alternative_titles gives
    // each name an optional [xx] prefix ([en]My Neighbor Totoro); the user
    // never types or sees the code - the dropdown on the add/edit form
    // prints these names. To add a language: one line in title_lang_codes()
    // in title_lang_helpers.php plus one line here.
    'title_lang.en'                 => 'English',
    'title_lang.ja'                 => 'Japanese',
    'title_lang.tr'                 => 'Turkish',
    'title_lang.zh'                 => 'Chinese',
    'title_lang.ko'                 => 'Korean',
    'title_lang.fr'                 => 'French',

    // Broadcast status labels. The DB stores the Turkish strings as
    // free-text values in animes.status (legacy), so the lookup is
    // done by exact string match in PHP before the label is shown.
    'index.broadcast.ongoing'       => 'Currently Airing',
    'index.broadcast.finished'      => 'Finished Airing',
    // 1.1.10: three new broadcast-status values.
    'index.broadcast.not_started'   => 'Not Yet Aired',
    'index.broadcast.unselected'    => 'Not Selected',
    'index.broadcast.cancelled'     => 'Cancelled',

    'index.add_anime'               => 'Add New Anime',
    'index.pending_link'            => 'Pending (%d)',
    'pending.page_title'            => 'Pending',
    'pending.heading'               => 'Pending',
    'pending.intro'                 => 'Anime added by users that are awaiting moderator approval. Once approved they appear in the catalog.',
    'pending.badge'                 => 'Pending approval',
    'pending.empty'                 => 'There are no pending additions right now.',
    'pending.back'                  => 'Back to the catalog',

    'index.col.anime'               => 'Anime',
    'index.col.status'              => 'Status',
    'index.col.watched_episodes'    => 'Watched Episodes',
    'index.col.episode_count'       => 'Episode Count',
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
    'anime_details.label.country'        => 'Country of Origin:',
    'anime_details.label.unset'          => 'Not set',
    'anime_details.label.broadcast_attribution' => 'Broadcast time data from %s',
    'anime_details.label.watched_episodes' => 'Watched Episodes:',
    // 1.1.27 - tooltips for the quick +/- buttons on the detail page.
    // Detail-page counterparts of the list's index.row.ep_*_tooltip keys;
    // the text matches but the keys stay namespaced per page.
    'anime_details.ep.minus_tooltip'     => 'Back one episode',
    'anime_details.ep.plus_tooltip'      => 'Forward one episode',
    'anime_details.label.watch_start_date' => 'Started:',
    'anime_details.label.watch_finish_date' => 'Finished:',
    'anime_details.label.synopsis'       => 'Synopsis:',
    'anime_details.synopsis.auto_translated' => 'Auto-translated from Turkish',
    'anime_details.synopsis.en_unavailable'  => 'English synopsis not available — showing the Turkish original.',
    'anime_details.label.user_synopsis'  => 'Personal Synopsis:',
    'anime_details.label.genres'         => 'Genres:',
    'anime_details.label.watch_status'   => 'Watch Status:',
    'anime_details.label.emotion'        => 'Emotion:',
    'anime_details.label.broadcast_day'  => 'Broadcast Day:',
    'anime_details.label.broadcast_time' => 'Broadcast Time:',
    'anime_details.label.next_episode'   => 'Next Episode:',
    // 1.1.29: label for the countdown on an anime that has not started.
    // "Next Episode" would be misleading - there is no previous one yet.
    'anime_details.label.premiere'       => 'Premiere:',
    'anime_details.label.notes'          => 'Notes:',

    'anime_details.btn.chronology'       => 'Chronology',
    'anime_details.btn.series_chronology' => 'Series Chronology',
    'anime_details.btn.edit'             => 'Edit',
    'anime_details.btn.back'             => 'Back',
    'anime_details.suggest.title'        => 'Suggest a Correction',
    'anime_details.suggest.intro'        => 'Spotted wrong or missing info for this anime? Write it below; a moderator will review it.',
    'anime_details.suggest.placeholder'  => 'Describe your suggestion (e.g. wrong air date, missing genre...).',
    'anime_details.suggest.submit'       => 'Send Suggestion',
    'anime_details.suggest.ok'           => 'Thanks, your suggestion was received and is now in the moderation queue.',
    'anime_details.suggest.rate'         => 'You have sent too many suggestions. Please try again later.',
    'anime_details.suggest.err'          => 'The suggestion could not be sent. Please try again.',

    'anime_details.section.external_sites' => 'Anime Sites',
    'anime_details.section.next_up'      => 'Next Up',
    'anime_details.section.related'      => 'Related Anime',
    'anime_details.section.related_other_type' => 'Other',
    'anime_details.section.chronology'   => 'Chronology Notes',

    'anime_details.alert.watch_after'    => 'Watch after episode %d:',

    'anime_details.marker.after_episode' => 'After episode %d',
    'anime_details.marker.story_placeholder' => 'Story ep.',                 // 1.1.15
    'anime_details.marker.story_edit_hint' => 'Which episode it should be watched after in story order (empty = same as release)', // 1.1.15
    'anime_details.marker.release_edit_hint' => 'Which episode it aired after in release order', // 1.1.15
    'anime_details.marker.story_save' => 'Save story point',                 // 1.1.15
    'anime_details.marker.delete_tooltip' => 'Delete',
    'anime_details.marker.delete_confirm' => 'Are you sure you want to delete this chronology note?',

    // Chronology display mode (1.1.15)
    'chrono.mode.release'     => 'Release Order',
    'chrono.mode.story'       => 'Story Order',
    'chrono.mode.both'        => 'Both',
    'chrono.mode.showing'     => 'Showing: %s',
    'chrono.mode.toggle_hint' => 'Change view (release → story → both)',

    // Synopsis spoiler guard (1.1.33). %s = title of the nearest unwatched
    // entry in the chain, %d = how many further entries are unwatched.
    'spoiler.notice_fmt'      => '%s is still unwatched — this synopsis may spoil the earlier season.',
    'spoiler.notice_more_fmt' => '%s and %d more entries in the chain are still unwatched — this synopsis may spoil earlier seasons.',
    'spoiler.reveal'          => 'Let me read it anyway',
    'spoiler.hide'            => 'Hide synopsis',

    'anime_details.marker_form.title'    => 'Add New Chronology Note',
    'anime_details.marker_form.after_episode' => 'In release order (after episode):',
    'anime_details.marker_form.after_episode_placeholder' => 'e.g. 46',
    'anime_details.marker_form.story_after_episode' => 'In story order (optional):',                  // 1.1.15
    'anime_details.marker_form.story_after_episode_placeholder' => 'e.g. 35 (empty = same as release)', // 1.1.15
    'anime_details.marker_form.target_anime' => 'Anime to watch:',
    'anime_details.marker_form.choose'   => 'Choose...',
    'anime_details.marker_form.note'     => 'Note (optional):',
    'anime_details.marker_form.note_placeholder' => 'e.g. Canonical chronology',
    'anime_details.marker_form.submit'   => 'Add',

    'anime_details.js.operation_failed'  => 'Operation failed.',
    'anime_details.js.connection_error'  => 'Connection error. Please try again.',

    'anime_details.error.not_found'      => 'Anime not found.',
    // 1.1.2 - adult (18+) content neutral hidden notice (detail page gate)
    'anime_details.adult.hidden'         => 'This content is hidden. To view it, turn on "Show adult content" in List Settings.',
    // 1.1.2 - neutral placeholder title for +18 nodes in ordered relations (chronology/series)
    'adult.hidden_node_title'            => 'Hidden content',

    // -----------------------------------------------------------------
    // add_anime.php - new anime entry form
    // -----------------------------------------------------------------

    // Page meta
    'add_anime.page_title'                   => 'Add Anime to List',
    'add_anime.heading'                      => 'Add Anime to List',

    // Form field labels
    'add_anime.label.title'                  => 'Anime Title:',
    'add_anime.label.alternative_titles'     => 'Alternative Titles:',
    'add_anime.label.synopsis'               => 'Synopsis (TR):',
    'add_anime.label.total_episodes'         => 'Total Episodes:',
    'add_anime.label.aired_episodes'         => 'Aired Episodes:',
    'add_anime.label.release_date'           => 'Release Date:',
    'add_anime.label.end_date'               => 'End Date:',

    // 1.1.31 - partially known dates. The dropdown next to a date field says
    // how much of it is known; unknown parts print as "??"
    // (??.04.2005 / ??.??.2005 / ??.??.????).
    'add_anime.date_precision.full'          => 'Full date',
    'add_anime.date_precision.month'         => 'Month and year',
    'add_anime.date_precision.year'          => 'Year only',
    'add_anime.date_precision.none'          => 'Unknown',
    'add_anime.option.month'                 => 'Month',
    'add_anime.ph.date_year'                 => 'YYYY',
    'add_anime.hint.date_precision'          => 'Unknown parts are shown as ??.??.????. A date left empty counts as "Not specified" instead.',
    'add_anime.label.status'                 => 'Broadcast Status:',
    'add_anime.label.episode_interval'       => 'Episode Interval (Days):',
    'add_anime.label.broadcast_day'          => 'Broadcast Day:',
    'add_anime.label.broadcast_time'         => 'Broadcast Time:',
    'add_anime.label.broadcast_timezone'     => 'Broadcast Timezone:',
    'add_anime.label.watch_status'           => 'Watch Status:',
    'add_anime.label.watched_episodes'       => 'Watched Episodes:',
    'add_anime.label.watch_dates'            => 'Watch Dates (optional):',
    'add_anime.label.watch_start_date'       => 'Start:',
    'add_anime.label.watch_finish_date'      => 'Finish:',
    'add_anime.label.genres'                 => 'Genres:',
    'add_anime.label.tags'                   => 'Sentences:',
    'add_anime.label.notes'                  => 'Notes:',
    'add_anime.label.series_name'            => 'Series Name (optional):',
    'add_anime.label.media_type'             => 'Media Type (optional):',
    'add_anime.label.country'                => 'Country of Origin (optional):',
    'add_anime.label.anidb_link'             => 'AniDB Link:',
    'add_anime.label.mal_link'               => 'MyAnimeList Link:',
    'add_anime.label.animeschedule_link'     => 'AnimeSchedule Link:',
    'add_anime.label.image'                  => 'Upload Image:',

    // Input placeholders
    'add_anime.ph.alternative_title'         => 'Alternative title',
    'add_anime.opt.alt_title_lang_none'      => '— No language set',
    'add_anime.hint.alternative_titles'      => 'You can pick each alternative title\'s language in the box next to it. The one marked "English" is shown instead of the Romaji title while "show English titles" is enabled in List Settings.',
    'add_anime.ph.synopsis'                  => 'Write the anime synopsis',
    'add_anime.label.synopsis_en'            => 'Synopsis (EN):',
    'add_anime.ph.synopsis_en'               => 'English synopsis (AI-translated)',
    'add_anime.ph.total_episodes'            => 'Leave blank if unknown',
    'add_anime.ph.aired_episodes'            => 'Episodes aired so far',
    'add_anime.ph.new_genre'                 => 'Add new genre',
    'add_anime.ph.tag_input'                 => 'Add sentence (e.g. Set in school, Sports theme)...',
    'add_anime.ph.series_name'               => 'e.g. Detective Conan, Spy x Family',
    'add_anime.ph.anidb_link'                => 'https://anidb.net/anime/12345 or /episode/12345',
    'add_anime.ph.mal_link'                  => 'https://myanimelist.net/anime/12345',
    'add_anime.ph.animeschedule_link'        => 'https://animeschedule.net/anime/...',

    // Buttons
    'add_anime.btn.add_alternative_title'    => 'Add Alternative Title',
    'add_anime.btn.add_genre'                => 'Add',
    'add_anime.btn.animeschedule_fetch'      => 'Auto-fill',
    'add_anime.btn.choose_file'              => 'Choose File',
    'add_anime.btn.submit'                   => 'Add',
    'add_anime.btn.cancel'                   => 'Cancel',

    // Generic select options
    'add_anime.option.choose'                => 'Select...',
    'add_anime.option.choose_from_existing'  => 'Choose from existing genres',

    // Broadcast day labels. The DB stores Turkish day names as
    // values (broadcast_day column), so the values stay TR and only
    // the displayed label is translated.
    'add_anime.day.monday'                   => 'Monday',
    'add_anime.day.tuesday'                  => 'Tuesday',
    'add_anime.day.wednesday'                => 'Wednesday',
    'add_anime.day.thursday'                 => 'Thursday',
    'add_anime.day.friday'                   => 'Friday',
    'add_anime.day.saturday'                 => 'Saturday',
    'add_anime.day.sunday'                   => 'Sunday',

    // Broadcast timezone labels (value attribute stays as IANA tz id)
    'add_anime.tz.tokyo'                     => 'Japan (Tokyo) - JST',
    'add_anime.tz.istanbul'                  => 'Turkey (Istanbul) - TRT',
    'add_anime.tz.utc'                       => 'UTC',
    'add_anime.tz.new_york'                  => 'US East (New York) - ET',
    'add_anime.tz.los_angeles'               => 'US West (Los Angeles) - PT',
    'add_anime.tz.london'                    => 'United Kingdom (London)',

    // File upload UI
    'add_anime.file.no_file'                 => 'No file selected',

    // Form hints (small.form-text)
    'add_anime.hint.notes'                   => 'notes are not restored by sync if deleted',
    'add_anime.hint.watch_dates'             => 'Entered manually, may be left blank. Personal; not shared via catalog sync.',
    'add_anime.warn.date_order'              => 'Finish date is before start. It will still be saved.',
    'add_anime.hint.series_name'             => 'Anime in the same series share this name. Existing series are auto-suggested.',
    'add_anime.hint.tags'                    => 'Matching sentences appear as you type. Press Enter to create a new one if there is no match.',
    'add_anime.link.manage_tags'             => 'Manage sentences',

    // CSRF rejection
    'add_anime.csrf.invalid'                 => 'Invalid CSRF token. Please refresh the page and try again.',

    // Server-side validation errors (shown on the form error page)
    'add_anime.error.mal_link_required'      => 'MyAnimeList link is required.',
    'add_anime.error.mal_link_invalid'       => 'MyAnimeList link has invalid format. Example: https://myanimelist.net/anime/12345',
    'add_anime.error.anidb_link_required'    => 'AniDB link is required.',
    'add_anime.error.anidb_link_invalid'     => 'AniDB link is invalid. Must be an anidb.net address. Example: https://anidb.net/anime/12345 or https://anidb.net/episode/212772',
    'add_anime.error.release_date_invalid'   => 'Release date has invalid format. Correct format: YYYY-MM-DD (e.g. 2026-04-08)',
    'add_anime.error.end_date_invalid'       => 'End date has invalid format. Correct format: YYYY-MM-DD (e.g. 2026-09-15)',
    'add_anime.error.release_date_year_invalid' => 'Release date year is invalid. Enter a four-digit year (e.g. 2005).',
    'add_anime.error.end_date_year_invalid'  => 'End date year is invalid. Enter a four-digit year (e.g. 2005).',
    'add_anime.error.next_episode_date_invalid' => 'Next episode date has invalid format.',

    // Error pages (validation / image upload / duplicate)
    'add_anime.error_page.form_error_title'  => 'Form Error',
    'add_anime.error_page.image_error_title' => 'Image Upload Error',
    'add_anime.error_page.duplicate_title'   => 'Duplicate Data Error',
    'add_anime.error_page.duplicate_heading' => 'This anime is already in your list',
    'add_anime.error_page.go_back_and_fix'   => 'Go back and fix',
    'add_anime.error_page.go_back_and_retry' => 'Go back and try again',
    'add_anime.error_page.go_to_existing'    => 'Go to existing entry',
    'add_anime.error_page.go_to_list'        => 'Go to anime list',

    // Duplicate detection - field labels and detail message fragments
    'add_anime.duplicate.field_mal_id'       => 'MAL ID',
    'add_anime.duplicate.field_anidb_id'     => 'AniDB ID',
    'add_anime.duplicate.field_catalog_uuid' => 'Catalog UUID',
    'add_anime.duplicate.field_unknown'      => 'unknown UNIQUE field',
    'add_anime.duplicate.already_exists_suffix' => 'is already registered.',
    'add_anime.duplicate.existing_record_prefix' => 'Existing entry:',

    // JS-side messages (injected via LANG constant in <script>)
    'add_anime.js.genre_add_failed'          => 'Failed to add genre',
    'add_anime.js.create_new_tag_prefix'     => '+ Create new sentence:',
    'add_anime.js.enter_animeschedule_url'   => 'Enter the AnimeSchedule URL first.',
    'add_anime.js.fetching'                  => 'Fetching data from AnimeSchedule...',
    'add_anime.js.unknown_error'             => 'Unknown error.',
    'add_anime.js.field_not_found_suffix'    => '(field not found)',
    // 1.1.27 - two extra notes that keep the autofill report honest.
    'add_anime.js.field_hidden_suffix'       => '(in a hidden section)',
    'add_anime.js.field_value_rejected_suffix' => '(value rejected)',
    'add_anime.js.no_empty_fields'           => 'No empty fields to fill (all fields populated).',
    'add_anime.js.fields_filled_prefix'      => 'Fields filled:',
    'add_anime.js.request_failed_prefix'     => 'Request failed:',

    // 1.1.26 - [[anime:...]] link picker for synopsis fields (js/synopsis_link.js).
    'add_anime.js.synlink.btn'               => 'Add anime link',
    'add_anime.js.synlink.hint'              => 'Pick an anime and the link code is inserted at the caret.',
    'add_anime.js.synlink.search_ph'         => 'Type an anime title...',
    'add_anime.js.synlink.searching'         => 'Searching...',
    'add_anime.js.synlink.no_results'        => 'No matching anime',
    'add_anime.js.synlink.failed'            => 'Search failed',

    // -----------------------------------------------------------------
    // edit_anime.php - mevcut anime duzenleme sayfasi
    // -----------------------------------------------------------------
    //
    // edit_anime form yapisi add_anime ile buyuk olcude paraleldir, bu
    // yuzden tum label/placeholder/buton/gun/timezone/option/hint
    // anahtarlari add_anime.* uzerinden yeniden kullanilir (KARARLAR
    // Bolum 7 tek-kaynak prensibi). Asagidaki anahtarlar SADECE
    // edit_anime'a ozgu olanlar: sayfa basligi, Guncelle butonu,
    // kilitli durum uyarisi, Kisisel Konu (Mode 2) alanlari, Siradaki
    // Anime alani, duplicate hatasinin edit-tarafi metinleri ve aired
    // episodes sync (Senkronize Et) butonu icin JS string'leri.

    // Page meta
    'edit_anime.page_title'                  => 'Edit Anime',
    'edit_anime.heading'                     => 'Edit Anime',

    // Submit button (add_anime "Add" yerine "Update")
    'edit_anime.btn.submit'                  => 'Update',
    // 1.1.5: success banner shown when staying on the same page after update/add
    'edit_anime.notice.saved'                => 'Changes saved.',
    // 1.1.5: button from the edit page to the anime's detail page
    'edit_anime.btn.view_detail'             => 'Anime Details',
    // 1.1.8: admin-only full-catalog push button + confirm + result banners.
    // The normal "Update" now pushes only this anime's series; this button
    // resends the whole catalog (the online equivalent of admin_sync).
    'edit_anime.btn.full_push'               => 'Push Entire Catalog',
    'edit_anime.confirm.full_push'           => 'This will resend the entire catalog to the server. Continue?',
    'edit_anime.notice.full_pushed'          => 'Entire catalog pushed to the server (%d anime).',
    'edit_anime.notice.full_push_failed'     => 'Full catalog push failed. See the server log for details.',

    // Status field - locked hint shown when anime status is "Yayin
    // Tamamlandi" (the select is replaced with a readonly input).
    'edit_anime.status.locked_hint'          => 'Status cannot be changed because this anime has finished airing.',

    // Synopsis Mode 2 - user_synopsis is set, "Synopsis" becomes
    // readonly (server text) and "Personal Synopsis" is the editable
    // personal field. The label "Synopsis:" itself is shared with
    // add_anime.label.synopsis.
    'edit_anime.hint.synopsis_readonly'      => 'comes from server, updates via sync',
    'edit_anime.btn.copy_synopsis_tr'        => 'Copy',
    'edit_anime.hint.synopsis_en'            => 'Translate the text with an AI tool and paste it here. Shown with an "Auto-translated from Turkish" label on the detail page.',
    'edit_anime.label.mark_reviewed'         => 'Mark as reviewed',
    'edit_anime.hint.mark_reviewed'          => 'Tick if you have read and verified the English translation. Cleared automatically if you change the Turkish text.',
    'edit_anime.label.user_synopsis'         => 'Personal Synopsis (TR):',
    'edit_anime.ph.user_synopsis'            => 'Your own comment, translation, summary',
    'edit_anime.hint.user_synopsis'          => 'user synopsis section - if removed, sync will not restore it',
    'edit_anime.label.user_synopsis_en'      => 'Personal Synopsis (EN):',
    'edit_anime.ph.user_synopsis_en'         => 'Your own comment / translation / summary',

    // Next-in-series field (only on edit, not on add)
    'edit_anime.label.next_in_series'        => 'Next in Series (optional):',
    'edit_anime.hint.next_in_series'         => 'The anime to watch after this one. ★ = same series.',

    // Duplicate detection - edit-side wording differs from add (a value
    // is "used by another record" rather than "already exists").
    'edit_anime.duplicate.already_used_suffix'   => 'is used by another record.',
    'edit_anime.duplicate.conflicting_record_prefix' => 'Record causing the duplicate error:',
    'edit_anime.error_page.go_to_conflicting'    => 'Go to conflicting record',

    // Aired episodes sync button (only on edit) - JS LANG bloku
    'edit_anime.js.aired_sync.fetching'      => 'Fetching episode count from AnimeSchedule...',
    'edit_anime.js.aired_sync.this_week'     => ' (this week)',
    'edit_anime.js.aired_sync.last_week'     => ' (last week)',
    'edit_anime.js.aired_sync.weeks_ago_fmt' => ' (%d weeks ago)',
    'edit_anime.js.aired_sync.updated_prefix'   => 'Updated:',
    'edit_anime.js.aired_sync.no_change_prefix' => 'Current value already up to date:',

    // -----------------------------------------------------------------
    // help.php - kullanici yardim / nasil calisir sayfasi
    // -----------------------------------------------------------------

    // Page meta
    'help.page_title'                        => 'Help - Anime Tracker',
    'help.heading'                           => 'Help',
    'help.back_to_home'                      => 'Back to Home',
    'help.back_to_index'                     => 'Help Contents',

    // Help sub-page group titles (1.0.22 split)
    'help.group.basics.heading'              => 'Watching Basics — Statuses and Buttons',
    'help.group.basics.page_title'           => 'Watching Basics - Anime Tracker',
    'help.group.fields.heading'              => 'Fields and Personal Data',
    'help.group.fields.page_title'           => 'Fields and Personal Data - Anime Tracker',
    'help.group.sync.heading'                => 'Sync, Deletion and Updates',
    'help.group.sync.page_title'             => 'Sync, Deletion and Updates - Anime Tracker',
    'help.group.discovery.heading'           => 'Discovery and Interaction',
    'help.group.discovery.page_title'        => 'Discovery and Interaction - Anime Tracker',
    'help.group.series.heading'              => 'Series and Episode Info',
    'help.group.series.page_title'           => 'Series and Episode Info - Anime Tracker',
    'help.group.timezone.heading'            => 'Timezone',
    'help.group.timezone.page_title'         => 'Timezone - Anime Tracker',
    'help.intro'                             => 'Here is how Anime Tracker works, what each field is for, and what to watch out for. If you are curious about a specific feature, jump to the relevant section.',
    'help.contact'                           => 'Contact: <a href="mailto:at@animetracker.uzakdiyarlar.com">at@animetracker.uzakdiyarlar.com</a>',

    // Table of contents
    'help.toc.heading'                       => 'Contents:',
    'help.toc.fields'                        => 'Anime Fields — What Does Each One Do?',
    'help.toc.statuses'                      => 'Watch Statuses — Five Options',
    'help.toc.quick_buttons'                 => 'Quick Watch Buttons (+/-)',
    'help.toc.sync'                          => 'Catalog Sync — How Does It Work?',
    'help.toc.personal'                      => 'Personal Fields — Notes and Personal Synopsis',
    'help.toc.emotions'                      => 'Emotions — React to an Anime',
    'help.toc.filler'                        => 'Filler and Canon Episodes',
    'help.toc.statistics'                    => 'Statistics',
    'help.toc.title_lang'                    => 'Title Language (English / Romaji)',
    'help.toc.translation'                   => 'Translation status',
    'help.toc.recommendations'               => 'What Should I Watch? — Recommendation System',
    'help.toc.chronology'                    => 'Series and Chronology',
    'help.toc.deletion'                      => 'Deletion Warnings',
    'help.toc.updates'                       => 'Update System',
    'help.toc.timezone'                      => 'Timezone (TZ)',

    // Section: Anime fields (catalog vs personal)
    'help.fields.h2'                         => 'Anime Fields — What Does Each One Do?',
    'help.fields.intro'                      => 'The fields on the add/edit anime screens fall into two groups: <strong>catalog fields</strong> (come from the server, updated by sync) and <strong>personal fields</strong> (yours alone, never sent to the server).',
    'help.fields.catalog.h3'                 => '<i class="fas fa-cloud icon-inline"></i> Catalog Fields (synced)',
    'help.fields.catalog.list' => '<li><strong>Anime Title, Alternative Titles</strong></li>
        <li><strong>Synopsis</strong> — The official summary of the anime</li>
        <li><strong>Genres</strong> — Action, Comedy, etc.</li>
        <li><strong>Sentences (Tags)</strong> — For the "What Should I Watch?" system</li>
        <li><strong>Broadcast status, episode count, broadcast day/time</strong></li>
        <li><strong>Release Date / End Date</strong> — you can enter only the part you know; an unknown day or month shows as <code>??</code> (<code>??.04.1979</code>, <code>??.??.1979</code>, <code>??.??.????</code>)</li>
        <li><strong>Country</strong> — country of production; this is what the country filter on the list uses</li>
        <li><strong>MAL / AniDB / AnimeSchedule links</strong></li>
        <li><strong>Series info</strong> (series name, media type, next in series)</li>',
    'help.fields.catalog.note'               => 'If you edit these fields manually, the next sync will <strong>overwrite</strong> them (the server has the last word).',
    'help.fields.personal.h3'                => '<i class="fas fa-user icon-inline"></i> Personal Fields (not synced)',
    'help.fields.personal.list' => '<li><strong>Watched Episodes count</strong></li>
        <li><strong>Watch Status</strong> (Watched / Watching / Plan to Watch / On Hold / Dropped) — can change automatically via <a href="help_basics.php#hizli-butonlar">the <code>+/-</code> buttons in the list</a></li>
        <li><strong>Notes</strong> — Your personal reminders, comments</li>
        <li><strong>Personal Synopsis</strong> — Your own take / description</li>
        <li><strong>Start and finish dates</strong> — when you started an anime and when you finished it</li>
        <li><strong>Poster (if you uploaded one yourself)</strong></li>
        <li><strong>Next episode date</strong> (locally calculated)</li>',
    'help.fields.personal.note'              => 'The server <strong>never touches</strong> these. Write and edit them as much as you like.',

    // Section: Watch statuses
    'help.statuses.h2'                       => 'Watch Statuses',
    'help.statuses.intro'                    => 'Every anime has a <strong>Watch Status</strong>. Five options cover the different stages of watching, plus an initial state for anime you have not chosen yet:',
    'help.statuses.list' => '<li><strong>Plan to Watch</strong> — You haven\'t started yet but want to in the future. Watched episodes: 0.</li>
        <li><strong>Watching</strong> — You are actively watching. Watched episodes are somewhere between zero and the total.</li>
        <li><strong>Watched</strong> — Anime you have finished. Watched episodes = total episodes (or every episode of a series whose broadcast has ended).</li>
        <li><strong>On Hold</strong> — You started watching but took a break and want your progress preserved. <em>Difference from Plan to Watch:</em> Plan to Watch means "I have not started yet" (watched=0); On Hold means "I have watched some, currently taking a break" (watched&gt;0).</li>
        <li><strong>Dropped</strong> — You decided to stop watching this anime entirely and do not plan to return. Difference from On Hold: On Hold means "I will continue later", Dropped means "I am done, I will not continue".</li>
        <li><strong>Not Selected</strong> — You have not chosen a status for this anime yet. It still appears in your list but belongs to no watch group; the first <code>+</code> or an Edit assigns a status and moves it out of this initial state.</li>',
    'help.statuses.when_postponed'           => '<strong>When to use On Hold?</strong> If you set an anime aside for six months meaning to return to it later, set the status to On Hold. That keeps your active "Watching" list uncluttered without dropping the entry back to Plan to Watch (because you still have progress). When you are ready, hit <code>+</code> and the system pulls it back to Watching automatically.',

    // Section: Quick watch buttons (+/-)
    'help.buttons.h2'                        => 'Quick Watch Buttons (+/-)',
    'help.buttons.intro'                     => 'Each anime in the list has <code>+</code> and <code>-</code> buttons next to it, and so does the "Watched Episodes" row on the anime detail page. These let you bump the watched episode count up or down without opening the "Edit" screen. Under certain conditions the count change also <strong>updates the Watch Status automatically</strong>.',
    'help.buttons.transitions.h3'            => 'Automatic Status Transitions',
    'help.buttons.transitions.intro'         => 'The table below summarises the five main cases:',
    'help.buttons.transitions.col_current'   => 'Current status',
    'help.buttons.transitions.col_action'    => 'Action',
    'help.buttons.transitions.col_new'       => 'New status',
    'help.buttons.transitions.row1_curr'     => 'Plan to Watch + 0/12',
    'help.buttons.transitions.row1_new'      => 'Watching + 1/12',
    'help.buttons.transitions.row2_curr'     => 'Watching + 11/12',
    'help.buttons.transitions.row2_new'      => 'Watched + 12/12',
    'help.buttons.transitions.row3_curr'     => 'Watched + 12/12',
    'help.buttons.transitions.row3_new'      => 'Watching + 11/12',
    'help.buttons.transitions.row4_curr'     => 'Watching + 1/12',
    'help.buttons.transitions.row4_new'      => 'Plan to Watch + 0/12',
    'help.buttons.transitions.row5_curr'     => 'On Hold + 5/12',
    'help.buttons.transitions.row5_new'      => 'Watching + 6/12',
    'help.buttons.transitions.note'          => 'The logic is simple: the status changes automatically on boundary transitions (returning to zero, reaching the end); intermediate values are left alone. Note: the automatic jump to "Watched" in the table applies to anime with a known total or a finished broadcast; for still-airing anime with an unknown total, the rule below applies instead.',
    'help.buttons.two_step.h3'               => 'Two Steps with One Click',
    'help.buttons.two_step.intro'            => 'Sometimes a single <code>+</code> or <code>-</code> press triggers two transitions at once:',
    'help.buttons.two_step.list' => '<li><strong>Plan to Watch + 11/12 → <code>+</code> → Watched + 12/12.</strong> First Plan to Watch flips to Watching, then because it reached the ceiling, Watching flips to Watched - all in one click.</li>
        <li><strong>Watched + 1/12 → <code>-</code> → Plan to Watch + 0/12.</strong> Mirror of the above: first to Watching, then because the count hits 0, back to Plan to Watch in one click.</li>
        <li><strong>On Hold + 11/12 → <code>+</code> → Watched + 12/12.</strong> When you reach the last episode of an on-hold anime, the same logic applies: first to Watching, then because the count hits the ceiling, on to Watched in one click.</li>',
    'help.buttons.untouched.h3'              => 'When Does It NOT Trigger?',
    'help.buttons.untouched.box_title'       => '<i class="fas fa-info-circle"></i> Automation leaves it alone:',
    'help.buttons.untouched.list' => '<li><strong>Watching + intermediate value</strong> (e.g. 7/12) — pressing <code>+</code> or <code>-</code> keeps the status at Watching and just changes the count.</li>
            <li><strong>Plan to Watch + <code>-</code></strong> — pressing <code>-</code> while in Plan to Watch changes neither the count nor the status (already at 0).</li>
            <li><strong>Watched + below ceiling + <code>+</code></strong> — pressing <code>+</code> on a record that has been put into an abnormal state manually keeps the status at Watched; the automation does not force a correction, so your manual intent is preserved.</li>
            <li><strong>On Hold + <code>-</code></strong> — pressing <code>-</code> on an on-hold anime keeps the status as On Hold and just decreases the count by one. This is for rare cases like "I paused but had skipped an episode". When you want to continue, press <code>+</code> (the 5th rule above kicks in) or change the status manually via Edit.</li>',
    'help.buttons.unknown_count.h3'          => 'Anime with Unknown Episode Count',
    'help.buttons.unknown_count.intro'       => 'If the total or aired episode count is unknown (ceiling-less old OVAs, series with unclear schedule):',
    'help.buttons.unknown_count.list' => '<li><strong>Ceiling check cannot run</strong> — so the automatic transition to "Watched" via <code>+</code> does not work. You have to mark it manually via Edit.</li>
        <li><strong>The zero-floor check works independently of the ceiling</strong> — pressing <code>-</code> on Watching + 1/? still returns the status to Plan to Watch + 0/? automatically.</li>
        <li><strong>Pressing <code>-</code> on a ceiling-less anime that was manually marked Watched</strong> keeps the status as Watched — the system can\'t make a safe transition, so it leaves the manual state alone.</li>',
    'help.buttons.airing_unknown.h3'         => 'Still-Airing Anime with an Unknown Total',
    'help.buttons.airing_unknown.intro'      => 'If a series is still airing and its total episode count is not known yet (e.g. 11 episodes aired so far, the show continues), catching up to the latest aired episode does not count as "finished".',
    'help.buttons.airing_unknown.box_title'  => '<i class="fas fa-info-circle"></i> Caught up is not the same as watched:',
    'help.buttons.airing_unknown.box_body'   => 'Even if you reach the latest aired episode with <code>+</code> (e.g. 11/11), the status <strong>stays "Watching"</strong> and does not flip to "Watched" by mistake. When a new episode airs, it remains "Watching". The status only becomes "Watched" when the series truly ends: when the known total is reached, or when every episode of a finished-broadcast series has been watched. (This behaviour arrived in 1.0.21; on earlier versions a record stuck on "Watched" by mistake returns to "Watching" on its own after one <code>-</code> press.)',
    'help.buttons.manual.h3'                 => 'Manual Editing Is Always Available',
    'help.buttons.manual.text'               => 'Automatic status transitions only fire when you press the <code>+</code> and <code>-</code> buttons. You can <strong>always</strong> pick any status manually from the "Edit" form; the automation will not interfere.',

    // Section: Catalog sync
    'help.sync.h2'                           => 'Catalog Sync — How Does It Work?',
    'help.sync.intro'                        => 'When you press "Import from Catalog" on the List Settings page, the server-side catalog is merged into your local database.',
    'help.sync.safe_title'                   => '<i class="fas fa-shield-alt"></i> Will not be lost:',
    'help.sync.safe_body'                    => 'Your watch progress, notes, Personal Synopsis, and the poster you uploaded yourself — these are yours alone and the sync never touches them.',
    'help.sync.warning_title'                => '<i class="fas fa-exclamation-triangle"></i> Will be overwritten:',
    'help.sync.warning_body'                 => 'Catalog fields such as anime title, synopsis, genres, broadcast info are updated to the server\'s latest state on every sync. If you edited them by hand, those edits are lost.',
    'help.sync.own_added.h3'                 => 'What About Anime I Added Myself?',
    'help.sync.own_added.text'               => 'If anime you added have not been promoted to the catalog by the admin (i.e. they are still your private entries), the sync <strong>does not touch them at all</strong>. All their fields are preserved.',
    'help.sync.when.h3'                      => 'When Does Sync Run?',
    'help.sync.when.text'                    => 'Not automatic — only when you ask for it. Sync runs once each time you press List Settings → "Import from Catalog".',
    'help.sync.aired.h3'                      => 'Episode-Count Sync (aired episodes)',
    'help.sync.aired.text'                    => 'Separate from the catalog sync, the "how many episodes have aired" figure for still-airing anime is refreshed from AnimeSchedule. This runs by itself once a day in the background each time you open the List Settings page; you can also trigger it manually with "Sync Now".',
    'help.sync.aired.box_title'               => '<i class="fas fa-shield-alt"></i> Does not touch your personal state:',
    'help.sync.aired.box_body'                => 'This only updates the catalog\'s "aired episode count" field. It does not touch your watch status, your watched-episode count, or your notes. When a new episode airs, your watch status does not change on its own; you record progress yourself with <code>+</code>.',

    // Section: Personal fields (Notes + Personal Synopsis)
    'help.personal.h2'                       => 'Personal Fields — Notes and Personal Synopsis',
    'help.personal.intro'                    => 'You have two different personal text fields. Their differences:',
    'help.personal.table.col_field'          => 'Field',
    'help.personal.table.col_purpose'        => 'Purpose',
    'help.personal.table.col_example'        => 'Example',
    'help.personal.table.row_notes_field'    => 'Notes',
    'help.personal.table.row_notes_purpose'  => 'Short reminders',
    'help.personal.table.row_notes_example'  => '"Watch together with a friend", "Speed up after the first 3 episodes"',
    'help.personal.table.row_synopsis_field' => 'Personal Synopsis',
    'help.personal.table.row_synopsis_purpose' => 'Longer commentary, your own summary',
    'help.personal.table.row_synopsis_example' => 'Your own translation, your own commentary, your own summary',
    'help.personal.howto.h3'                 => '<i class="fas fa-sync icon-inline"></i> How Does Personal Synopsis Appear?',
    'help.personal.howto.intro'              => '<strong>Initially there is only one "Synopsis" field.</strong> Whatever you wrote or the server provided lives there. If new content arrives from the catalog and you had your own writing in that field, the first sync does the following:',
    'help.personal.howto.list' => '<li>Your text is automatically moved into the <strong>"Personal Synopsis"</strong> field</li>
        <li>The text from the server is written into the "Synopsis" field (it becomes read-only - you cannot edit it)</li>
        <li>From now on you will see both fields and anything you edit goes into "Personal Synopsis"</li>',
    'help.personal.warning_title'            => '<i class="fas fa-exclamation-triangle"></i> Heads up:',
    'help.personal.warning_body'             => 'If you delete the Personal Synopsis, <strong>sync will not bring it back</strong>. Likewise if you clear the Notes field, that does not come back either. These two fields are yours and permanently under your control.',

    // Section: Emotions
    'help.emotions.h2'                       => 'Emotions — React to an Anime',
    'help.emotions.intro'                    => 'On an anime\'s detail page you can mark how it made you feel. There are nine emotion options:',
    'help.emotions.list' => '<li><strong>Saddened</strong></li>
        <li><strong>Excited</strong></li>
        <li><strong>Bored</strong></li>
        <li><strong>Made Me Laugh</strong></li>
        <li><strong>Scared</strong></li>
        <li><strong>Thought-provoking</strong></li>
        <li><strong>Surprised</strong></li>
        <li><strong>Relaxing</strong></li>
        <li><strong>Motivating</strong></li>',
    'help.emotions.cap_title'                => '<i class="fas fa-info-circle"></i> Up to 3 per anime:',
    'help.emotions.cap_body'                 => 'An anime can have at most 3 emotions marked at once, so the marks stay meaningful. Pressing an emotion again removes it (toggle). Removing a mark is always allowed and is not subject to the 3-mark limit.',
    'help.emotions.stats'                    => 'Your emotion marks are personal and are summarised on the Statistics page as a "By Emotion" distribution — you can see there which emotion you mark the most.',

    // Section: Filler / canon episodes
    'help.filler.h2'                         => 'Filler and Canon Episodes',
    'help.filler.intro'                      => 'An anime\'s episodes can be classified by their relation to the source material. This helps people who wonder "which episodes can I skip". There are four types:',
    'help.filler.list' => '<li><strong>Manga Canon</strong> — Episodes based on the source manga, part of the main story.</li>
        <li><strong>Anime Canon</strong> — Episodes not in the manga but woven into the story by the production and treated as canon.</li>
        <li><strong>Mixed</strong> — The same episode contains both canon and filler parts.</li>
        <li><strong>Filler</strong> — Skippable filler episodes that do not affect the main story.</li>',
    'help.filler.unmarked'                   => 'An unmarked episode means "assume canon" — that is, if an episode has no type label, it is treated as part of the main story.',
    'help.filler.warning_title'              => '<i class="fas fa-exclamation-triangle"></i> Catalog data:',
    'help.filler.warning_body'               => 'Episode classification is kept by the catalog; on sync the server\'s version wins. So if you change it yourself, the next sync may overwrite it.',

    // Section: Statistics
    'help.stats.h2'                          => 'Statistics',
    'help.stats.intro'                       => 'The Statistics page gives you numbers that summarise your list, split across three tabs:',
    'help.stats.user.h3'                     => 'User Statistics',
    'help.stats.user.text'                   => 'Your personal summary: total anime, total episodes you have watched, total episodes; a breakdown by media type (TV / Movie / OVA, etc.), by broadcast status, by watch status (Watching / Watched / Plan to Watch / On Hold / Dropped / Not Selected), and a by-emotion distribution.',
    'help.stats.recent.h3'                   => 'Recently Watched',
    'help.stats.recent.text'                 => 'The anime you most recently logged progress on, newest at the top. Handy for checking "where was I".',
    'help.stats.global.h3'                   => 'Global Statistics',
    'help.stats.global.text'                 => 'Independent of your personal list, this shows the catalog\'s overall distribution (how many anime, which media types, etc.). It reflects the whole catalog, not your watch status.',

    // Section: Title language
    'help.title_lang.h2'                     => 'Title Language',
    'help.title_lang.intro'                  => 'Anime titles are shown in Romaji by default (e.g. "Shingeki no Kyojin"). Under List Settings → "Title Language" you can pick another language: English, Japanese, Turkish, Chinese, Korean or French. Choosing English, for example, gives you "Attack on Titan".',
    'help.title_lang.box_title'              => '<i class="fas fa-info-circle"></i> Independent of the interface language:',
    'help.title_lang.box_body'               => 'This preference is yours alone and works independently of the site language (Turkish/English) — you can use a Turkish interface while preferring Japanese titles. Anime with no title in the language you picked stay in Romaji, so choosing an empty language is harmless. A title\'s language is marked in the add/edit form, in the box next to each alternative title.',

    // Section: Recommendation system
    'help.translation.h2'                    => 'Translation Status',
    'help.translation.intro'                 => 'Anime descriptions on this site are originally written in Turkish by the site curator. English versions are produced by AI translation using external tools and pasted in manually. They are labelled "Auto-translated from Turkish" below the synopsis.',
    'help.translation.quality'               => 'Translation quality may vary, and the Turkish original is always the authoritative version. You can switch language at any time using the language selector.',
    'help.recom.h2'                          => 'What Should I Watch? — Recommendation System',
    'help.recom.intro'                       => 'The "What Should I Watch?" link in the menu is a tool designed to suggest anime from your list that match your mood.',
    'help.recom.howto.h3'                    => 'How Does It Work?',
    'help.recom.howto.text'                  => 'Curators (moderators and administrators) assign each anime a few <strong>sentence tags</strong>: "Set in school", "Has sports", "Has magic", etc. Pick whichever sentences you fancy and press "Recommend".',
    'help.recom.scoop.h3'                    => 'Scoop Logic',
    'help.recom.scoop.text'                  => 'Think of each selected sentence as a scoop. Each scoop pulls its matches from the list. If you pick multiple scoops, the anime that match the most scoops bubble to the top.',
    'help.recom.scoop.box_title'             => '<i class="fas fa-check"></i> Important:',
    'help.recom.scoop.box_body'              => 'Picking many sentences does not narrow the results — it sharpens the ranking. The system uses OR + scoring rather than AND.',
    'help.recom.surprise.h3'                 => 'Surprise Me',
    'help.recom.surprise.text'               => 'If you press "Surprise Me" without picking any sentence, the system picks a random anime you have not watched yet. A quick fix when you can\'t decide.',
    'help.recom.search.h3'                   => 'Search Box',
    'help.recom.search.text'                 => 'When the sentence list grows long you can type into the search box. Sentences that <strong>start with</strong> the letters you type appear as a filtered list. Turkish characters are distinguished — typing "u" matches words starting with "U"; typing "ü" matches words starting with "Ü".',

    // Section: Series and Chronology
    'help.chrono.h2'                         => 'Series and Chronology',
    'help.chrono.intro'                      => 'There are two relationship systems for related anime:',
    'help.chrono.series.h3'                  => 'Series Info',
    'help.chrono.series.text'                => 'Which series an anime belongs to is determined by its <strong>series name</strong> and <strong>media type</strong> (TV / Film / OVA / Special / ONA). Anime sharing the same series name appear under "Related Anime" on the anime detail page.',
    'help.chrono.next.h3'                    => 'Next in Series (next_in_series)',
    'help.chrono.next.text'                  => 'Which anime to watch after finishing the current one. Appears in the "Next Up" box on the detail page.',
    'help.chrono.markers.h3'                 => 'Chronology Markers',
    'help.chrono.markers.text'               => 'For series like Detective Conan: episode-level markers such as "after episode 54, watch the first movie" are stored. They appear as active alerts on the detail page and are listed as a timeline on a separate "Chronology" page.',
    'help.chrono.story.h3'                   => 'Release Order and Story Order',
    'help.chrono.story.text'                 => 'A marker can carry two insertion points: the <strong>release order</strong> point (where the content actually aired) and the <strong>story order</strong> point (where it is best watched). Example: the first Card Captor Sakura film aired after episode 46 but is recommended after episode 35. Leaving the story point empty means "same as release" - you only fill the second number for markers that diverge. A single button on the detail page and the chronology page cycles the view: release → story → both. You can pick the default in List Settings.',
    'help.chrono.warning_title'              => '<i class="fas fa-exclamation-triangle"></i> Heads up:',
    'help.chrono.warning_body'               => 'Chronology markers also follow the sync\'s catalog-is-authoritative rule. If you added markers yourself, they are lost after the next sync.',

    // Section: Deletion warnings
    'help.delete.h2'                         => 'Deletion Warnings',
    'help.delete.danger_title'               => '<i class="fas fa-trash-alt"></i> Irreversible deletions:',
    'help.delete.danger_list' => '<li>Clearing the <strong>Notes</strong> field → sync will not restore it</li>
            <li>Clearing the <strong>Personal Synopsis</strong> field → sync will not restore it</li>
            <li>Deleting an anime → permanent; everything including watch progress is gone</li>
            <li>Deleting a poster file → the catalog poster is re-downloaded during sync (but your own uploaded poster does not come back)</li>',
    'help.delete.safe_title'                 => '<i class="fas fa-undo"></i> Reversible (via sync):',
    'help.delete.safe_list' => '<li>Changing / clearing the Synopsis field → the catalog synopsis comes back on the next sync</li>
            <li>Changing the anime title → fixed by sync</li>
            <li>Changing the genre list / broadcast info → fixed by sync</li>',

    // Section: Update system
    'help.update.h2'                         => 'Update System',
    'help.update.intro'                      => 'Anime Tracker itself ships new releases from time to time. You can check whether a new version is available via List Settings → "Check for Updates". That section is only visible to the <strong>owner of a personal (single-user) install</strong>: on a multi-user site the operator updates from the source code, so a link to the project page stands there instead of a button.',
    'help.update.flow_intro'                 => 'If a new release is available, a one-click automatic update runs:',
    'help.update.flow_list' => '<li>The new release is downloaded from the server</li>
        <li>Files are updated in place (<code>config.php</code>, <code>uploads/</code> and your watch data are preserved)</li>
        <li>The database is auto-upgraded if needed</li>
        <li>The page reloads; the new version is active</li>',
    'help.update.safe_title'                 => '<i class="fas fa-shield-alt"></i> Not lost during update:',
    'help.update.safe_body'                  => 'Your anime entries, watch data, notes, posters, and DB credentials — none of these are affected.',

    // Section: Timezone
    'help.tz.h2'                             => 'Timezone — How Are Broadcast Times Shown?',
    'help.tz.intro'                          => 'Anime Tracker stores all dates and times in the database as <strong>UTC</strong>. When displaying them it converts to each anime\'s own broadcast timezone.',
    'help.tz.bc_tz.h3'                       => 'Broadcast Timezone (the anime\'s TZ)',
    'help.tz.bc_tz.text'                     => 'The "Broadcast Timezone" field on the add/edit anime form. The dropdown has 6 fixed options: Asia/Tokyo (JST), Europe/Istanbul (TRT), UTC, America/New_York (ET), America/Los_Angeles (PT), Europe/London. For most Japanese anime, <code>Asia/Tokyo</code> is the right choice.',
    'help.tz.autofill_title'                 => '<i class="fas fa-magic"></i> Quick path — Auto-fill:',
    'help.tz.autofill_body'                  => 'Enter the AnimeSchedule URL and press the "Auto-fill" button — <strong>the broadcast_day, broadcast_time and broadcast_timezone fields are populated automatically with Asia/Tokyo + Tokyo time</strong>. The AnimeSchedule API returns Japanese anime data correctly in Tokyo TZ. No manual entry needed.',
    'help.tz.workflows.h3'                   => 'Two Valid Workflows',
    'help.tz.workflows.intro'                => 'The TZ field and the time field should reflect the same timezone. Both approaches are valid:',
    'help.tz.workflows.list' => '<li><strong>The anime\'s broadcast region:</strong> Pick "JST" + enter the Tokyo time (e.g. 23:30). The anime detail page shows "23:30 (JST)"; you calculate your own local time. The AnimeSchedule Auto-fill uses this approach.</li>
        <li><strong>Your own local time:</strong> Pick "TRT" + enter the Turkish time in 24-hour format (e.g. 17:30). The anime detail page shows "17:30 (TRT)", directly readable. If you are reading manually from the AnimeSchedule site, the site already shows Turkish time; just convert am/pm to 24-hour and write it down.</li>',
    'help.tz.consistency'                    => 'Important: the TZ selection and time field must be <strong>consistent</strong>. If you pick "JST" but enter Turkish time, or "TRT" but enter Tokyo time, the "when is the next episode" calculation will be wrong.',
    'help.tz.box_animeschedule_title'        => '<i class="fas fa-info-circle"></i> What the AnimeSchedule site shows:',
    'help.tz.box_animeschedule_body'         => 'If you open the AnimeSchedule site in a browser it shows times <strong>in am/pm (12-hour) format</strong>, <strong>in your local TZ</strong> (visitors from Turkey see Turkish time, visitors from elsewhere see their own country\'s time). For manual entry: pick your local TZ on the form (TRT for Turkey), convert am/pm to 24-hour (e.g. "5:30 PM" -> 17:30), write it into the "Broadcast Time" field. The site does not show Tokyo TZ — Tokyo TZ data is only pulled directly from the AnimeSchedule API via the "Auto-fill" button.',
    'help.tz.box_dst_title'                  => '<i class="fas fa-info-circle"></i> Daylight Saving Time (DST):',
    'help.tz.box_dst_body'                   => 'If the anime\'s broadcast TZ observes DST (Europe, US), the broadcast time shifts by one hour twice a year (late March and late October). Asia/Tokyo does not observe DST, so Japanese anime times stay fixed year-round.',
    'help.tz.upgrade.h3'                     => 'Upgrading from Older v0.5 Installations',
    'help.tz.upgrade.text'                   => 'After upgrading to v0.5.1 none of your data is lost. Broadcast times look the same (entries that were added under the default Asia/Tokyo TZ are still Asia/Tokyo). The anime detail page shows a TZ label (JST, etc.) next to each broadcast time.',

    // Footer

    // =================================================================
    // 1.1.33 - four new help groups and three new sections
    // =================================================================

    // New groups (help.php table of contents + subpage headings)
    'help.group.list.heading'                => 'List, Search and Filters',
    'help.group.list.page_title'             => 'List, Search and Filters - Anime Tracker',
    'help.group.prefs.heading'               => 'Personal Preferences',
    'help.group.prefs.page_title'            => 'Personal Preferences - Anime Tracker',
    'help.group.transfer.heading'            => 'Moving and Importing Lists',
    'help.group.transfer.page_title'         => 'Moving and Importing Lists - Anime Tracker',
    'help.group.account.heading'             => 'Membership and Contributing',
    'help.group.account.page_title'          => 'Membership and Contributing - Anime Tracker',

    // New table-of-contents entries
    'help.toc.list_tabs'                     => 'Full List and My List',
    'help.toc.search'                        => 'Search',
    'help.toc.filters'                       => 'Filters and Sorting',
    'help.toc.recent'                        => 'Recently Updated',
    'help.toc.prefs'                         => 'Preferences — All in One Place',
    'help.toc.ui_lang'                       => 'Interface Language',
    'help.toc.adult'                         => 'Adult Content (18+)',
    'help.toc.export'                        => 'Export Your List (backup)',
    'help.toc.import'                        => 'Import a List',
    'help.toc.mal'                           => 'Import a MyAnimeList List',
    'help.toc.anilist'                       => 'Import an AniList List',
    'help.toc.clear'                         => 'Clear the List',
    'help.toc.membership'                    => 'Signing In, Registering, Account',
    'help.toc.roles'                         => 'Roles — Who Can Do What?',
    'help.toc.add_anime'                     => 'Adding an Anime and Approval',
    'help.toc.suggest'                       => 'Suggesting a Correction',
    'help.toc.series_timeline'               => 'The Series Chronology Page',
    'help.toc.spoiler'                       => 'Spoiler Guard',
    'help.toc.broadcast'                     => 'Broadcast Info and Countdown',

    // -----------------------------------------------------------------
    // help/help_list.php - the list page
    // -----------------------------------------------------------------
    'help.list.tabs.h2'                      => 'Full List and My List',
    'help.list.tabs.intro'                   => 'There are two tabs at the top of the main list page. Both draw the same table; what differs is which anime get in:',
    'help.list.tabs.list' => '<li><strong>Full List</strong> — every anime in the catalog, including the ones you have never touched. This is the tab for browsing.</li>
        <li><strong>My List</strong> — only the anime you have <strong>given a watch status</strong>. Entries left as "Not Selected" do not appear here; putting an anime "in your list" means assigning it a status.</li>',
    'help.list.tabs.pref'                    => 'Which tab opens by default is set in <strong>List Settings → General Settings → "Default List"</strong>. Clicking a tab does not change that default; it just switches for the current visit.',
    'help.list.tabs.box_title'               => '<i class="fas fa-info-circle"></i> Browsing without signing in:',
    'help.list.tabs.box_body'                => 'My List is built from personal watch data, and a signed-out visitor has none — so that tab does nothing and the Full List is always shown.',

    'help.list.search.h2'                    => 'Search',
    'help.list.search.text'                  => 'The <strong>"Search anime..."</strong> box above the list looks at both the <strong>anime title</strong> and its <strong>alternative titles</strong>. The match does not have to start the name: typing "titan" also finds "Attack on Titan". "Clear" removes the search.',
    'help.list.search.box_title'             => '<i class="fas fa-info-circle"></i> Search and the letter filter are not the same:',
    'help.list.search.box_body'              => 'The search box finds text <strong>anywhere</strong> in the name; "Filter by Letter" returns only names that <strong>begin</strong> with that letter.',

    'help.list.filters.h2'                   => 'Filters and Sorting',
    'help.list.filters.intro'                => 'The list offers six filters. They work together — each choice narrows the result further, none cancels another:',
    'help.list.filters.list' => '<li><strong>By Genre</strong> — genre labels such as Action or Comedy. Matched on the exact genre name.</li>
        <li><strong>By Watch Status</strong> — your own status (Watching, Watched, Plan to Watch, On Hold, Dropped, Not Selected).</li>
        <li><strong>By Broadcast Status</strong> — the anime\'s own state (Airing, Finished, Not Started, Cancelled).</li>
        <li><strong>By Letter</strong> — the first letter of the title.</li>
        <li><strong>By Year</strong> — the release year. If only the year of the date is known (<code>??.??.2005</code>) the entry still appears under that year.</li>
        <li><strong>By Country</strong> — country of production. Only countries actually recorded in the catalog are listed.</li>',
    'help.list.filters.combine'              => 'Filters and search survive pagination: moving to page two does not drop your choices.',
    'help.list.filters.emotion.h3'           => 'Emotion Filter',
    'help.list.filters.emotion.text'         => 'This one is not a dropdown on the list page; it turns on when you <strong>click an emotion badge on the Statistics page</strong>, and it returns the anime you marked with that emotion. A "Emotion filter: ..." line appears above the list, with a link next to it that clears the filter.',
    'help.list.filters.per_page.h3'          => 'Items Per Page',
    'help.list.filters.per_page.text'        => 'Choose how many anime a page shows: 10 (the default), 20, 30, 50, 100, or "All". On a large catalog "All" can make the page slow.',
    'help.list.filters.sort.h3'              => 'Sorting',
    'help.list.filters.sort.text'            => 'The small <code>↑</code> and <code>↓</code> arrows in the table headers change the sort order. Four columns can be sorted: <strong>Anime</strong> (title), <strong>Status</strong>, <strong>Watched Episodes</strong> and <strong>Next Episode</strong>. Status sorts alphabetically by the label in the current interface language.',

    'help.list.recent.h2'                    => 'Recently Updated',
    'help.list.recent.text'                  => 'The <strong>"Recently Updated"</strong> page in the menu shows the five anime most recently added to, or edited in, the catalog. It answers "what is new, what got refreshed".',
    'help.list.recent.box_title'             => '<i class="fas fa-info-circle"></i> Not to be confused with "Recently Watched":',
    'help.list.recent.box_body'              => 'The <strong>Recently Watched</strong> tab on the Statistics page shows <strong>your</strong> latest watching activity. <strong>Recently Updated</strong> shows the latest changes to the <strong>catalog</strong>; marking an episode does not move anything on it.',

    // -----------------------------------------------------------------
    // help/help_prefs.php - personal preferences
    // -----------------------------------------------------------------
    'help.prefs.h2'                          => 'Preferences — All in One Place',
    'help.prefs.intro'                       => 'Personal preferences live on the <strong>List Settings → General Settings</strong> tab. Every one of them affects <strong>only you</strong>: they change nothing for other users and never touch catalog data.',
    'help.prefs.list' => '<li><strong>Interface Language</strong> — the language of the site itself (Turkish / English).</li>
        <li><strong>Title Language</strong> — which language anime titles are written in. Details: the Title Language section on the "Fields and Personal Data" page.</li>
        <li><strong>Default List</strong> — whether the home page opens on the Full List or My List.</li>
        <li><strong>Chronology View</strong> — whether chronology markers are listed in release order, story order, or both.</li>
        <li><strong>Series Chronology View</strong> — which tab the series chronology page opens on (chain order / air date).</li>
        <li><strong>Spoiler Guard</strong> — whether the synopsis is hidden for anime whose earlier seasons you have not watched.</li>
        <li><strong>Adult Content</strong> — whether entries marked 18+ are shown.</li>',

    'help.prefs.ui_lang.h2'                  => 'Interface Language',
    'help.prefs.ui_lang.text'                => 'The site runs in Turkish and English. Change the language from the picker at the top right of any page, or from List Settings; the choice is saved to your account. The interface language <strong>does not change anime titles</strong> — that is a separate preference. If a synopsis does not exist in your language, the Turkish original is shown.',

    'help.prefs.adult.h2'                    => 'Adult Content (18+)',
    'help.prefs.adult.intro'                 => 'Some catalog entries, and some genre/sentence labels, are marked <strong>18+</strong>. This preference is <strong>off by default</strong>, and while it is off:',
    'help.prefs.adult.list' => '<li>18+ anime are <strong>not shown</strong> in lists, search, recommendations or statistics.</li>
        <li>If such an anime\'s detail page is opened, a neutral notice appears instead of the content.</li>
        <li>Genre and sentence labels marked 18+ drop out of the filter lists and off the detail page badges.</li>
        <li>Where a series or chronology link points at an 18+ entry, only its title is hidden — the link itself stays.</li>',
    'help.prefs.adult.box_title'             => '<i class="fas fa-info-circle"></i> Turning it on is up to you:',
    'help.prefs.adult.box_body'              => 'Tick <strong>"Show adult content"</strong> under List Settings → General Settings and those entries become visible to you. The preference is per person: a moderator who wants to see them turns on their own checkbox too.',

    'help.prefs.spoiler.h2'                  => 'Spoiler Guard',
    'help.prefs.spoiler.text'                => 'For later entries in a series, the synopsis is not shown directly while you have not watched the entries before it. The full rule is in the <a href="help_series.php#spoiler">Spoiler Guard</a> section on the "Series and Episode Info" page. To switch it off: List Settings → General Settings → <strong>"Hide the synopsis of seasons I have not watched"</strong>.',

    // -----------------------------------------------------------------
    // help/help_transfer.php - export / import
    // -----------------------------------------------------------------
    'help.transfer.intro'                    => 'The <strong>Import/Export</strong> tab in List Settings does four separate jobs: taking a backup, restoring one, moving a MyAnimeList list in, and moving an AniList list in.',
    'help.transfer.export.h2'                => 'Export Your List (backup)',
    'help.transfer.export.text'              => 'The <strong>"Export List"</strong> button downloads a single JSON file named <code>anime_list_YYYY-MM-DD.json</code>. It contains the anime records of this installation together with <strong>your</strong> data attached to them:',
    'help.transfer.export.list' => '<li>Watch status, watched episode count, start and finish dates</li>
        <li>Notes and Personal Synopsis</li>
        <li>Your emotion marks</li>
        <li>Genres and sentences (by name, not by id)</li>
        <li>Chronology markers (referring to the linked anime by its MAL/AniDB id)</li>',
    'help.transfer.export.box_title'         => '<i class="fas fa-shield-alt"></i> Why names, not ids:',
    'help.transfer.export.box_body'          => 'Genres, sentences and chronology targets are written with <strong>names and external ids</strong>. Record numbers differ in every installation; a backup carried by name can be restored into a different one.',

    'help.transfer.import.h2'                => 'Import a List',
    'help.transfer.import.text'              => 'This uploads a JSON file produced by the export above. If the file is not valid JSON nothing happens and you get an error message. What happens next depends on the kind of installation:',
    'help.transfer.import.online.h3'         => 'On a multi-user site',
    'help.transfer.import.online.text'       => 'Records in the file are <strong>matched</strong> against the catalog and the matches are added to your own list — the catalog itself is not touched. Records with no counterpart in the catalog become <strong>catalog suggestions</strong> in the moderator queue. You then get a summary: how many entries were added, how many suggestions were created, and how many had already been suggested.',
    'help.transfer.import.selfhost.h3'       => 'On a personal (single-user) install',
    'help.transfer.import.selfhost.text'     => 'The backup is restored directly: anime records, genres, sentences, your personal data and chronology markers are written from the file. This is available to the owner (administrator) of the installation.',

    'help.transfer.mal.h2'                   => 'Import a MyAnimeList List',
    'help.transfer.mal.intro'                => 'You can upload the export file you download from MyAnimeList (either the <code>.xml</code> or its compressed <code>.gz</code> form). The flow has three steps:',
    'help.transfer.mal.steps' => '<li><strong>Choose the file</strong> and press "Preview".</li>
        <li><strong>Read the preview:</strong> how many records were read, how many matched the catalog, how many are already in your list, and how many have no counterpart.</li>
        <li><strong>Press "Import"</strong> to confirm. Nothing is written before you confirm.</li>',
    'help.transfer.mal.box_title'            => '<i class="fas fa-info-circle"></i> The two options in the preview:',
    'help.transfer.mal.box_body'             => '<strong>Statuses to import:</strong> lets you take only the watch statuses you want (say, only "Watched"). <strong>"Overwrite entries already in my list":</strong> if left unticked, your existing entries are kept and only new ones are added. Records with no counterpart become catalog suggestions on a multi-user site, and are added directly on a personal install.',
    'help.transfer.mal.note'                 => 'A MyAnimeList file carries a single name per anime, so entries arriving through this route do not gain a second-language title.',

    'help.transfer.anilist.h2'               => 'Import an AniList List',
    'help.transfer.anilist.intro'            => 'Here you upload nothing: you type your <strong>AniList username</strong> and your public list is fetched from AniList. Again a preview comes first, and nothing is written before you confirm.',
    'help.transfer.anilist.modes.h3'         => 'Two Import Modes',
    'help.transfer.anilist.modes' => '<li><strong>Import the list with watch statuses</strong> — status, watched episodes, dates and notes are written into your list.</li>
        <li><strong>Import content only</strong> — no personal watch state is taken; only anime missing from the catalog are added as records/suggestions. Useful for growing the catalog.</li>',
    'help.transfer.anilist.box_title'        => '<i class="fas fa-info-circle"></i> Things to know:',
    'help.transfer.anilist.box_body'         => 'The list must be <strong>public</strong>; AniList does not serve private lists. If you hit AniList\'s rate limit, wait a few minutes and try again. Also, the <strong>number of distinct AniList accounts is limited</strong> — you can re-sync accounts you have already imported as often as you like, but you cannot keep adding new ones.',
    'help.transfer.anilist.overwrite'        => 'The "overwrite" option only applies to the "with watch statuses" mode; in "content only" mode it is ignored.',

    'help.transfer.clear.h2'                 => 'Clear the List',
    'help.transfer.clear.text'               => 'The <strong>Clear</strong> tab in List Settings deletes every anime record. The button is shown <strong>to administrators only</strong> and is really meant for personal installs: on a shared catalog it would wipe every user\'s data at once.',
    'help.transfer.clear.danger_title'       => '<i class="fas fa-exclamation-triangle"></i> Cannot be undone:',
    'help.transfer.clear.danger_body'        => 'Deleting takes everything attached to the anime with it (watch data, notes, emotions, chronology markers, filler records). The genre and sentence vocabulary survives, but its links to anime do not. Do not press this button without <strong>exporting a backup first</strong>.',

    // -----------------------------------------------------------------
    // help/help_account.php - membership and contributing
    // -----------------------------------------------------------------
    'help.account.intro'                     => 'This section is about a <strong>multi-user (online) installation</strong> — a site where several people keep their own lists. On a single-person install there is no sign-in at all, and nothing below appears there.',
    'help.account.membership.h2'             => 'Signing In, Registering, Account',
    'help.account.membership.intro'          => 'Browsing the catalog, reading anime details and searching need <strong>no account</strong>. Signing in is what unlocks your own list: watch statuses, notes, Personal Synopsis, emotion marks and personal preferences.',
    'help.account.register.h3'               => 'Creating an Account',
    'help.account.register.text'             => 'A site runs in one of two registration modes. In <strong>invite mode</strong> (the default) creating an account needs an <strong>invite code</strong>; with a code in hand you fill in the code, a username and a password on the "Register" page. In <strong>open mode</strong> no code is asked for. The username must be 3-32 characters (letters, digits, underscore) and the password at least 8; e-mail is optional.',
    'help.account.invite.h3'                 => 'Requesting an Invite',
    'help.account.invite.text'               => 'Without a code you can use the <strong>"Request an invite"</strong> link on the registration page and leave your e-mail with a short reason. If the request is accepted, a code is sent to you. When the quota is full, new requests may not be accepted.',
    'help.account.account.h3'                => 'The Account Page',
    'help.account.account.text'              => 'Once signed in, the <strong>"Account"</strong> link at the top right shows your username, e-mail and role; the same page is where you change your password (current password + the new one twice).',

    'help.account.roles.h2'                  => 'Roles — Who Can Do What?',
    'help.account.roles.intro'               => 'There are four levels. Each level can also do everything the one before it can:',
    'help.account.roles.list' => '<li><strong>Visitor (signed out)</strong> — browses the catalog, reads details, searches, suggests corrections.</li>
        <li><strong>Member</strong> — keeps a personal list (watch status, episodes, notes, Personal Synopsis, emotions), saves preferences, and submits an anime that is missing from the catalog.</li>
        <li><strong>Moderator</strong> — curates the catalog: reviews incoming additions and correction suggestions, manages the genre and sentence vocabulary, adds and removes chronology markers.</li>
        <li><strong>Administrator</strong> — on top of that, looks after user management, invites and site settings such as the registration mode.</li>',

    'help.account.add.h2'                    => 'Adding an Anime and Approval',
    'help.account.add.text'                  => 'To add an anime that is missing from the catalog you (once signed in) use the <strong>"Add New Anime"</strong> form. Your entry does not go straight into the catalog list: it lands in the <strong>approval queue</strong> and waits there until a moderator reviews it.',
    'help.account.add.box_title'             => '<i class="fas fa-clock"></i> Pending approvals:',
    'help.account.add.box_body'              => 'The <strong>"Pending approval"</strong> link above the main list shows the additions that are waiting, so you can see your own entry there. Once a moderator approves it, the anime becomes visible in the catalog and can be listed by everyone.',

    'help.account.suggest.h2'                => 'Suggesting a Correction',
    'help.account.suggest.text'              => 'At the bottom of an anime detail page the <strong>"Suggest a correction"</strong> box takes whatever you found wrong or missing (for example "release date is wrong", "a genre is missing") and sends it to the moderator queue. You <strong>do not need an account</strong> for this. To keep abuse down, the number of suggestions that can be sent in a short period is limited.',

    // -----------------------------------------------------------------
    // help/help_series.php - three sections added in 1.1.33
    // -----------------------------------------------------------------
    'help.st.h2'                             => 'The Series Chronology Page',
    'help.st.intro'                          => 'The <strong>"Series Chronology"</strong> button on the detail page of any anime in a series shows that whole series on a single timeline. The page opens with two tabs:',
    'help.st.tabs.list' => '<li><strong>Chain Order</strong> — the watch order built from the "next in series" links: first season, second season, sequel film... The chain is built by hand, so it is the order the curator recommends.</li>
        <li><strong>Air Date</strong> — <strong>every</strong> record sharing the same series name, by first air date. Entries never linked into a chain (stand-alone films, specials) show up here too.</li>',
    'help.st.tabs.text'                      => 'The two tabs read the same series with two different questions: "in what order should I watch this" and "when did it come out". Which tab the page opens on is set by List Settings → General Settings → <strong>"Series Chronology View"</strong>; clicking a tab does not change that default.',
    'help.st.chains.h3'                      => 'Other Chains',
    'help.st.chains.text'                    => 'One series name can hold more than one independent chain — the films may follow one order while the TV series follow a completely different one. In that case <strong>"Other Chain 1..N"</strong> tabs appear in the tab bar, numbered from the oldest chain. A single record linked to nothing does not count as a chain; those live on the "Air Date" tab.',

    'help.spoiler.h2'                        => 'Spoiler Guard',
    'help.spoiler.intro'                     => 'The synopsis of a second (or later) entry in a series usually describes how the previous one ended. So when <strong>any earlier entry in the chain is unwatched</strong>, the synopsis is not shown directly; it waits behind a <strong>"Let me read it anyway"</strong> button. Pressing it expands the synopsis in place.',
    'help.spoiler.rules.h3'                  => 'When Does It Appear, and When Not?',
    'help.spoiler.rules.list' => '<li>If you have watched <strong>every</strong> earlier entry in the chain, no button appears at all and the synopsis reads as usual.</li>
        <li>If an earlier entry is only <strong>half</strong> watched ("Watching"), the guard stays on — a summary of what happens after the point you stopped is a spoiler too.</li>
        <li>On an anime you have <strong>already started</strong> (watching, finished, on hold or dropped) no button appears: there is nothing left to spoil for you in its synopsis.</li>
        <li>Stand-alone records that belong to no chain never trigger the guard — there is no "earlier" to speak of.</li>',
    'help.spoiler.where'                     => 'The guard applies in two places: the <strong>Synopsis</strong> row on the anime detail page, and the teaser on the <strong>Recommendations → Surprise Me</strong> card. Your own <strong>Personal Synopsis</strong> is never hidden.',
    'help.spoiler.box_title'                 => '<i class="fas fa-info-circle"></i> If you want it off:',
    'help.spoiler.box_body'                  => 'Untick <strong>"Hide the synopsis of seasons I have not watched"</strong> under List Settings → General Settings, and no button appears anywhere from then on. The preference is per person. For signed-out visitors the guard is always on: with no personal watch data, no season counts as watched.',

    'help.broadcast.h2'                      => 'Broadcast Info and Countdown',
    'help.broadcast.intro'                   => 'For anime that are airing or have not started yet, the detail page shows the broadcast day, time and time zone. The <strong>date of the next episode</strong> is worked out from those.',
    'help.broadcast.countdown.h3'            => 'Countdown on an Unaired Anime',
    'help.broadcast.countdown.text'          => 'On an anime that has not started yet, if both the first air date and the broadcast time are known, the page shows <strong>how long is left until the first episode</strong>. That figure is never stored; it is recalculated against the current date every time you open the page.',
    'help.broadcast.partial'                 => 'If only the year or the month of the release date is known (<code>??.??.2027</code>) the countdown line is <strong>not printed at all</strong>: it would mean inventing an exact number for an unknown day.',
    'help.footer'                            => 'For further questions: more detailed technical information is on the project\'s <a href="https://github.com/hitsumo/animetracker" target="_blank" rel="noopener">GitHub page</a>.',

    // -----------------------------------------------------------------
    // statistics.php
    // -----------------------------------------------------------------
    'statistics.page_title'                  => 'Statistics - Anime Tracker',
    'statistics.heading'                     => 'Statistics',
    'statistics.tab.user'                    => 'User Statistics',
    'statistics.tab.global'                  => 'Global Statistics',
    'statistics.tab.recent_watched'          => 'Recently Watched',
    'statistics.label.total_anime'           => 'Total Anime',
    'statistics.label.total_watched'         => 'Total Watched Episodes',
    'statistics.label.total_episodes'        => 'Total Episodes',
    'statistics.section.by_media'            => 'By Media Type',
    'statistics.section.by_broadcast'        => 'By Broadcast Status',
    'statistics.section.by_watch'            => 'By Watch Status',
    'statistics.col.type'                    => 'Type',
    'statistics.col.status'                  => 'Status',
    'statistics.col.count'                   => 'Count',
    'statistics.col.last_watched'            => 'Last Watched',
    'statistics.value.unspecified'           => 'Unspecified',
    'statistics.section.by_emotion'          => 'By Emotion',
    'statistics.col.emotion'                 => 'Emotion',
    'statistics.emotion.summary'             => '%d marks across %d anime.',
    'statistics.emotion.empty'               => 'You have not marked any anime with emotions yet. Open an anime detail page and use the emotion buttons to add marks.',
    'statistics.emotion.empty_global'        => 'No emotion marks have been added to any anime yet.',
    // 1.1.5: tooltip on the clickable personal emotion badge
    'statistics.emotion.filter_hint'         => 'List anime you marked with this emotion',
    'statistics.recent_watched.empty'        => 'No watch activity yet. Once you watch an episode of an anime it will show up here.',

    // -----------------------------------------------------------------
    // recent.php - son duzenlenen 5 anime
    // -----------------------------------------------------------------
    'recent.page_title'                      => 'Recently Updated - Anime Tracker',
    'recent.heading'                         => 'Recently Updated',
    'recent.back_to_list'                    => 'Back to List',
    'recent.empty_state'                     => 'No anime added yet.',
    'recent.time.now'                        => 'Just now',
    'recent.time.minutes_ago'                => '%d min ago',
    'recent.time.hours_ago'                  => '%d hour(s) ago',
    'recent.time.days_ago'                   => '%d day(s) ago',

    // -----------------------------------------------------------------
    // recommendations.php - 'Ne Izlesem?' oneri sayfasi
    // -----------------------------------------------------------------
    'recommendations.page_title'             => 'What Should I Watch? - Anime Tracker',
    'recommendations.heading'                => 'What Should I Watch?',
    'recommendations.surprise.heading'       => 'Try this today:',
    'recommendations.surprise.try_another'   => 'Try Another',
    'recommendations.surprise.choose_sentences' => 'Pick by Sentences',
    'recommendations.intro'                  => 'Pick the sentences that fit your mood and press <strong>Recommend</strong>. Selecting many sentences does not narrow the results — each sentence is a scoop that pulls its own matches; the anime that fall into the most scoops bubble to the top.',
    'recommendations.no_tags_empty'          => 'No sentences defined yet. First add a few via the <a href="manage_tags.php">manage sentences</a> page, then use the <a href="add_anime.php">add anime</a> or edit screen to assign them to anime.',
    'recommendations.search.placeholder'     => 'Search sentence (filters as you type)...',
    'recommendations.toggle.show'            => 'Show Sentences',
    'recommendations.toggle.hide'            => 'Hide Sentences',
    'recommendations.toggle.count_selected'  => '(%d selected)',
    'recommendations.search.empty_state'     => 'No sentence starts with this text.',
    'recommendations.btn.recommend'          => 'Recommend',
    'recommendations.btn.surprise'           => 'Surprise Me',
    'recommendations.btn.clear'              => 'Clear',
    'recommendations.no_match'               => 'No anime matches the sentences you picked. None of the anime may have these sentences assigned yet — remember to add sentences via the anime edit screen.',
    'recommendations.result.count'           => '<strong>%d</strong> anime found (%d sentences selected).',
    'recommendations.group.matched'          => '%d / %d sentences matched',
    'recommendations.group.count_suffix'     => '(%d anime)',

    // 0.6.5 - emotion filter integration (KARARLAR Bolum 8 devir borc kapanisi).
    // tag (cumle) + emotion bucket'lari paralel calisir, OR mantigi:
    // score = tag_score + emo_score. Eski tag-only anahtarlar bozulmaz;
    // emotion seciliyse alttaki _combined varyantlar devreye girer.
    'recommendations.emotion.toggle.show'           => 'Show Emotions',
    'recommendations.emotion.toggle.hide'           => 'Hide Emotions',
    'recommendations.emotion.toggle.count_selected' => '(%d emotion(s) selected)',
    'recommendations.emotion.empty_marks'           => 'You have not marked any anime with emotions yet. Open an anime detail page to add marks via the emotion buttons.',
    'recommendations.matched.emotion_prefix'        => 'Matched emotions:',
    'recommendations.no_match_combined'             => 'No anime matched the selected sentences and emotions. Try fewer criteria and submit again.',
    'recommendations.result.count_combined'         => '<strong>%d</strong> anime found (%d sentence(s), %d emotion(s) selected).',
    'recommendations.group.matched_combined'        => '%d criteria matched',

    // -----------------------------------------------------------------
    // about.php
    // -----------------------------------------------------------------
    'about.page_title'                       => 'About - Anime Tracker',
    'about.description'                      => 'Anime Tracker is an anime list and broadcast-tracking system built with the help of AI tools.',
    'about.ai_notice_link'                   => 'AI Use Notice',
    'about.back_to_list'                     => 'Back to Anime List',
    'about.version_fmt'                      => 'Version %s',

    // -----------------------------------------------------------------
    // chronology.php - per-anime kronoloji isaretleri timeline
    // -----------------------------------------------------------------
    'chronology.title_suffix'                => 'Chronology',
    'chronology.subtitle'                    => 'Chronological Watch Order',
    'chronology.status.watched'              => 'Watched',
    'chronology.status.watching'             => 'Watching',
    'chronology.status.upcoming'             => 'Up Next',
    'chronology.episode.range.watching'      => 'Watching (%s/%s)',
    'chronology.episode.range.single'        => 'Episode %d',
    'chronology.episode.range.multi'         => 'Episodes %d - %d',
    'chronology.episode.progress'            => '%d / %s episodes watched',
    'chronology.back_to_details'             => 'Back to Details',

    // -----------------------------------------------------------------
    // series_timeline.php - seri zincir kronolojisi
    // -----------------------------------------------------------------
    'series_timeline.title_suffix'           => 'Series Chronology',
    'series_timeline.subtitle'               => 'Series Chronology',
    'series_timeline.count'                  => '%d anime',
    'series_timeline.back_to_details'        => 'Back to Details',
    // 1.1.23 - tabs + air-date view
    'series_timeline.tab.chain'              => 'Chain Order',
    'series_timeline.tab.airdate'            => 'Air Date',
    'series_timeline.tab.other_chain'        => 'Other Chain %d',
    'series_timeline.no_date'                => 'no date',

    // -----------------------------------------------------------------
    // list_settings.php - import/export/clear/sync/update
    // -----------------------------------------------------------------
    'list_settings.page_title'               => 'List Settings - Anime Tracker',
    'list_settings.heading'                  => 'List Settings',
    // 1.1.13 - List Settings tab labels
    'list_settings.tab.import_export'        => 'Import/Export',
    'list_settings.tab.general_settings'     => 'General Settings',
    'list_settings.tab.management'           => 'Management Settings',
    'list_settings.tab.clear'                => 'Cleanup',
    'list_settings.csrf.invalid'             => 'Invalid CSRF token. Please refresh the page and try again.',
    'list_settings.version.unknown'          => 'unknown',
    'list_settings.aired.cancelled_prefix'   => 'Sync cancelled:',
    'list_settings.aired.no_api_key'         => 'AnimeSchedule API key is not defined in config.php.',
    'list_settings.aired.rate_limit'         => 'API request limit exceeded. Try again in a few minutes.',
    'list_settings.aired.invalid_key'        => 'API key is invalid. Check config.php.',
    'list_settings.aired.result.updated'     => '%d anime updated',
    'list_settings.aired.result.unchanged'   => '%d unchanged',
    'list_settings.aired.result.started'     => '%d started airing',
    'list_settings.aired.result.finished'    => '%d finished',
    'list_settings.aired.result.not_in_table' => '%d not found in schedule',
    'list_settings.aired.result.no_slug'     => '%d without AnimeSchedule URL',
    'list_settings.aired.result.errors'      => '%d errors',
    'list_settings.import.result'            => '%d anime imported, %d skipped.',
    'list_settings.import.markers'           => '%d chronology note linked, %d skipped.',
    'list_settings.import.invalid_format'    => 'Please upload a valid JSON file!',
    'list_settings.import.online_result'     => 'Import complete: %d anime added to your list, %d new catalog requests created, %d were already suggested.',
    'list_settings.import.upload_error'      => 'File upload failed (error code: %d). Please try again.',
    'list_settings.import.read_failed'       => 'The uploaded file could not be read. The file size or server settings may be blocking it.',
    'list_settings.clear.success'            => 'List cleared successfully!',
    'list_settings.section.export'           => 'Export List',
    'list_settings.section.export.desc'      => 'Export your current anime list in JSON format.',
    'list_settings.btn.export'               => 'Export List',
    'list_settings.section.import'           => 'Import List',
    'list_settings.section.import.desc'      => 'Import a previously exported list.',
    'list_settings.btn.choose_file'          => 'Choose File',
    'list_settings.btn.import'               => 'Import List',

    // MAL list import (1.1.1)
    'list_settings.section.mal_import'       => 'Import MyAnimeList List',
    'list_settings.section.mal_import.desc'  => 'Upload your MyAnimeList export file (XML or .gz). A preview is shown first; nothing is saved until you confirm.',
    'list_settings.mal.btn.choose_file'      => 'Choose MAL File',
    'list_settings.mal.btn.preview'          => 'Preview',
    'list_settings.mal.btn.commit'           => 'Import',
    'list_settings.mal.btn.cancel'           => 'Cancel',
    'list_settings.mal.err.upload'           => 'File upload failed (error code %d).',
    'list_settings.mal.err.read'             => 'Could not read the file.',
    'list_settings.mal.err.parse'            => 'Could not parse the MAL file. Please choose a valid MyAnimeList export file.',
    'list_settings.mal.err.empty'            => 'No anime to import were found in the file.',
    'list_settings.mal.err.session'          => 'The preview has expired. Please upload the file again.',
    'list_settings.mal.preview.summary'      => 'Read %d entries: %d matched in the catalog, %d already in your list, %d not in the catalog.',
    'list_settings.mal.preview.status_filter' => 'Statuses to import:',
    'list_settings.mal.preview.overwrite'    => 'Overwrite entries already in my list',
    'list_settings.mal.preview.unmatched_note.online'   => 'Entries not in the catalog will be sent as catalog suggestions.',
    'list_settings.mal.preview.unmatched_note.selfhost' => 'Entries not in the catalog will be added locally.',
    'list_settings.mal.result'               => 'Import complete: %d written, %d skipped (already in list), %d suggested/added.',
    // 1.1.35 - admin blacklist (import_blacklist). Both the MAL and the
    // AniList import use the same two keys, hence the shared 'import.'
    // prefix rather than 'mal.' / 'anilist.'. Multi-user mode only; these
    // strings are never printed on a self-host install.
    'list_settings.import.blocked_preview'   => '%d of these are on the admin blacklist and will be skipped.',
    'list_settings.import.blocked_result'    => '%d animes were skipped because they are on the admin blacklist.',
    'list_settings.section.anilist_import'      => 'Import AniList List',
    'list_settings.section.anilist_import.desc' => 'Enter your AniList username; your public anime list is fetched from AniList. A preview is shown first; nothing is saved until you confirm. (Your list must be public.)',
    'list_settings.anilist.username_label'      => 'AniList username',
    'list_settings.anilist.username_placeholder' => 'e.g. username',
    'list_settings.anilist.btn.preview'         => 'Preview',
    'list_settings.anilist.btn.commit'          => 'Import',
    'list_settings.anilist.btn.cancel'          => 'Cancel',
    'list_settings.anilist.err.bad_username'    => 'Invalid username. Use only letters, digits, underscore and hyphen.',
    'list_settings.anilist.err.network'         => 'Could not reach AniList. Check your internet connection and try again.',
    'list_settings.anilist.err.rate_limit'      => 'AniList rate limit reached. Please try again in a few minutes.',
    'list_settings.anilist.err.notfound'        => 'AniList user not found. Please check the username.',
    'list_settings.anilist.err.http'            => 'Unexpected response from AniList. Please try again later.',
    'list_settings.anilist.err.parse'           => 'Could not parse the AniList response. Please try again later.',
    'list_settings.anilist.err.empty'           => 'No public anime list to import was found for this user.',
    'list_settings.anilist.err.session'         => 'The preview has expired. Please enter the username again.',
    'list_settings.anilist.err.source_limit'    => 'You can import from at most %d different AniList accounts. Accounts you have already imported can be re-synced without limit; for a new account, ask an administrator to reset it.',
    'list_settings.anilist.preview.summary'     => 'Read %d entries: %d matched in the catalog, %d already in your list, %d not in the catalog.',
    'list_settings.anilist.preview.mode'        => 'Import type:',
    'list_settings.anilist.preview.mode.list'   => 'Import the list with watch state (status, episodes, dates, notes)',
    'list_settings.anilist.preview.mode.content' => 'Import content only (no personal watch state)',
    'list_settings.anilist.preview.status_filter' => 'Statuses to import:',
    'list_settings.anilist.preview.overwrite'   => 'Overwrite entries already in my list',
    'list_settings.anilist.preview.overwrite_hint' => 'Applies only to the "with watch state" type; ignored for "content only".',
    'list_settings.anilist.preview.unmatched_note.online'   => 'Entries not in the catalog will be sent as catalog suggestions.',
    'list_settings.anilist.preview.unmatched_note.selfhost' => 'Entries not in the catalog will be added locally.',
    'list_settings.anilist.result'              => 'Import complete: %d written, %d skipped (already in list), %d suggested/added.',
    'list_settings.anilist.result_content'      => 'Content import complete: %d new catalog entries added/suggested, %d already in the catalog.',

    'list_settings.section.clear'            => 'Clear List',
    'list_settings.section.clear.desc'       => 'WARNING: This action cannot be undone!',
    'list_settings.btn.clear'                => 'Clear List',
    'list_settings.section.language'         => 'Interface Language',
    'list_settings.section.language.desc'    => 'The language of menus, labels and buttons. This preference is independent of the title language.',
    'list_settings.language.option_tr'       => 'Türkçe',
    'list_settings.language.option_en'       => 'English',
    'list_settings.language.save'            => 'Save',

    'list_settings.section.title_lang'       => 'Title Language',
    'list_settings.section.title_lang.desc'  => 'Choose which language you want anime titles shown in. Anime with no title in that language stay in Romaji. This preference is independent of the interface language.',
    'list_settings.title_lang.option_romaji' => 'Romaji (default)',
    'list_settings.title_lang.save'          => 'Save',

    // 1.1.13 - default list tab preference (General / Personal)
    'list_settings.section.list_view'        => 'Default List',
    'list_settings.section.list_view.desc'   => 'Chooses which tab is selected when the anime list page opens. General List shows the whole catalog; Personal List shows only the anime you have set a watch status on. This preference affects only you.',
    'list_settings.list_view.option_all'     => 'General List',
    'list_settings.list_view.option_personal' => 'Personal List',
    'list_settings.list_view.save'           => 'Save',

    // 1.1.15 - chronology display mode default (list_settings)
    'list_settings.section.chrono_mode'      => 'Chronology View',
    'list_settings.section.chrono_mode.desc' => 'Chooses the order the chronology notes on the detail page and the chronology page open in: release order, story (recommended-watch) order, or both. You can switch it temporarily with the button on the detail page. This preference affects only you.',
    'list_settings.chrono_mode.save'         => 'Save',
    // 1.1.23 - series timeline default view
    'list_settings.section.st_mode'          => 'Series Chronology View',
    'list_settings.section.st_mode.desc'     => 'Chooses which tab the series chronology page opens in: chain order (follows the Next in Series links) or air date (lists every anime sharing the series name by first air/release date). You can switch temporarily with the tabs on the page. This preference affects only you.',
    'list_settings.st_mode.save'             => 'Save',

    // 1.1.2 - adult (18+) content visibility toggle (list_settings)
    // 1.1.33 - synopsis spoiler guard (user_pref 'spoiler_guard').
    'list_settings.section.spoiler'          => 'Spoiler Guard',
    'list_settings.section.spoiler.desc'     => 'On by default. While on, the synopsis of a later entry in a series is not shown directly if you have not watched the entries that come before it; it waits behind a "let me read it" button. This preference affects only you.',
    'list_settings.spoiler.checkbox'         => 'Hide the synopsis of seasons I have not watched',
    'list_settings.spoiler.save'             => 'Save',

    'list_settings.section.adult'            => 'Adult Content',
    'list_settings.section.adult.desc'       => 'Off by default. When on, anime marked 18+ appear in lists, search, recommendations and statistics. This preference affects only you.',
    'list_settings.adult.checkbox'           => 'Show adult content',
    'list_settings.adult.save'               => 'Save',
    'list_settings.section.genres'           => 'Genre Management',
    'list_settings.section.genres.desc'      => 'Manage misspelled or unused genres.',
    'list_settings.btn.manage_genres'        => 'Manage Genres',
    'list_settings.section.tags'             => 'Sentence Management',
    'list_settings.section.tags.desc'        => 'Manage misspelled or unused sentences.',
    'list_settings.btn.manage_tags'          => 'Manage Sentences',
    'list_settings.section.catalog'          => 'Catalog Sync',
    'list_settings.section.catalog.desc'     => 'Pull the latest anime info from the central catalog. Your watch progress and notes are preserved.',
    'list_settings.catalog.last_sync_prefix' => 'Last sync:',
    'list_settings.catalog.never_synced'     => 'Not synced yet.',
    'list_settings.catalog.unpushed_warning' => 'There are <strong>%d</strong> chronology markers not yet pushed to the catalog. Import <strong>will not delete</strong> them &mdash; your own additions are preserved and catalog entries are matched automatically. To keep the universal chronology complete, consider pushing them to the catalog via admin push.',
    'list_settings.btn.catalog_import'       => 'Import from Catalog',
    'list_settings.section.aired'            => 'Episode Count Sync',
    'list_settings.section.aired.desc'       => 'The "aired episodes" count for ongoing anime is updated automatically from AnimeSchedule. This page runs the sync once per day in the background when opened; use the button below to run it manually.',
    'list_settings.btn.sync_now'             => 'Sync Now',
    'list_settings.section.update'           => 'Update Check',
    'list_settings.section.update.desc'      => 'Check for a new version.',
    'list_settings.update.current_version'   => 'Current version:',
    'list_settings.update.online_note'       => 'A multi-user (online) install is updated from the source repository (git / Docker), not via an in-app button.',
    'list_settings.update.github_link'       => 'GitHub repository',
    'list_settings.btn.check_update'         => 'Check for Update',
    'list_settings.back_to_list'             => 'Back to Anime List',
    'list_settings.js.confirm_clear'         => 'The entire list will be deleted. This cannot be undone! Continue?',
    'list_settings.js.confirm_sync_intro'    => 'About to import from the catalog.',
    'list_settings.js.confirm_sync_safe'     => 'Your watch progress and notes are PRESERVED.',
    'list_settings.js.confirm_sync_overwrite' => 'Only anime info (title, synopsis, episode count, etc.) is updated.',
    'list_settings.js.confirm_sync_unpushed' => 'NOTE: There are %d chronology markers not synced to the catalog. Import WILL NOT delete them. Your own additions are preserved and catalog entries are matched automatically.',
    'list_settings.js.confirm_continue'      => 'Do you want to continue?',
    'list_settings.js.checking'              => 'Checking...',
    'list_settings.js.update_error'          => 'An error occurred during update check.',
    'list_settings.js.up_to_date_suffix'     => '(up to date)',
    'list_settings.js.new_version_label'     => 'New version:',
    'list_settings.js.confirm_install' => 'New version available: %s

Update now?',
    'list_settings.js.network_error'         => 'An error occurred during update check:',
    'list_settings.js.installing'            => 'Downloading and applying update...',
    'list_settings.js.installing_note'       => 'This may take a few seconds. Do not close the page.',
    'list_settings.js.install_failed'        => 'Update failed',
    'list_settings.js.install_failed_alert'  => 'Update failed:',
    'list_settings.js.unknown_error'         => 'Unknown error',
    'list_settings.js.install_success'       => 'Update complete!',
    'list_settings.js.install_previous'      => 'Previous version:',
    'list_settings.js.install_new'           => 'New version:',
    'list_settings.js.reloading'             => 'Page reloading...',
    'list_settings.js.install_network_error' => 'Network error',
    'list_settings.js.install_network_error_alert' => 'An error occurred during update:',

    // -----------------------------------------------------------------
    // manage_tags.php - sentence (tag) library management
    // -----------------------------------------------------------------
    'manage_tags.title'                      => 'Sentence Management',
    'manage_tags.intro'                      => 'Sentences are shown to users in the recommendation system. They are created automatically when you type a new sentence on the add/edit anime screen. From here you can fix typos or delete unnecessary sentences. Write the sentence exactly as the user will see it (e.g. "Set in a school", "Sports theme").',
    'manage_tags.placeholder'                => 'New sentence (e.g. Set in a school, Sports theme)',
    'manage_tags.ph.name_en'                 => 'English equivalent',
    'manage_tags.btn.add'                    => 'Add',
    'manage_tags.th.tag'                     => 'Sentence',
    'manage_tags.th.usage'                   => 'Usage',
    'manage_tags.th.rename'                  => 'Rename',
    'manage_tags.adult.label'                => '18+',
    'manage_tags.th.delete'                  => 'Delete',
    'manage_tags.usage_suffix'               => 'anime',
    'manage_tags.empty'                      => 'No sentences yet. You can add your first sentence using the form above.',
    'manage_tags.btn.delete'                 => 'Delete',
    'manage_tags.confirm_delete'             => 'Are you sure you want to delete the sentence "%s"? It will be removed from %d anime.',
    'manage_tags.back_to_list'               => 'Back to Anime List',
    'manage_tags.csrf.invalid'               => 'Invalid CSRF token. Please refresh the page and try again.',
    'manage_tags.err.empty'                  => 'Sentence cannot be empty.',
    'manage_tags.msg.added'                  => 'Sentence added (or already existed): %s',
    'manage_tags.err.rename_missing'         => 'Missing info: sentence ID or new text is empty.',
    'manage_tags.msg.renamed'                => 'Sentence updated.',
    'manage_tags.err.invalid_id'             => 'Invalid sentence ID.',
    'manage_tags.msg.deleted'                => 'Sentence deleted.',
    'manage_tags.err.unknown_action'         => 'Unknown action.',
    'manage_tags.err.duplicate'              => 'This sentence already exists.',
    'manage_tags.err.db'                     => 'A database error occurred.',

    // -----------------------------------------------------------------
    // manage_genres.php - master genre list management
    // -----------------------------------------------------------------
    'manage_genres.title'                    => 'Genre Management',
    'manage_genres.th.name'                  => 'Genre Name',
    'manage_genres.th.name_en'               => 'English Name',
    'manage_genres.ph.name_en'               => 'English name',
    'manage_genres.btn.save_en'              => 'Save',
    'manage_genres.adult.label'              => '18+',
    'manage_genres.th.action'                => 'Action',
    'manage_genres.confirm_delete'           => 'Are you sure you want to delete this genre? It will also be removed from any anime using it.',
    'manage_genres.btn.delete'               => 'Delete',
    'manage_genres.back_to_list'             => 'Back to Anime List',
    'manage_genres.csrf.invalid'             => 'Invalid CSRF token. Please refresh the page and try again.',


    // -----------------------------------------------------------------
    // filler_edit.php - per-episode filler / canon grid editor (0.7)
    // Type labels come from functions.php filler_type_label(), not here.
    // -----------------------------------------------------------------
    'filler.title_suffix'      => 'Filler Episodes',
    'filler.subtitle'          => 'Per-episode filler / canon marking',
    'filler.instructions'      => 'Click an episode to change its type: unmarked → Manga Canon → Anime Canon → Mixed → Filler. You only need to mark the exceptions; unmarked episodes are treated as canon.',
    'filler.type.unmarked'     => 'Unmarked',
    'filler.guard.no_count'    => 'No episode count is set for this anime. Enter a total or aired episode count first to build the grid.',
    'filler.guard.set_count'   => 'Edit episode count',
    'filler.save'              => 'Save',
    'filler.back_to_details'   => 'Back to details',
    'filler.js.saving'         => 'Saving...',
    'filler.js.saved'          => 'Saved.',
    'filler.js.save_error'     => 'Save failed. Please try again.',
    'filler.js.marked_count'   => '%d episode(s) marked',

    // anime_details.php - filler summary row (0.7)
    'anime_details.label.filler'    => 'Episode details:',
    'anime_details.btn.filler_edit' => 'Edit',
    'anime_details.filler_empty'    => 'No episodes marked yet.',

    // add_anime.php / edit_anime.php - filler tracking toggle (0.7)
    'add_anime.label.filler_tracking'     => 'Filler episode tracking:',
    'add_anime.hint.filler_tracking'  => 'Enables per-episode filler/canon marking (you can toggle this later too).',

    // add_anime.php / edit_anime.php - adult (18+) content flag (1.1.2)
    'add_anime.label.is_adult'     => '18+ / Adult content:',
    'add_anime.hint.is_adult'  => 'When checked, this anime is hidden by default; turn on "Show adult content" in List Settings to see it.',


    // Filler import (AnimeFillerList) - 0.7
    'filler.import.placeholder'  => 'AnimeFillerList show URL (e.g. .../shows/detective-conan)',
    'filler.import.button'       => 'Import from AnimeFillerList',
    'filler.js.importing'        => 'Importing...',
    'filler.js.imported_count'   => '%d episodes loaded.',
    'filler.js.import_skipped'   => '%d episodes were beyond the episode count (skipped).',
    'filler.js.import_review'    => 'Review and click Save.',
    'filler.js.import_need_url'  => 'Please enter an AnimeFillerList URL.',
    'filler.js.import_error'     => 'Import failed.',

    // =====================================================================
    // Auth (Faz 2 / Milestone 2). Login, logout, account pages + nav links.
    // =====================================================================
    'nav.login'   => 'Sign In',
    'nav.logout'  => 'Sign Out',
    'nav.account' => 'Account',
    'nav.register' => 'Register',

    'auth.login.page_title' => 'Sign In',
    'auth.login.heading'    => 'Sign In',
    'auth.login.username'   => 'Username',
    'auth.login.password'   => 'Password',
    'auth.login.submit'     => 'Sign in',
    'auth.login.error'      => 'Invalid username or password.',
    'auth.login.empty'      => 'Username and password are required.',

    'auth.logout.page_title' => 'Sign Out',
    'auth.logout.heading'    => 'Sign Out',
    'auth.logout.confirm'    => 'Do you want to sign out?',
    'auth.logout.submit'     => 'Sign out',

    'auth.account.page_title'           => 'Account',
    'auth.account.heading'              => 'Account Settings',
    'auth.account.username_label'       => 'Username',
    'auth.account.email_label'          => 'Email',
    'auth.account.role_label'           => 'Role',
    'auth.account.email_empty'          => '(not set)',
    'auth.account.change_password'      => 'Change Password',
    'auth.account.current_password'     => 'Current password',
    'auth.account.new_password'         => 'New password',
    'auth.account.new_password_confirm' => 'New password (confirm)',
    'auth.account.submit'               => 'Update password',
    'auth.account.success'              => 'Password updated.',
    'auth.account.err_empty'            => 'All fields are required.',
    'auth.account.err_current'          => 'Current password is incorrect.',
    'auth.account.err_short'            => 'New password must be at least 8 characters.',
    'auth.account.err_mismatch'         => 'New passwords do not match.',

    'auth.register.page_title'          => 'Register',
    'auth.register.heading'             => 'Create Account',
    'auth.register.intro_invite'        => 'Registration is by invitation. Enter your invite code to create an account.',
    'auth.register.token'               => 'Invite code',
    'auth.register.username'            => 'Username',
    'auth.register.email'               => 'Email (optional)',
    'auth.register.password'            => 'Password',
    'auth.register.password_confirm'    => 'Password (confirm)',
    'auth.register.submit'              => 'Create account',
    'auth.register.have_account'        => 'Already have an account? Sign in',
    'auth.register.err_generic'         => 'Registration could not be completed. Please try again.',
    'auth.register.err_token_required'  => 'An invite code is required.',
    'auth.register.err_token_invalid'   => 'The invite code is invalid or already used.',
    'auth.register.err_username_invalid' => 'Username must be 3-32 characters (letters, digits, underscore).',
    'auth.register.err_username_taken'  => 'That username is taken.',
    'auth.register.err_email_invalid'   => 'The email address is invalid.',
    'auth.register.err_email_taken'     => 'That email address is in use.',
    'auth.register.err_password_short'  => 'Password must be at least 8 characters.',
    'auth.register.err_password_mismatch' => 'Passwords do not match.',
    'auth.register.request_invite'      => 'No invite? Request one',
    'invite_request.page_title'         => 'Request an Invite',
    'invite_request.heading'            => 'Request an Invite',
    'invite_request.intro'              => 'This site is invite-only. You need an invite to create an account. Enter your email and why you would like one; your request is sent to the operator.',
    'invite_request.email_label'        => 'Your email address',
    'invite_request.reason_label'       => 'Why would you like an invite?',
    'invite_request.reason_hint'        => 'Tell us a little about yourself and how you found the site. This helps filter out spam requests.',
    'invite_request.submit'             => 'Send request',
    'invite_request.back_to_register'   => 'Have an invite code? Back to registration',
    'invite_request.ok'                 => 'Your request has been received. If approved, an invite code will be sent to you.',
    'invite_request.err'                => 'Could not send the request. Please check the email and reason fields and try again.',
    'invite_request.rate'               => 'You have sent too many requests. Please try again later.',
    'invite_request.full'               => 'Invite requests are currently closed. The quota is full; please try again later.',
    'invite_request.mail.subject'       => 'New invite request',
    'invite_request.mail.line_email'    => 'Email:',
    'invite_request.mail.line_reason'   => 'Reason:',

    // -----------------------------------------------------------------
    // SEO / sharing texts (1.1.30)
    // Descriptions shown in search results and link previews. The detail
    // page prefers the CATALOG synopsis; the template below is only used
    // when that is empty. The personal synopsis (user_synopsis) never
    // appears here - it is the user's own private note.
    // -----------------------------------------------------------------

    'seo.site_name'                     => 'Anime Tracker',
    'seo.index.description'             => 'Track your anime episode by episode: watch order (chronology), filler episode lists and emotion markers. Open-source anime tracking application.',
    'seo.anime.description_fmt'         => '%s - episode tracking, watch order, filler episodes and broadcast info.',
    'seo.chronology.description_fmt'    => '%s watch order: which movie, OVA or special comes after which episode - in order.',
    'seo.series.description_fmt'        => '%s series chronology: seasons, movies and specials in broadcast and story order.',
    'seo.about.description'             => 'What Anime Tracker is, what it does and the licence it is distributed under.',
    'seo.help.description'              => 'Anime Tracker help pages: fields, watch statuses, catalog sync, series and time zones.',
    'seo.help.group.description_fmt'    => 'Anime Tracker help - %s.',
    'seo.recent.description'            => 'Recently updated anime entries in the catalog.',
    'seo.statistics.description'        => 'Statistics for your list: episodes watched, genres, years and distributions.',
    'seo.recommendations.description'   => 'What should I watch? Recommendations from your list by genre, tag and emotion.',

];
