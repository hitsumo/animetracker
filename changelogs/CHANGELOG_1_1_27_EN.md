# Anime Tracker 1.1.27

**Release date:** 2026-08-08

## New: the watched-episode count can now be changed from the anime detail page

- **The "Watched Episodes" row on the detail page now carries (−) and (+)
  buttons.** Until now that number could only be changed from the list page or
  by opening the edit form; while reading an anime's details you had to go back
  to the list just to move one episode forward.
- **Same buttons as in the list.** The same bounds apply: the count never drops
  below 0 and never rises above the total (or, when the total is unknown, the
  aired episode count). At a bound the matching button greys out and stops
  responding.
- **The watch status moves along on its own.** Pressing (+) on a "Plan to Watch"
  anime turns it into "Watching"; reaching the final episode turns it into
  "Watched"; stepping back returns it to "Watching". The coloured status badge
  on the detail page follows immediately — both its text and its colour.
- **No buttons on anime with an unknown episode count.** With neither a total
  nor an aired count there is no ceiling to show, so the row stays a plain
  number as before. Run "Sync" or enter the episode data first.
- **The page refreshes itself a second and a half later.** Three other things on
  the detail page are derived from the watched count: the "Next Episode" row
  (how far behind you are), the chronology alert (watch this after that episode)
  and the finish-date stamp. They have to be re-rendered when the number
  changes, or the page contradicts itself. Pressing repeatedly restarts the
  timer — eight (+) presses cause a single refresh.
- **Half-written text is never discarded.** If you have started filling in any
  box on the page (correction note, chronology marker, episode field) the
  refresh is skipped entirely; the number still updates instantly.

## New: the release date and end date are filled in too

- **"Auto Fill" now fills the Release Date** — whatever the anime's status
  (airing, not yet aired, finished). That field was never auto-filled before.
- **On a finished anime the Broadcast End Date is filled as well**, alongside
  the episode count.
- **Single-episode works (film, special, OVA) get no end date** — for those the
  end date *is* the release date, and the form does not show the field at all.
  What gets filled there is the Release Date. (Example: *Lupin III vs Meitantei
  Conan* — AniList likewise reports the same day for both: 2009-03-27.)
- **When the episode count fills as 1, the end-date section now closes on its
  own.** The form only applied that rule when you typed the number yourself, so
  the section could stay open after an autofill.
- **The dates come from AniList — they are not calculated.** The AnimeSchedule
  anime record has **no end-date field at all**: it carries the first-episode
  date and a broadcast time, but nothing marking the finale. Calculating it as
  "first episode + (episodes−1)×7 days" was possible but **would not be
  correct**: for a series that skipped a week the result quietly drifts. Take
  *Ahiru no Sora*: it started on 2 October 2019 and ran 50 episodes; the
  calculation says 9 September 2020, while the real end was **30 September
  2020** — three weeks out. So the dates are read from the source that actually
  knows: AniList's own date fields.
- **The release date comes from there too, for the same kind of reason.**
  AnimeSchedule has the first episode's *timestamp*, but turning an instant into
  a calendar date can slip by a day for late-night broadcasts (a show announced
  as "Friday at 25:25" actually airs Saturday 01:25). AniList's release date is
  already the settled calendar date, so both dates come from one query.
- **No extra configuration.** The AniList side needs no API key, so this works
  even if you never configured one.
- **It does not matter which link you pasted.** The id needed for matching is
  read from the cross-site links inside the AnimeSchedule response we already
  fetched. So the dates arrive even if you only entered an AniDB link, or
  pressed "Auto Fill" before filling the MAL box. If the response carries no
  such link, the form's MAL link is used instead; with neither, the dates are
  skipped and the rest of the autofill works exactly as before.
- **Only where it means something.** An airing show has no end date yet, and for
  a single-episode release the form does not show the field at all. In those two
  cases no end date is written — the release date still is.
- **On the Add Anime page the broadcast status can now be filled too.** The
  status box starts at "Not selected" — which is the form's empty state, not a
  choice. It used to count as "filled", so Auto Fill could never set the status,
  and the sections that depend on it (broadcast details, end date) never opened.
  Now the status fills and the relevant section opens on its own.

## Fix: on a finished anime, "Auto Fill" claimed to fill fields it had not

- **The problem:** pressing the AnimeSchedule "Auto Fill" button on a finished
  anime reported "Fields filled: 3: broadcast_day, broadcast_timezone,
  total_episodes" while nothing visibly changed on the form. Two of those three
  were not really filled at all:
  - **Broadcast day and timezone are not visible on a finished anime.** That
    whole section only opens for "Currently Airing" — a weekly broadcast slot
    only means something while a show is still airing, and the detail page never
    shows it for a finished anime either. Auto Fill was writing into that hidden
    section and counting it as success.
  - **The timezone already held the value being written.** The field starts at
    "Asia/Tokyo" and the service returns "Asia/Tokyo", so the same value was
    written over itself: nothing changed, yet it still went into the count. That
    entry padded the list every single time.
- **Broadcast day/time/timezone are no longer fetched for a finished anime.**
  For those the fillable thing is the episode count, and the message now says so.
  When there is nothing left to fill it honestly says "no empty fields to fill".
- **Nothing changed for airing or upcoming anime** — broadcast day and time are
  fetched exactly as before.
- **The message now counts only fields that genuinely changed.** If writing a
  value leaves the field exactly as it was, it is no longer called "filled". If
  the value matches none of the form's options (a browser silently ignores such
  an assignment), the field is restored and the message says so. If a field is
  filled but is not on screen at that moment, its name carries an "(in a hidden
  section)" note — so nobody goes hunting for a field the form is not showing.

## Fix: the watch status was written in Turkish on the English interface

- On the English interface, pressing (+) or (−) in the list wrote the updated
  status into the cell **in Turkish** ("Izlendi" and so on) while the rest of
  the page was English. This has been the case since 0.6 and is now fixed — the
  label follows your interface language. Nothing changes for Turkish users.

## How it works (technical)

- The detail page **opens no new write path**: it posts to the same endpoint
  (`update_watched.php`) the list buttons use. The bound rules, the watch-status
  transitions and the permission gate all stay in one place; the detail page is
  simply that endpoint's second client.
- The ceiling is computed with the exact same rule on both pages (total if set,
  else aired, else no ceiling). The server enforces that bound itself — the
  browser-side copy is for appearance only.
- The status badge's **colour** now comes from the server too (a
  `watch_status_css` field was added to the reply), so the status-to-colour
  mapping stays in a single place instead of being copied into the browser.
- Signed-out visitors get no buttons at all; the watched count is personal data
  and the endpoint requires a sign-in anyway.
- The button styles (`.ep-*`) moved out of the embedded style block in
  `index.php` and into `css/components.css`. With the widget now living on two
  pages, two separate copies of those rules would inevitably drift apart. The
  rules moved unchanged; the list page looks exactly as it did.

## Schema / migration

- **No schema change.** No new table, column or preference.
  `migration/1.1.27/upgrade.sql` carries the version stamp only.
- **No manual work on the central catalog server** — the catalog wire was not
  touched. The watched count and watch status are personal data already.

## Changed / new files

- files/anime_details.php (+/- widget and its script on the watched row)
- files/update_watched.php (`watch_status_css` in the reply; language-init fix)
- files/index.php (embedded `.ep-*` style block emptied)
- files/css/components.css (`.ep-*` styles moved here + horizontal variant)
- files/functions/animeschedule_helpers.php (no broadcast data for finished anime)
- files/functions/anilist_import_helpers.php (new helper reading the end date from AniList)
- files/fetch_animeschedule.php (end-date step)
- files/js/anime_form.js (report counts only real changes; MAL link sent to the
  endpoint; "Not selected" treated as empty)
- files/add_anime.php, files/edit_anime.php (two new language keys passed to the script)
- files/lang/tr.php, files/lang/en.php (four new strings)
- files/migration/1.1.27/upgrade.sql (new, version stamp only)
- files/version.txt

## Deployment note

- No new files, but `files/anime_details.php` and `files/css/components.css`
  must be uploaded **together**. If `components.css` is missing, not only the new
  detail-page buttons but **the existing list buttons too** will render unstyled
  — the rules now live there. The version stamp in asset URLs (`?v=1.1.27`,
  in place since 1.1.24) refreshes the browser cache on its own; there is
  nothing to do by hand.
