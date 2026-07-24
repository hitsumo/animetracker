<?php

/**
  [Anime Tracker/Anime izleme takip listesi.
    https://www.sicakcikolata.com]
  Copyright (C) 2025 [Okan Sumer]
 
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 2 as
 published by the Free Software Foundation.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.
 
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 MA 02110-1301, USA.
 */

// =====================================================================
// Helper loader. functions.php was split into functions/*.php in 0.6.7
// (pure code reorganization, no behavior change). Every page that did
// require_once 'functions.php' keeps working unchanged - this loader
// pulls in all helper modules. If any module file is missing from the
// deployment/ZIP, the app fatals on the first helper call, so packaging
// must include the whole functions/ directory.
// =====================================================================

require_once __DIR__ . '/functions/watch_status_helpers.php';
require_once __DIR__ . '/functions/broadcast_status_helpers.php';
require_once __DIR__ . '/functions/country_helpers.php';
require_once __DIR__ . '/functions/title_lang_helpers.php';
require_once __DIR__ . '/functions/emotion_helpers.php';
require_once __DIR__ . '/functions/filler_helpers.php';
require_once __DIR__ . '/functions/i18n_helpers.php';
require_once __DIR__ . '/functions/anime_helpers.php';
require_once __DIR__ . '/functions/user_anime_helpers.php';
require_once __DIR__ . '/functions/mal_import_helpers.php';
require_once __DIR__ . '/functions/anilist_import_helpers.php';
require_once __DIR__ . '/functions/security_helpers.php';
require_once __DIR__ . '/functions/auth_helpers.php';
require_once __DIR__ . '/functions/series_helpers.php';
require_once __DIR__ . '/functions/taxonomy_helpers.php';
require_once __DIR__ . '/functions/animeschedule_helpers.php';
require_once __DIR__ . '/functions/synopsis_helpers.php';
