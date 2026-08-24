# Anime Tracker 1.1.28

**Release date:** 2026-08-08

## Fix: the broadcast information section stayed closed on "Not Yet Aired" anime

- **The problem:** the **Broadcast Information** section of the add and edit
  forms (episode interval, broadcast day, broadcast time, broadcast timezone)
  only opened while the status was "Currently Airing". On an anime marked "Not
  Yet Aired" you could neither see nor fill in those four fields.
- **Yet a show that has not started still has a known slot.** An anime is
  announced as "Saturday 23:30" weeks before the season begins; that information
  does not appear on the day it starts airing. AnimeSchedule returns these
  fields for upcoming titles as well.
- **So "Auto Fill" was writing into a place you could not see.** On an upcoming
  anime the button really did fill in the broadcast day and time, and the report
  said so honestly — "broadcast_day *(in a hidden section)*" — but with the
  field off-screen you could neither check nor correct it.
- **The section now opens on "Not Yet Aired" too.** You can fill the fields by
  hand, see what auto-fill brought in, and correct it. By the time the anime
  starts airing and the status flips to "Currently Airing", the data is already
  in place.
- **The anime detail page shows it as well now.** An upcoming anime's detail
  page carries the Broadcast Day and Broadcast Time rows, along with the source
  note. Otherwise the time you entered in the form would be visible nowhere.
- **The "Next Episode" row is deliberately left out.** An upcoming anime's "next
  episode" is its first one, and the Release Date row already carries that.
- **The "(in a hidden section)" note was not removed** — it still applies to the
  cases that really are closed (for example everything but the episode count on
  a finished anime).

## Fix: your personal notes were missing from the detail page

- **The problem:** the **Notes** row on the detail page sat *inside* the
  broadcast information block — that is, behind the "Currently Airing only"
  condition. As a result, a note written on a **finished, upcoming or cancelled**
  anime **never appeared** on its detail page.
- **Nothing was lost.** The note was saved, kept, and editable in the edit form;
  it simply was not rendered on the detail page.
- **The reach was larger than it sounds:** most of a catalog is finished anime,
  so most notes were invisible.
- **It now shows in every status.** A note is personal data and has nothing to do
  with the anime's broadcast status. On airing anime the note's position is
  unchanged.
- **The chronology button appears exactly once in every status** (counted and
  verified while making this fix).

## Fix: three defects in the "External Sites" section

- **The AnimeSchedule button depended on the MyAnimeList link.** On an anime
  where you had entered the AnimeSchedule address but left the MAL box empty,
  the button never appeared. Since 1.1.27 made "enter only the AnimeSchedule
  link" a supported workflow, this had become quite visible. Each button now has
  its own condition.
- **With no address, the button led to the site's home page** (and only did so
  when a MAL link existed). This section lists the links *for this anime*; a
  button that does not take you to the anime's page is noise, not information.
  With no address the button is no longer rendered at all.
- **An anime with no links at all still got an empty "External Sites"
  heading.** The section's condition always evaluated to true. It now appears
  only when there is at least one link.
- **The source note under the broadcast time is unchanged** — that is an
  attribution ("where the time came from"), not a link to the anime's page, so
  falling back to the service's home page is the right behaviour there.

## What deliberately did not change

This release fixes the visibility rule of the **broadcast information** section
only. Its neighbours were left exactly as they were:

- **"Aired Episodes" still appears only while the status is "Currently
  Airing".** On an upcoming anime that count is zero by definition, and the
  server already clears the field for every non-airing status.
- **"Broadcast End Date" still appears only on finished, non-single-episode
  works.**
- **The next-episode date is still computed only for airing anime.**
- **Finished anime still show no broadcast day/time at all, and auto-fill still
  never carries those fields for them** (the rule introduced in 1.1.27). There,
  those fields genuinely are meaningless.

## Your data is not affected

The broadcast day, time, timezone and episode interval were **already saved in
every status** — a hidden form section still submits its values. Your notes were
always saved too; what changed is that they are now **displayed**. This release
moves, deletes and converts nothing.

## Also: detail page cleanup

While doing the work above, `anime_details.php` was read end to end. Fixes that
change nothing visible but make the file sturdier:

- **Opening `anime_details.php` with no id** now shows a clean "anime not found"
  instead of emitting a PHP warning first. The same happened with a non-existent
  id.
- **Indentation fixed.** Part of the detail rows started at column zero; they
  were pulled in line with their siblings. The rendered page is byte-identical
  in meaning — verified with a whitespace-insensitive diff.

## Schema / migration

- **No schema change.** No new table, column or preference.
  `migration/1.1.28/upgrade.sql` carries the version stamp only.
- **No manual work on the central catalog server** — the catalog wire was not
  touched.

## Changed / new files

- files/js/anime_form.js (visibility rule: the broadcast section now opens on
  "Not Yet Aired" as well)
- files/edit_anime.php (the section's initial state on page load)
- files/anime_details.php (broadcast information shown for upcoming anime too;
  **the notes row moved out of that block** — visible in every status; the
  "External Sites" section fixed; indentation and small hardening)
- files/functions/animeschedule_helpers.php (**comment update only** — the old
  note described a form rule that no longer holds; behaviour is identical)
- files/migration/1.1.28/upgrade.sql (new, version stamp only)
- files/version.txt

`files/add_anime.php` is **unchanged**: on that form the section always starts
closed (the default status is "Not selected") and its visibility is driven
entirely by the script.

## Deployment note

- No new files. `files/js/anime_form.js` and `files/edit_anime.php` must be
  uploaded **together**; if one of them stays behind, the edit form contradicts
  the script on page load (the section starts open and closes on the first
  status change, or the other way round) — an inconsistency that is easy to miss
  and annoying to diagnose.
- `files/anime_details.php` is independent and can be uploaded on its own, but
  keeping it at the same version makes sense: the form opens the time field for
  entry, the detail page displays it.
- The version stamp in asset URLs (`?v=1.1.28`, in place since 1.1.24) refreshes
  the browser cache on its own; there is nothing to do by hand.
