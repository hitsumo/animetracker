# Anime Tracker 0.6.1

**Release date:** 24 May 2026
**Type:** Feature (Emotion Tags v1 - personal reaction tracking)

This release ships via auto-update; you do not need to do anything.
The update adds a new table and does not touch your existing data.

## New

- **Emotion Tags.** If you do not want to assign a single score
  (1-10, stars, etc.) to an anime but still want to record "how
  did this make me feel" - this is for you. On the detail page
  you can pick from 9 tags: Saddened, Excited, Bored, Made Me
  Laugh, Scared, Thought-provoking, Surprised, Relaxing,
  Motivating. You can mark up to 3 tags per anime - because
  more than one emotion may be triggered, but marking all 9
  dilutes the signal.

- **The philosophy: marks, not scores.** Saying "8/10" about a
  film or anime compresses your experience into a single number
  and loses a lot. Instead: "This anime saddened me AND made me
  think" carries more meaning than "8/10". Emotion Tags let
  you capture the response an anime evokes in you without
  reducing it to a score.

- **Click = toggle.** Clicking a tag marks it; clicking again
  removes it. When you reach the 3-mark limit, the remaining
  inactive tags dim out - but active tags can always be removed
  (to make room for a different one).

## Improvements

- **The "Watch Status" badge now fits its label width.**
  Previously the badge could span the whole row on some detail
  pages (a long blue or green strip). Now it just hugs the label
  text, with room to breathe around it.

- **The "Anime Tracker" page title sits at the same size as
  the Search button.** Previously the title could look
  disproportionately large on the index page. The header now
  looks more balanced.

## Important Fixes

This release fixes three notable bugs carried over from 0.6 that
had been silently affecting behaviour. They were discovered during
0.6.1 testing (in a different database configuration than the
typical XAMPP setup).

- **The chronology page now shows the correct status for each
  anime in a series.** Since 0.6, every anime in the chronology
  view was labelled "upcoming" regardless of its actual watch
  status. The cause was a code path missed during the version
  transition. Animes now display with the correct status
  (Watched / Watching / Plan To Watch / On Hold).

- **Importing from the server catalog is now safer.** A rare
  database configuration could cause an error when pulling
  catalog data. This no longer happens.

- **Newly added animes now get the right watch status by
  default.** Previously, on some database configurations, a new
  anime could end up with an empty watch status (causing totals
  to disagree on the statistics page). New animes now start as
  "Plan To Watch" by default.

## Known behaviour

- **You cannot mark the same emotion twice.** This is on
  purpose: if an anime "Bored" you, marking it twice does not
  add information. A mark is not a vote - it is a reaction
  record.

- **You cannot exceed 3 marks per anime.** If you want to add
  a fourth, you must first remove one of the existing three.
  The point of the limit: it forces a meaningful pick. If an
  anime triggered 4-5 different emotions, deciding which three
  were *strongest* carries more information than marking all
  of them at once.

- **Removing an active tag is always allowed.** Even at the
  3-mark limit, you can remove an existing tag with one click.
  This frees a slot for a different tag.

- **Emotion tags are personal.** In this release you only
  see your own marks. When multi-user mode arrives in a future
  release, all users' marks can be aggregated into a
  distribution chart - but that is a later release.

## Technical notes

- A new table was added (`user_anime_emotion`). Your existing
  tables were not touched; this release is risk-free and
  reversible.

- The tag list is defined in a single place inside
  `functions.php`. Adding or removing a tag in the future
  requires editing one file - no other code is touched.
