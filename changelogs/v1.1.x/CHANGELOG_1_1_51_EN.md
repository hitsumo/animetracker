# Anime Tracker 1.1.51

**Release date:** 2026-09-25

One change: **the "What to Watch?" emotion search now looks at every
member's marks on an online installation**, and anime you have already
started are hidden from the results by default. No schema change (empty
migration); nothing to do on the central catalog. Nothing changes on a
single-user installation.

## 1. Why

The emotion panel on What to Watch (0.6.5) searched **only your own**
marks. Picking "Made Me Laugh" returned the anime you had already watched
and marked "Made Me Laugh", so the recommendation tool recommended
nothing new. On an online installation the other members' marks were
there (the detail page's emotion distribution has shown them since
1.1.45), but recommendations did not use them.

## 2. Every member's marks

On an online (multi-user) installation the emotion scoop now draws from
everyone's marks:

- **Scoring is unchanged:** each matched emotion is 1 point, the same
  unit as a sentence. An anime five members marked "Made Me Laugh" does
  not outrank one that matched two criteria.
- **Ties go to more marks:** among anime with the same score, the one
  with more marks comes first (the rule that unwatched anime come before
  watched ones still applies).
- **Count on the badge:** a matched emotion badge shows how many members
  marked it ("Made Me Laugh · 3"). The numbers are anonymous; who marked
  what is never shown. It is the same information the distribution line
  on the detail page already shows.
- **The panel opens for new members too:** a user with no marks of their
  own used to get a "you have not marked anything yet" note instead of
  the panel. Now the panel appears as soon as any member has a mark, and
  guests can use it as well. When nobody has marked anything the note
  says so.
- A one-line note under the panel: "Every member's marks are counted;
  who marked what is never shown."

## 3. "Only ones I have not started" box

On an online installation, logged-in members get a new box in the form.
**It is ticked by default:**

- Anime you watched, are watching, put on hold or dropped, and any anime
  you have counted episodes for, are left out. Anime set to "Plan to
  Watch" and anime with no status (and no counted episodes) stay.
- The box applies to both sentence and emotion matches: the question is
  "what should I watch next".
- Above the results a line says how many were hidden ("7 anime hidden
  because they are already in your list"), and **Show them too** re-runs
  the same search with the box off. When every match was hidden, this
  line replaces the empty result.
- The choice is not saved; it lives in the address. Untick the box and
  search, and that search runs without hiding.

Guests and single-user installations do not see the box.

## 4. Single-user installation

Unchanged. There is only one person's marks there; an emotion search
still means "the anime I marked with this emotion", badges carry no
count and there is no box.

## 5. Help

The What to Watch help gained a **Recommending by Emotion** heading (the
emotion panel had not been described in the help since 0.6.5): emotions
as scoops, every member's marks online, the number on the badge, the
"Only ones I have not started" box, and what it means on a single-user
installation.

## Files

**New:**

```
files/migration/1.1.51/upgrade.sql     empty (version stamp)
```

**Changed:**

```
files/recommendations.php              emotion query (everyone / own), not-started filter, badge counts, hidden line
files/help/help_discovery.php          "Recommending by Emotion" heading
files/lang/tr.php                      +9 keys
files/lang/en.php                      same
files/version.txt
```

Language file parity: 1166 = 1166.

## Deployment note

- **Nothing to do on the central catalog.** The table and index have
  existed since 0.6.1.
- The migration is empty; it carries the version stamp on the first page
  load.
- **On the distribution server**, the usual two steps: move the
  published `version.txt` to 1.1.51 and publish the
  `updates/1.1.51/anime-tracker-1.1.51.zip` package.
