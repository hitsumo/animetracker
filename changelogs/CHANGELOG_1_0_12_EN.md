# Anime Tracker 1.0.12

**Release date:** (fill in on deploy day)

## New

### Automatic episode tracking now follows the Japanese (raw) broadcast schedule
The automatic "aired episode" sync done through AnimeSchedule used to query the
subtitled (sub) timetable, while the next-episode countdown was already
computed from the Japanese broadcast day. Because those are two different
tracks, the aired count and the countdown could drift apart. Now both follow
the same track — the Japanese (raw) broadcast — so the aired count and the
countdown stay consistent.

### Shows whose broadcast has finished are marked "Yayın Tamamlandı" automatically
When every episode of a show has aired, the sync flips its status to
"Yayın Tamamlandı" automatically and clamps the aired count to the total. The
countdown then stops rolling forward week after week; previously this required
editing the anime by hand.

### The countdown stops on a completed show
On a show whose aired count has reached the total, the next-episode countdown
is no longer rolled forward; the completed view takes over.

## Notes
- The automatic sync runs when the List Settings page is opened (once a day, or
  manually via the "Update" button); it needs an AnimeSchedule API key.
- After the first sync some ongoing shows may jump by an episode or two as they
  align to the raw schedule (raw can be ahead of subs). This is expected.
- Shows that air with subtitles only and are absent from the raw timetable may
  not match during the sync. Tracking the sub schedule alongside raw is planned
  for a future release.
- The database schema is unchanged in this release.

## Changed files
- `animeschedule_helpers.php`
- `anime_helpers.php`
- `index.php`
- `version.txt`
- `upgrade.sql`

## New files
- `migration/1.0.12/upgrade.sql`
