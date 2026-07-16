# Anime Tracker 1.1.13

**Release date:** 2026-07-16

## New: Personal list tab

- **The main list page now has "General List" / "Personal List" tabs.** A
  slim tab bar sits between the pagination bar and the list table.
  - **General List** - the whole catalog (the previous default behaviour;
    nothing changed).
  - **Personal List** - only the anime you have set a watch status on. That
    is, every status EXCEPT "Not Selected": Watched, Watching, Plan to Watch,
    On Hold, Dropped.
- **An anime enters your personal list the moment you pick a status.**
  Setting an anime's status to anything other than "Not Selected" (via the
  in-list +/- controls or the details page) makes it appear on the Personal
  List tab. Setting it back to "Not Selected" removes it.
- **The tab keeps your current search, filters and sort.** While on the
  Personal List tab, the genre / status / letter / emotion filters and search
  all work as usual, scoped to anime with a selected status. Switching tabs
  returns you to the first page.
- **The tabs only show for users who have a personal list.** They are always
  visible on your own install (self-host). In multi-user mode a logged-out
  visitor has no personal list, so the tab bar is not shown to them.

## New: Default list preference

- **The List Settings page gains a "Default List" selector.** It chooses which
  tab is selected when the anime list page opens: **General List** (the previous
  behaviour) or **Personal List**. It is a dropdown, like the interface-language
  selector, and saves as soon as you pick a value.
- **It is per-user.** The preference affects only you (stored in the `user_pref`
  table) and never touches other users.
- **Clicking a tab overrides the preference.** Even if your default is Personal
  List, clicking the "General List" tab switches you for that visit; the next
  time you open the page your default is applied again.

## New: List Settings tabs

- **The List Settings page is now split into tabs.** There are three:
  - **Import/Export** - Export List, Import List, Import MyAnimeList List,
    Import AniList List.
  - **General Settings** - Interface Language, Title Language, Default List,
    Adult Content.
  - **Management Settings** - Genres, Tags, Catalog Sync, Episode Count Sync,
    Update. This tab (both its button and panel) is shown only to
    **moderators/admins** and the **self-host owner**; a regular online member
    never sees it (every section inside was already behind a capability gate;
    now the tab itself is hidden too).
  - **Cleanup** - Clear List. Moved to its own tab because it is a destructive
    action, and shown only to **admins** and the **self-host owner** (button and
    panel gated by `canAdmin`; a regular online member and moderators do not see
    it).
- **Switching tabs is instant, with no page reload.** The open tab is
  remembered in the browser; after an import or a sync reloads the page, the
  same tab stays open.
- **Nothing is lost with JavaScript off.** The tab bar is hidden and all
  sections stack as before (progressive enhancement) - no section was moved,
  only grouped.

## How it works (technical)

- The tab is selected with the `?view=personal` URL parameter and is a pure
  query-scope view: it adds `AND ua.watch_status IS NOT NULL` to the main
  list's existing `user_anime` LEFT JOIN. No new table, column or setting is
  needed.
- An invalid or unauthorized `view` value silently falls back to "General
  List" (including for anonymous online visitors), so it cannot be forced via
  the parameter.
- The default-list preference is stored in the per-user `user_pref` key
  `list_view_default` ('all' / 'personal') and written by a new CSRF-protected
  POST endpoint `set_list_view_pref.php`. An explicit `?view=` in the URL (a tab
  click) always overrides the preference, so the tabs always send an explicit
  view and the preserved sort/filter/search links carry the view only when it
  differs from the preference.

## Schema / migration

- `migration/1.1.13/upgrade.sql` only advances the version to 1.1.13; there is
  **no schema change** (no SQL statement to run). The central catalog is not
  affected and requires no manual step.

## Changed / new files

- index.php (General/Personal tab bar, view scope, default-preference cascade,
  tab preserved across filter/search/sort/pagination links, tab CSS)
- list_settings.php (Default List selector section + Import/Export & General
  tabs: tab bar, panel wrappers, tab CSS and JS switching)
- set_list_view_pref.php (new - CSRF-protected preference endpoint)
- lang/tr.php, lang/en.php (index.tab.* + list_settings.section.list_view.* +
  list_settings.tab.*)
- migration/1.1.13/upgrade.sql (new)
- version.txt
