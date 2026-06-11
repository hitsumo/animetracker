# Anime Tracker 1.0.10

**Release date:** (fill in on deploy day)

## New

### The "Dropped" watch status is now usable
The fifth watch status that has existed in the database for a while is
now in the interface:
- Selectable in the add and edit forms, filterable in the main list.
- Turkish label "İzleme Bırakıldı", English label "Dropped".
- Shown with its own red badge color on every page.
- Pressing "+" on a dropped anime is a "resuming" signal and
  automatically switches the status to "Watching"; "-" leaves the
  status alone and only decrements the count.
- The watched episode count is preserved and stays visible in the form
  (where you dropped a series is meaningful information).

### "Not Selected" is now a real status
- "Not Selected" is selectable in the add and edit forms; you can add
  an anime without assigning a watch status, or set an existing status
  back to "not selected".
- The edit form no longer opens a not-selected anime as "Plan to
  Watch"; an unrelated save such as fixing a typo in the title no
  longer silently changes the status.
- Adding only a note or a personal synopsis no longer auto-assigns
  "Plan to Watch".
- Pressing "+" on a not-selected anime automatically switches it to
  "Watching".
- The details page, recent list, series timeline, chronology alerts,
  related animes and recommendations all show the not-selected state
  with its own gray badge.

### Sorting by status is now language-aware alphabetical
- The "Status" column in the main list now sorts by the label shown on
  screen, and the order follows the active UI language. In Turkish:
  İzleme Bırakıldı → İzleme Ertelendi → İzlendi → İzleniyor → İzlenme
  Planlandı → Seçim Yapılmamış. In English: Dropped → Not Selected →
  On Hold → Plan to Watch → Watched → Watching.
- "Not Selected" is not pinned to either end; it takes its own
  alphabetical place and reverses with the rest in descending order.
- The PHP intl extension is used for correct Turkish collation; a
  built-in Turkish comparison takes over when the extension is absent.

## Fixes
- On online installations, a user with an expired session opening one
  of the admin pages was redirected to a 404 instead of the login
  page; redirect targets are now root-absolute.
- Main list status filter: the "Plan to Watch" filter now returns only
  animes explicitly set to "Plan to Watch"; not-selected animes are
  listed under their own filter.
- The personal watch status column was removed from the admin pending
  list; it was misleading side data unrelated to the page's job.

## Database
- The `user_anime.watch_status` column now accepts NULL (NULL = not
  selected); the column default is NULL. Existing rows are untouched,
  no data conversion. The migration is applied automatically.

## Changed files
- `index.php`
- `add_anime.php`
- `edit_anime.php`
- `update_watched.php`
- `anime_details.php`
- `recent.php`
- `recommendations.php`
- `statistics.php`
- `series_timeline.php`
- `schema.sql`
- `version.txt`
- `upgrade.sql`
- `js/anime_form.js`
- `functions/watch_status_helpers.php`
- `functions/user_anime_helpers.php`
- `functions/series_helpers.php`
- `functions/auth_helpers.php`
- `css/components.css`
- `css/series.css`
- `admin/admin_pending.php` *(online installations)*
- `lang/admin_tr.php` *(online installations)*
- `lang/admin_en.php` *(online installations)*

## New files
- `migration/1.0.10/upgrade.sql`
