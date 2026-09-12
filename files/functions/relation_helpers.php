<?php

/**
 * Anime Tracker - Anime Relation Helpers (tipli iliskiler)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.38.
 *
 * WHY THIS FILE EXISTS
 *
 * 1.1.36 gave a series' watch tracks a NAME (animes.chain_name), which
 * answered "which track is this record on". It deliberately did NOT answer
 * the other question the same investigation raised: WHAT KIND of link do
 * two records have. Both bugs that started 1.1.36 were really about the
 * missing type, not the missing name:
 *
 *   Space Adventure Cobra (1982, film) is the ALTERNATIVE VERSION of the
 *   Space Cobra TV series (AniDB a1384 / a1091). The catalog could only
 *   say "linked" or "not linked", so the curator's honest answer - "these
 *   two belong together but one does not follow the other" - was
 *   unsayable, and the pair looked like missing data.
 *
 *   Sailor Moon Crystal (also an alternative version) had been linked INTO
 *   the 90s chain, so the timeline claimed a watch order that does not
 *   exist and the spoiler gate counted eight unrelated records as Crystal's
 *   unwatched predecessors.
 *
 * This file is the second of the three steps recorded in KARARLAR_4 sec.94:
 * chain_name (1.1.36) -> anime_relations, ORDERLESS types only (1.1.38) ->
 * sequel/prequel move into the table and next_in_series retires (1.1.39).
 *
 * WHY `sequel` IS NOT IN THE ENUM
 *
 * Watch ORDER still lives in exactly one place: animes.next_in_series. If
 * this table could also carry a `sequel` edge, two sources could disagree
 * about the same pair and the timeline would have to pick a winner. Leaving
 * the value out of the enum makes that contradiction impossible to enter -
 * not merely discouraged. Every type defined here is ORDERLESS: it says the
 * two records are related, never which one is watched first. Nothing in
 * this file feeds the series timeline or the spoiler gate.
 *
 * DIRECTION AND ITS INVERSE
 *
 * A row reads FROM IS THE <type> OF TO:
 *
 *     (from_anime_id, to_anime_id, relation_type)
 *
 *   side_story : `from` is the side story, `to` is the parent story.
 *   summary    : `from` is the summary,   `to` is the full story.
 *
 * Those two are the only asymmetric types, and the asymmetry is real: if A
 * is B's side story, B is NOT A's side story - it is A's PARENT story. So
 * the same row renders with two different labels depending on which end is
 * being viewed (anime_relation_type_label(..., $inverse)). The remaining
 * three (alternative_version, alternative_setting, other) mean the same
 * thing read either way; for them the direction carries no information, so
 * it is CANONICALISED at write time (smaller id first). Without that, the
 * same statement could be stored twice - once per direction - and the
 * UNIQUE key would not catch it.
 *
 * LOCAL ONLY - LIKE next_in_series AND chain_name
 *
 * Relations are NOT pushed to the central catalog: the wire format gains no
 * field, no manual ALTER is needed on the catalog server, and no file under
 * catalog_server/ changed in this release. The reason is the one that keeps
 * next_in_series local too - a relation is a pair of LOCAL row ids, and
 * every install numbers its rows differently. Carrying it would need the
 * stable-identity quadruple the chronology markers use (mal_id / anidb_id /
 * catalog_uuid / title); the JSON backup in list_settings.php does exactly
 * that, so a relation survives backup-and-restore even though it does not
 * cross to the central catalog.
 */

// =====================================================================
// Types
// =====================================================================

/**
 * The relation types, in display order.
 *
 * The order is also the grouping order on the detail page: the two
 * "another telling of the same thing" types first, then the two
 * "smaller piece / shorter cut" types, then the catch-all.
 *
 * @return string[] enum values of anime_relations.relation_type
 */
function anime_relation_types() {
    return [
        'alternative_version',
        'alternative_setting',
        'side_story',
        'summary',
        'other',
    ];
}

/**
 * Is this type read the same way from both ends?
 *
 * Symmetric types are stored canonically (smaller id first) because their
 * direction carries no meaning; asymmetric ones keep the direction the
 * curator entered.
 *
 * @param string $type
 * @return bool
 */
function anime_relation_symmetric($type) {
    return in_array($type, ['alternative_version', 'alternative_setting', 'other'], true);
}

/**
 * Label of a relation as seen from ONE end.
 *
 * $inverse = "I am looking at this row from its `from` end", i.e. the
 * OTHER anime is what `to` is. For the two asymmetric types that flips the
 * word: side story -> parent story, summary -> full story. For the
 * symmetric ones both ends read the same, so the flag is ignored.
 *
 * @param string $type
 * @param bool   $inverse
 * @return string Translated label.
 */
function anime_relation_type_label($type, $inverse = false) {
    if ($inverse) {
        if ($type === 'side_story') { return t('relation.type.parent_story'); }
        if ($type === 'summary')    { return t('relation.type.full_story'); }
    }
    if (!in_array($type, anime_relation_types(), true)) {
        $type = 'other';
    }
    return t('relation.type.' . $type);
}

/**
 * The <select> options of the add-relation form, in display order.
 *
 * The form asks ONE question - "the anime you picked is this anime's
 * ____" - so the labels are possessive and the two asymmetric types appear
 * twice, once per direction. The key is what the form posts; the '|inv'
 * suffix marks the inverted direction. Keys are opaque to the UI: only
 * anime_relation_parse_choice() takes them apart.
 *
 * @return array<string,string> choice key => translated label
 */
function anime_relation_choices() {
    return [
        'alternative_version' => t('relation.opt.alternative_version'),
        'alternative_setting' => t('relation.opt.alternative_setting'),
        'side_story'          => t('relation.opt.side_story'),
        'side_story|inv'      => t('relation.opt.parent_story'),
        'summary'             => t('relation.opt.summary'),
        'summary|inv'         => t('relation.opt.full_story'),
        'other'               => t('relation.opt.other'),
    ];
}

/**
 * Take a posted choice key apart.
 *
 * @param mixed $raw
 * @return array{type:string,inverse:bool}|null null if the key is unknown.
 */
function anime_relation_parse_choice($raw) {
    $raw     = is_string($raw) ? trim($raw) : '';
    $inverse = false;
    if (substr($raw, -4) === '|inv') {
        $raw     = substr($raw, 0, -4);
        $inverse = true;
    }
    if (!in_array($raw, anime_relation_types(), true)) {
        return null;
    }
    // '|inv' on a symmetric type is meaningless, not an error: the two
    // readings are the same sentence. Drop the flag so the row is still
    // canonicalised.
    if ($inverse && anime_relation_symmetric($raw)) {
        $inverse = false;
    }
    return ['type' => $raw, 'inverse' => $inverse];
}

/**
 * Which id goes in from_anime_id and which in to_anime_id.
 *
 * $anime_id is the anime being edited, $other_id the one just picked, and
 * the choice is phrased from the picked anime's side ("the picked one is
 * THIS one's side story"). So:
 *
 *   asymmetric, straight : picked IS the <type> of the edited one
 *                          -> from = picked, to = edited
 *   asymmetric, inverted : the edited one is the <type> of the picked one
 *                          -> from = edited, to = picked
 *   symmetric            : direction means nothing -> smaller id first
 *
 * @param int    $anime_id
 * @param int    $other_id
 * @param string $type
 * @param bool   $inverse
 * @return array{0:int,1:int} [from, to]
 */
function anime_relation_endpoints($anime_id, $other_id, $type, $inverse) {
    $anime_id = (int)$anime_id;
    $other_id = (int)$other_id;

    if (anime_relation_symmetric($type)) {
        return $anime_id <= $other_id ? [$anime_id, $other_id] : [$other_id, $anime_id];
    }
    return $inverse ? [$anime_id, $other_id] : [$other_id, $anime_id];
}

// =====================================================================
// Reading
// =====================================================================

/**
 * Every relation of one anime, both directions, ready to render.
 *
 * The JOIN picks whichever end is NOT $anime_id, so the caller never has
 * to know which column the anime sat in. Each returned row carries:
 *
 *   id, relation_type      - the stored row
 *   inverse                - true when $anime_id is the `from` end, i.e.
 *                            the OTHER anime needs the flipped label
 *   label                  - that label, already translated
 *   other_id + title / alternative_titles / media_type / total_episodes
 *   watch_status, watched_episodes  - the CURRENT user's progress (1.0.1)
 *
 * +18 masking follows the next_in_series card (1.1.2): the row stays -
 * hiding it would make the relation list lie about how many relations
 * exist - but the title is replaced by the neutral placeholder.
 *
 * :id is bound three times under three names on purpose: db.php runs with
 * EMULATE_PREPARES = false, where a repeated named placeholder is an
 * error.
 *
 * @param PDO $pdo
 * @param int $anime_id
 * @return array<int,array<string,mixed>>
 */
function getAnimeRelations($pdo, $anime_id) {
    $anime_id = (int)$anime_id;
    if ($anime_id <= 0) {
        return [];
    }

    $order = "'" . implode("','", anime_relation_types()) . "'";
    $stmt  = $pdo->prepare("
        SELECT r.id, r.relation_type,
               CASE WHEN r.from_anime_id = :id1 THEN 1 ELSE 0 END AS is_from,
               o.id AS other_id, o.title, o.alternative_titles, o.media_type,
               o.total_episodes, o.release_date, o.image_path, o.is_adult,
               ua.watch_status,
               COALESCE(ua.watched_episodes, 0) AS watched_episodes
          FROM anime_relations r
          JOIN animes o
            ON o.id = CASE WHEN r.from_anime_id = :id2 THEN r.to_anime_id ELSE r.from_anime_id END
     LEFT JOIN user_anime ua
            ON ua.anime_id = o.id AND ua.user_id = :uid
         WHERE r.from_anime_id = :id3 OR r.to_anime_id = :id4
      ORDER BY FIELD(r.relation_type, $order), o.release_date ASC, o.title ASC
    ");
    $stmt->execute([
        ':id1' => $anime_id,
        ':id2' => $anime_id,
        ':id3' => $anime_id,
        ':id4' => $anime_id,
        ':uid' => current_user_id(),
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['inverse'] = ((int)$row['is_from'] === 1);
        $row['label']   = anime_relation_type_label($row['relation_type'], $row['inverse']);
        $row            = adult_mask_related($row, 'is_adult', 'title', 'alternative_titles');
    }
    unset($row);

    return $rows;
}

/**
 * Group the rows of getAnimeRelations() by their RENDERED label.
 *
 * Grouping by label and not by relation_type is deliberate: one stored
 * type produces two headings ("Yan Hikaye" / "Ana Hikaye") and a single
 * list mixing them would state the opposite of the truth for half its
 * entries. Insertion order is kept, so the type order of
 * anime_relation_types() still decides the heading order.
 *
 * @param array $rows
 * @return array<string,array> label => rows
 */
function anime_relations_grouped(array $rows) {
    $grouped = [];
    foreach ($rows as $row) {
        $grouped[$row['label']][] = $row;
    }
    return $grouped;
}

// =====================================================================
// Writing - the rules a new relation must survive
// =====================================================================

/**
 * The relation already recorded between two animes, if any.
 *
 * A PAIR CARRIES AT MOST ONE RELATION. Two rows about the same pair would
 * be either a duplicate ("alternative version" twice) or a contradiction
 * ("A is B's summary" AND "B is A's summary"), and no third case exists
 * that the curator could not express by picking the better single type. So
 * the endpoint refuses a second one and says which is already there,
 * instead of silently stacking them. Both directions are checked because
 * the stored direction depends on the type.
 *
 * @param PDO $pdo
 * @param int $a
 * @param int $b
 * @return array|false The existing row, or false.
 */
function anime_relation_between($pdo, $a, $b) {
    $stmt = $pdo->prepare("
        SELECT id, from_anime_id, to_anime_id, relation_type
          FROM anime_relations
         WHERE (from_anime_id = :a1 AND to_anime_id = :b1)
            OR (from_anime_id = :b2 AND to_anime_id = :a2)
         LIMIT 1
    ");
    $stmt->execute([':a1' => (int)$a, ':b1' => (int)$b, ':b2' => (int)$b, ':a2' => (int)$a]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row : false;
}

/**
 * Are these two animes ALREADY linked as a watch order?
 *
 * Every type in this table is orderless, so a relation between two records
 * that the curator has also chained with next_in_series states two
 * incompatible things at once - which is precisely the Sailor Moon Crystal
 * bug that 1.1.36 was written for. The endpoint blocks it and names the
 * fix ("remove the Next Anime link first").
 *
 * The check honours the 1.1.36 rule instead of restating it: a link is
 * only followed when both ends carry the SAME chain name (chain_same),
 * so a dormant link - one the timeline already ignores because the names
 * differ - does not block anything. One rule, one place.
 *
 * @param PDO $pdo
 * @param int $a
 * @param int $b
 * @return bool
 */
function anime_relation_chain_conflict($pdo, $a, $b) {
    $stmt = $pdo->prepare("
        SELECT id, chain_name, next_in_series
          FROM animes
         WHERE id IN (:a, :b)
    ");
    $stmt->execute([':a' => (int)$a, ':b' => (int)$b]);
    $rows = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[(int)$row['id']] = $row;
    }
    if (count($rows) !== 2) {
        return false;
    }
    $rowA = $rows[(int)$a];
    $rowB = $rows[(int)$b];

    $linked = ((int)($rowA['next_in_series'] ?? 0) === (int)$b)
           || ((int)($rowB['next_in_series'] ?? 0) === (int)$a);

    return $linked && chain_same($rowA['chain_name'], $rowB['chain_name']);
}

/**
 * The sentence for a relation_error code coming back on the URL.
 *
 * The endpoint sends a fixed KEYWORD, never text: the message itself is
 * looked up here, so it is translated and so nothing a request carries
 * can reach the page. An unknown code falls back to the generic failure,
 * which is what an old bookmark or a hand-edited URL deserves.
 *
 * @param string $code
 * @return string Translated sentence, or '' when there is no error.
 */
function anime_relation_error_message($code) {
    $code = is_string($code) ? trim($code) : '';
    if ($code === '') {
        return '';
    }
    $known = ['input', 'self', 'missing', 'exists', 'chain', 'failed'];
    if (!in_array($code, $known, true)) {
        $code = 'failed';
    }
    return t('relation.error.' . $code);
}
