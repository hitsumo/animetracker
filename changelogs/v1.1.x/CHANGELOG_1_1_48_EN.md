# Anime Tracker 1.1.48

**Release date:** 2026-09-22

One item: **the relation diagram.** The Series Chronology page gained a
third tab — **Diagram** — that shows a series as a drawing rather than a
list: every record a box, every relation a line. The diagram asks for no
extra input; it is drawn from the relations and chain names that are
already set up. No schema change (empty migration); nothing to do on the
central catalog.

## 1. Why

The two tabs of the Series Chronology read a series in one dimension:
"in what order do I watch this" (Chain Order) and "when did it come out"
(Air Date). Yet the catalog holds two more facts, and neither showed in
a list:

- **The type of a link** (since 1.1.38): "Crystal is the alternative
  version of the 90s series" was one line on the detail page; the
  chronology never drew that link between the two tracks.
- **The tracks** (since 1.1.36): chain names became tabs, but how two
  tracks stand relative to each other — which comes first, which is
  linked to which — was invisible.

The curation was already being done; only the drawing was missing.

## 2. The Diagram tab

`series_timeline.php?id=…&mode=graph`. It appears for every anime that
has a series name (like Air Date); the "Other Chain" view stays chain
mode as before.

**Layout** — two natural axes, nothing computed:

- **Row = track.** Every chain name is a row; unnamed chains are numbered
  "Chain 1, 2…" (oldest first); records in no chain gather in the
  "Unlinked" row.
- **Column = release year.** Boxes run left to right by first air date.
  Two records released in the same year **share a column when they sit
  on different rows** (a TV season and that year's film stack
  vertically); on the same row a new column opens. Records with no known
  date stay in the rightmost "?" column. The year label is printed only
  when it changes.
- **Box:** poster, shortened title, media type · year. The colour stripe
  on the left edge is your watch status (same colours as the dot in the
  list); the blue frame marks the anime you came from. Clicking opens
  the detail page; hovering shows the full title.
- **Title shortening:** the series-name prefix is dropped inside the box
  — "Tensei Shitara Slime Datta Ken: Tensura Nikki" reads "Tensura
  Nikki", "… (2021)" reads "2021". The prefix is found even when it is
  not at the start ("Gekijouban <series>: Guren no Kizuna-hen" → "Guren
  no Kizuna-hen"; "Film" is on the line below anyway). If nothing is
  left (the first season) the full title stays. Display only; tooltip
  and link carry the full title.

**Lines** — every `anime_relations` row is a line:

- **Sequel / Prequel:** solid purple arrow, flowing left to right along
  a chain. When another box sits between the two on the same row the
  line does not pass under it but arcs over the top (otherwise it would
  read as "A → B").
- **Other types:** dashed curves between rows; each type has its own
  pattern and colour (alternative version / setting green, side story /
  summary orange, same setting / shared character blue, other grey). On
  the same row they arc over the top.
- **Arrow direction, one rule:** for directed types the arrow points at
  the **derived work** — predecessor → sequel, parent story → side
  story, full story → summary. Undirected types have no arrow.
- Hovering a line shows the two records and the type of the link.
- The **legend** sits under the diagram and lists only the types and
  statuses that occur in it — not the whole vocabulary.

**Scope:** the series-name group plus records outside it that are
**directly** related to a member. Such a record (another series, or none)
appears as a faded, dash-framed **"ghost" box** in the bottom "Outside
the series" row: a link that crosses the series boundary, like Lunlun ↔
Hua Xianzi, stays visible, but the rest of that record's series is not
pulled in. Clicking a ghost opens the diagram of its own series (or its
detail page if it has none).

**Empty states:** in a series with no relations the boxes are laid out
in release order and a note says "no relation set up yet". A record
flagged 18+ keeps its box; its title stays hidden unless the preference
is on (the 1.1.2 rule).

## 3. Why plain SVG, why no library

PHP produces the diagram; the page arrives ready. No JavaScript library,
no extra download. A library such as Mermaid would shorten the work but
brings a ~3 MB payload and decides the placement itself — we could not
say "TV series on top, films below". Here the position of every box
already follows from the two axes, so a general graph layout
(force-directed etc.) is unnecessary. Font Awesome remains the project's
only external resource.

**No per-type code.** The drawing rule is type-agnostic: `sequel` is a
horizontal arrow, every other type a dashed curve. The type name only
goes into a CSS class and the legend label. When a new type enters the
vocabulary (1.1.47's Same Setting / Shared Character arrived this way;
the pending `special` will too) the diagram draws it by itself; adding a
colour line to the CSS is optional, otherwise it falls back to grey
dashes.

## 4. List Settings

**Diagram** was added to the "Series Chronology View" option, so the
page can open on the diagram by default. The option list and the saving
endpoint's whitelist now come from one source (the mode list) — the
hard-coded copy that in 1.1.46 silently refused to save "Recently
Watched" existed here too and is gone.

## 5. Help

- The "Series Chronology Page" section of the series help: Diagram in
  the tab list; the "two tabs" sentences now say "three"; a new
  **"Diagram"** subsection — row/column rule, line types, arrow
  direction, status stripe, ghost box, scrolling.
- The List Settings description mentions the diagram.

## Files

**New:**

```
files/functions/series_graph_helpers.php  data gathering, row/column layout, SVG, legend
files/migration/1.1.48/upgrade.sql        empty (version stamp)
```

**Changed:**

```
files/series_timeline.php                 Diagram tab, mode=graph branch, diagram CSS, wide container
files/functions/series_helpers.php        series_timeline_modes(): 'graph'; single-source note
files/functions.php                       loads the new helper file
files/list_settings.php                   <select> and whitelist from series_timeline_modes()
files/set_series_timeline_mode.php        comment (mode list)
files/help/help_series.php                "Diagram" subsection
files/lang/tr.php                         +11 keys (series_timeline.tab.graph, series_graph.*, help.st.graph.*); 4 texts changed
files/lang/en.php                         same
files/version.txt
```

Language file parity: 1149 = 1149.

## Deployment note

- **Nothing to do on the central catalog.** The diagram only reads; no
  new table or column.
- The migration is empty; it moves the version stamp on the first page
  load.
- The `functions/` folder must go **whole**: `functions.php` loads the
  new `series_graph_helpers.php`; with the file missing every page
  fails fatally.
- Known limits (deliberate): row labels scroll with the diagram (they
  live inside the SVG); a very wide series needs horizontal scrolling;
  the page carries no mobile viewport meta (as since 1.1.23, untouched
  in this release).
- **On the distribution server** the usual two steps: the published
  `version.txt` goes to 1.1.48 and the `updates/1.1.48/anime-tracker-1.1.48.zip`
  package is published.
