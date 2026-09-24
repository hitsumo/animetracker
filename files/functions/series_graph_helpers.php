<?php

/**
 * Anime Tracker - Series Graph Helpers (iliski semasi)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.48.
 *
 * WHY THIS FILE EXISTS
 *
 * series_timeline.php'nin iki sekmesi bir seriyi iki LISTE olarak okur:
 * zincir sirasi (`sequel` yuruyusu) ve yayin tarihi. Ikisi de tek
 * boyutludur. 1.1.38'den beri katalogda BAGIN TURU de var
 * (anime_relations: alternatif versiyon, yan hikaye, ozet, 1.1.47 ile
 * ayni evren ve ortak karakter) ve 1.1.36'dan beri kayitlar HATLARA
 * ayrilir (chain_name). Bu iki bilgi listede gorunmez: "Crystal, 90'lar
 * dizisinin alternatif versiyonudur" cumlesi detay sayfasinda bir satir,
 * kronolojide ise yalnizca "Diger Zincir 1" sekmesidir - iki hat
 * arasindaki bag CIZILMEZ.
 *
 * Bu dosya seriyi iki boyutta cizer: SATIR = HAT (zincir adi), SUTUN =
 * YAYIN SIRASI (release_date). Her kayit bir kutu, her iliski bir cizgi.
 * `sequel` duz ok (izleme sirasi), oteki turler kesikli egri. Kurator
 * hicbir ek veri girmez - sema zaten kurulmus iliskilerden ve zincir
 * adlarindan TURETILIR; "otomatik sema" tam olarak budur.
 *
 * NEDEN SAF SVG, NEDEN KUTUPHANE YOK
 *
 * Genel bir graf yerlesimi (force-directed vb.) burada gereksizdir:
 * verinin iki dogal ekseni zaten var (hat, tarih) ve kutularin yeri bu
 * iki eksenden BELIRLENIR, hesaplanmaz. Bir kutuphane (Mermaid ~3 MB)
 * hem agirdi hem de yerlesimi kendisi secerdi - "TV dizileri ustte,
 * filmler altta" diyemezdik. PHP SVG etiketlerini yazar, sayfa hazir
 * gelir; JS yok, ek indirme yok, projenin tek dis kaynagi Font Awesome
 * olarak kalir.
 *
 * TURE GORE KOD YOK
 *
 * Cizim kurali turden bagimsizdir: `sequel` (tek sirali tur, 1.1.40)
 * yatay duz ok; geri kalan HER tur satirlar arasi kesikli egri. Tur
 * adi yalnizca CSS sinifina (sg-edge-<type>) ve lejant etiketine
 * (relation.type.<type>) gider. Sozluge yeni bir tur eklendiginde
 * (1.1.47'nin iki turu boyle geldi; YAPILACAKLAR'daki `special` boyle
 * gelecek) sema onu kendiliginden cizer; istege bagli olarak CSS'e bir
 * renk satiri eklenir, yoksa varsayilan gri kesikli kullanilir.
 *
 * OK YONU - TEK KURAL
 *
 * Bir satir "from, to'nun <turu>dur" diye okunur (relation_helpers.php).
 * Asimetrik turlerde ok TURETILMIS ISE bakar, yani `from` ucuna: oncul ->
 * devam, ana hikaye -> yan hikaye, tam hikaye -> ozet. Boylece zincir
 * soldan saga izleme sirasinda akar ve oteki oklar da ayni cumleyi
 * kurar: "bundan su turedi". Simetrik turlerde ok yoktur.
 *
 * KAPSAM
 *
 * Seri adi grubu (series_name) + o gruba DOGRUDAN iliskiyle bagli dis
 * kayitlar. Dis kayit (baska seri ya da serisiz) soluk "hayalet" kutu
 * olarak en alt satirda durur: Lunlun <-> Hua Xianzi gibi seri sinirini
 * asan bir bag gorunur kalir, ama o kaydin kendi serisi buraya
 * TASINMAZ (hayaletin hayaleti cizilmez). Hayalet, kendi serisi varsa
 * onun semasina, yoksa detayina baglanir.
 *
 * SATIRLAR
 *
 *   1..N  getSeriesChains() zincirleri (adli ya da en az 2 halkali),
 *         eskiden yeniye. Etiket: zincir adi, adsizsa "Zincir N".
 *   +1    Hicbir zincire girmeyen grup uyeleri ("Bagimsiz") - varsa.
 *   +1    Hayaletler ("Seri disi") - varsa.
 *
 * SUTUNLAR
 *
 * Kutular tarihe gore siralanir (tarihsiz sona). Ayni YILDA cikan iki
 * kayit FARKLI satirlardaysa tek sutunu paylasir (TV sezonu + ayni yilin
 * filmi yan yana degil, alt alta durur); ayni satirdaysa yeni sutun
 * acilir. Bu, gercek zaman olcegi ile sirali dizilim arasinda bir orta
 * yoldur: yillar soldan saga artar, ust uste binme olmaz, genislik
 * kayit sayisiyla sinirli kalir. Yil etiketi sutun basinda yalnizca
 * degistiginde yazilir.
 *
 * +18 MASKESI: kutu kalir, baslik sizmaz (adult_mask_related, 1.1.2).
 * Konu spoiler kapisi burada yoktur: sema yalnizca kronolojinin zaten
 * gosterdigi basliklari gosterir.
 *
 * Kullanim (series_timeline.php, mode=graph):
 *
 *   $graph = series_graph_build($pdo, $seriesName, $anchorId);
 *   echo series_graph_render($graph);   // <div class="sg-wrap">...svg...lejant</div>
 */

// =====================================================================
// Geometry - one place for every number the drawing uses
// =====================================================================

/**
 * Cizim sabitleri. Tek yerde dursun; series_timeline.php'nin CSS'i bu
 * olculere GUVENMEZ (svg kendi width/height'ini tasir).
 *
 * @return array<string,int>
 */
function series_graph_geometry() {
    return [
        'label_w' => 150,  // satir etiketi sutunu
        'col_w'   => 176,  // bir sutun
        'node_w'  => 156,  // kutu
        'node_h'  => 60,
        'row_h'   => 96,   // bir satir
        'top'     => 44,   // yil ekseni (ustten kemerler icin pay)
        'pad_b'   => 16,   // alt bosluk
        'poster_w'=> 36,
        'poster_h'=> 52,
    ];
}

// =====================================================================
// Building the graph
// =====================================================================

/**
 * Bir serinin semasini kur: dugumler, kenarlar, satirlar, sutunlar.
 *
 * @param PDO    $pdo
 * @param string $series_name
 * @param int    $anchor_id   Vurgulanacak (istekteki) anime.
 * @return array{
 *   nodes: array<int,array>, edges: array<int,array>, rows: array<int,array>,
 *   cols: array<int,array>, types: string[], statuses: string[],
 *   group_count: int, ghost_count: int
 * }
 */
function series_graph_build($pdo, $series_name, $anchor_id) {
    $empty = [
        'nodes' => [], 'edges' => [], 'rows' => [], 'cols' => [],
        'types' => [], 'statuses' => [], 'group_count' => 0, 'ghost_count' => 0,
    ];
    if (empty($series_name)) {
        return $empty;
    }
    $anchor_id = (int)$anchor_id;

    // --- 1. Grup uyeleri -------------------------------------------------
    $stmt = $pdo->prepare("SELECT id FROM animes WHERE series_name = ? ORDER BY id ASC");
    $stmt->execute([$series_name]);
    $groupIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    $stmt->closeCursor();
    if (empty($groupIds)) {
        return $empty;
    }
    $inGroup = array_flip($groupIds);

    // --- 2. Gruba dokunan her iliski ---------------------------------------
    // Iki uc da grupta -> ic kenar. Tek uc grupta -> obur uc HAYALET olur.
    $ph = implode(',', array_fill(0, count($groupIds), '?'));
    $stmt = $pdo->prepare("
        SELECT id, from_anime_id, to_anime_id, relation_type
          FROM anime_relations
         WHERE from_anime_id IN ($ph) OR to_anime_id IN ($ph)
         ORDER BY id ASC
    ");
    $stmt->execute(array_merge($groupIds, $groupIds));
    $relRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $ghostIds = [];
    foreach ($relRows as $r) {
        $f = (int)$r['from_anime_id'];
        $t = (int)$r['to_anime_id'];
        if (!isset($inGroup[$f])) { $ghostIds[$f] = true; }
        if (!isset($inGroup[$t])) { $ghostIds[$t] = true; }
    }
    $ghostIds = array_keys($ghostIds);

    // --- 3. Gosterim satirlari (tek sorgu, +18 maskesi) --------------------
    $nodes = [];
    foreach (seriesRowsByIds($pdo, array_merge($groupIds, $ghostIds)) as $row) {
        $row   = adult_mask_related($row, 'is_adult', 'title', 'alternative_titles');
        $nid   = (int)$row['id'];
        $ghost = !isset($inGroup[$nid]);
        $fullTitle = display_title($row);
        $nodes[$nid] = [
            'id'           => $nid,
            'title'        => $fullTitle,
            // Kutuda seri adi oneki dusurulur ("Tensei Shitara Slime Datta
            // Ken: Tensura Nikki" -> "Tensura Nikki"); tam ad tooltip'te.
            'short'        => $ghost ? $fullTitle : series_graph_short_title($fullTitle, $series_name),
            'image_path'   => $row['image_path'] ?? '',
            'media_type'   => $row['media_type'] ?? 'TV',
            'watch_status' => $row['watch_status'] ?? '',
            'release_date' => $row['release_date'] ?? null,
            'precision'    => $row['release_date_precision'] ?? 'full',
            'chain_name'   => $row['chain_name'] ?? null,
            'series_name'  => $row['series_name'] ?? null,
            'ghost'        => $ghost,
            'anchor'       => ($nid === $anchor_id),
            'row'          => null,
            'col'          => null,
        ];
    }

    // --- 4. Satirlar: zincirler -> bagimsizlar -> hayaletler ----------------
    $rows     = [];
    $assigned = [];
    $unnamed  = 0;
    foreach (getSeriesChains($pdo, $series_name) as $chainInfo) {
        $name = $chainInfo['name'] ?? null;
        if ($name !== null) {
            $label = $name;
        } else {
            $unnamed++;
            $label = sprintf(t('series_graph.row.chain'), $unnamed);
        }
        $rowIdx = count($rows);
        $rows[] = ['label' => $label, 'kind' => 'chain', 'count' => 0];
        foreach ($chainInfo['ids'] as $cid) {
            $cid = (int)$cid;
            if (isset($nodes[$cid]) && !isset($assigned[$cid])) {
                $nodes[$cid]['row'] = $rowIdx;
                $assigned[$cid] = true;
                $rows[$rowIdx]['count']++;
            }
        }
    }

    $looseIdx = null;
    foreach ($groupIds as $gid) {
        if (isset($nodes[$gid]) && !isset($assigned[$gid])) {
            if ($looseIdx === null) {
                $looseIdx = count($rows);
                $rows[] = ['label' => t('series_graph.row.unassigned'), 'kind' => 'loose', 'count' => 0];
            }
            $nodes[$gid]['row'] = $looseIdx;
            $assigned[$gid] = true;
            $rows[$looseIdx]['count']++;
        }
    }

    $ghostIdx = null;
    foreach ($ghostIds as $hid) {
        if (isset($nodes[$hid])) {
            if ($ghostIdx === null) {
                $ghostIdx = count($rows);
                $rows[] = ['label' => t('series_graph.row.outside'), 'kind' => 'ghost', 'count' => 0];
            }
            $nodes[$hid]['row'] = $ghostIdx;
            $rows[$ghostIdx]['count']++;
        }
    }

    // --- 5. Sutunlar: tarihe gore, ayni yil + farkli satir = ayni sutun ------
    $order = array_values($nodes);
    usort($order, function ($a, $b) {
        $da = series_graph_sort_key($a);
        $db = series_graph_sort_key($b);
        if ($da !== $db) { return strcmp($da, $db); }
        return $a['id'] - $b['id'];
    });

    $cols = [];
    $cur  = null; // ['year' => string, 'rows' => array<int,bool>]
    foreach ($order as $n) {
        $year = series_graph_year_label($n);
        if ($cur === null || $cur['year'] !== $year || isset($cur['rows'][$n['row']])) {
            $cols[] = ['year' => $year];
            $cur = ['year' => $year, 'rows' => []];
        }
        $cur['rows'][$n['row']] = true;
        $nodes[$n['id']]['col'] = count($cols) - 1;
    }

    // --- 6. Kenarlar --------------------------------------------------------
    $edges    = [];
    $types    = [];
    $statuses = [];
    foreach ($relRows as $r) {
        $f = (int)$r['from_anime_id'];
        $t = (int)$r['to_anime_id'];
        if (!isset($nodes[$f]) || !isset($nodes[$t])) {
            continue; // uc silinmis; FK cascade normalde buna izin vermez
        }
        $type = (string)$r['relation_type'];
        $edges[] = [
            'id'   => (int)$r['id'],
            'from' => $f,
            'to'   => $t,
            'type' => $type,
        ];
        $types[$type] = true;
    }
    foreach ($nodes as $n) {
        $statuses[watch_status_css_class($n['watch_status'])] = true;
    }

    // Lejant sirasi sozlugun sirasidir; sozlukte olmayan (eski dosya, yeni
    // enum) bir tur sona duser - cizilir, "Diger Iliski" diye etiketlenir.
    $orderedTypes = [];
    foreach (anime_relation_types() as $kt) {
        if (isset($types[$kt])) { $orderedTypes[] = $kt; unset($types[$kt]); }
    }
    foreach (array_keys($types) as $kt) { $orderedTypes[] = $kt; }

    return [
        'nodes'       => $nodes,
        'edges'       => $edges,
        'rows'        => $rows,
        'cols'        => $cols,
        'types'       => $orderedTypes,
        'statuses'    => array_keys($statuses),
        'group_count' => count($groupIds),
        'ghost_count' => count($ghostIds),
    ];
}

/**
 * Siralama anahtari: tarihsizler sona ('9999'), bozuk tarih de oyle.
 * Parcali tarih (1.1.31) yil hassasiyetinde yine yilina gore dizilir.
 */
function series_graph_sort_key(array $n) {
    $iso = substr((string)($n['release_date'] ?? ''), 0, 10);
    if (date_precision_normalize($n['precision'] ?? 'full') === 'none'
        || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $iso) || $iso === '0000-00-00') {
        return '9999-99-99';
    }
    return $iso;
}

/** Sutun basligi: yil ya da '?'. */
function series_graph_year_label(array $n) {
    $k = series_graph_sort_key($n);
    return $k === '9999-99-99' ? '?' : substr($k, 0, 4);
}

// =====================================================================
// Rendering
// =====================================================================

/**
 * Basligi kutuya sigacak satirlara bol (SVG <text> sarmaz).
 *
 * Kelime sinirinda sarar; tek kelime sigmazsa keser. En fazla $maxLines
 * satir; tasarsa son satirin sonuna "…" konur. mb_* ile - basliklar
 * Japonca/Turkce karakter tasir.
 *
 * @return string[]
 */
function series_graph_wrap($text, $maxChars = 16, $maxLines = 2) {
    $text  = trim((string)$text);
    $words = preg_split('/\s+/u', $text) ?: [];
    $lines = [];
    $cur   = '';
    foreach ($words as $w) {
        if ($w === '') { continue; }
        $try = $cur === '' ? $w : $cur . ' ' . $w;
        if (mb_strlen($try) <= $maxChars) {
            $cur = $try;
            continue;
        }
        if ($cur !== '') {
            $lines[] = $cur;
            $cur = '';
        }
        // Tek kelime sigmiyorsa parcala.
        while (mb_strlen($w) > $maxChars) {
            $lines[] = mb_substr($w, 0, $maxChars);
            $w = mb_substr($w, $maxChars);
        }
        $cur = $w;
    }
    if ($cur !== '') { $lines[] = $cur; }

    if (count($lines) > $maxLines) {
        $lines = array_slice($lines, 0, $maxLines);
        $last  = $lines[$maxLines - 1];
        $lines[$maxLines - 1] = mb_substr($last, 0, max(1, $maxChars - 1)) . '…';
    }
    return $lines;
}

/**
 * Kutu basligi: seri adi onekini dusur.
 *
 * Bir serinin her uyesi ayni uzun onekle baslar ("Tensei Shitara Slime
 * Datta Ken (2021)", "... : Tensura Nikki"); 16 karakterlik kutuda hepsi
 * "Tensei Shitara S…" olur ve sema okunmaz. Seri adi (buyuk/kucuk harf
 * farki yok, basta olmasi sart degil) ve ardindan gelen ayirici (: - –
 * bosluk) atilir; geriye bir sey kalmiyorsa (ilk sezon, adi serinin
 * kendisi) tam ad kalir.
 * Parantezli kalan "(2021)" gibi ise parantez de acilir.
 *
 * Yalnizca gosterim: tooltip ve baglantilar tam adi tasir.
 */
function series_graph_short_title($title, $series_name) {
    $title = trim((string)$title);
    $sn    = trim((string)$series_name);
    if ($sn === '' || mb_strlen($title) <= mb_strlen($sn)) {
        return $title;
    }
    // Onek basta olmayabilir: "Gekijouban <seri>: Guren no Kizuna-hen".
    // Seri adinin ARKASINDAKI parca alinir; medya turu zaten kutunun alt
    // satirinda ("Film · 2022") yazar, "Gekijouban" kaybolmaz.
    $pos = mb_stripos($title, $sn);
    if ($pos === false) {
        return $title;
    }
    $rest = trim(mb_substr($title, $pos + mb_strlen($sn)));
    $rest = trim(preg_replace('/^[\s:\-–—]+/u', '', $rest));
    if ($rest === '') {
        return $title;
    }
    if (preg_match('/^\(([^()]+)\)$/u', $rest, $m)) {
        $rest = trim($m[1]);
    }
    return $rest;
}

/**
 * Kenar renkleri. TURE OZEL TEK YER - ve yalnizca renk. Bilinmeyen tur
 * varsayilan griye duser; yani yeni bir tur icin buraya satir eklemek
 * ISTEGE BAGLIDIR. Ayni degerler series_timeline.php'nin CSS'inde de
 * durur (cizgi deseni orada); burasi yalnizca ok ucu (marker) icindir,
 * cunku marker rengi CSS'ten miras almaz.
 */
function series_graph_edge_color($type) {
    static $map = [
        'sequel'              => '#8e44ad',
        'alternative_version' => '#16a085',
        'alternative_setting' => '#16a085',
        'side_story'          => '#e67e22',
        'summary'             => '#e67e22',
        'same_setting'        => '#2980b9',
        'character'           => '#2980b9',
        'other'               => '#95a5a6',
    ];
    return $map[$type] ?? '#95a5a6';
}

/** CSS sinifi icin guvenli tur adi (enum degerleri zaten [a-z_] ama yine de). */
function series_graph_type_class($type) {
    $t = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$type));
    return $t === '' ? 'other' : $t;
}

/**
 * Semanin tamamini bas: kaydirilabilir SVG + lejant (+ bos durum notu).
 *
 * @param array $graph series_graph_build() sonucu
 * @return string HTML
 */
function series_graph_render(array $graph) {
    if (empty($graph['nodes'])) {
        return '<p class="sg-empty">' . htmlspecialchars(t('series_graph.empty'), ENT_QUOTES, 'UTF-8') . '</p>';
    }
    $html  = '<div class="sg-wrap">';
    $html .= '<div class="sg-scroll">' . series_graph_svg($graph) . '</div>';
    $html .= series_graph_legend($graph);
    if (empty($graph['edges'])) {
        $html .= '<p class="sg-note">' . htmlspecialchars(t('series_graph.no_relations'), ENT_QUOTES, 'UTF-8') . '</p>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Yalnizca <svg>.
 *
 * Katman sirasi: satir bantlari ve yil cizgileri (arka), kenarlar
 * (orta), kutular (on) - kutu her zaman cizginin ustunde okunur.
 */
function series_graph_svg(array $graph) {
    $g   = series_graph_geometry();
    $h   = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
    $fmt = function ($v) { return rtrim(rtrim(number_format((float)$v, 1, '.', ''), '0'), '.'); };

    $nCols = max(1, count($graph['cols']));
    $nRows = max(1, count($graph['rows']));
    $W = $g['label_w'] + $nCols * $g['col_w'];
    $H = $g['top'] + $nRows * $g['row_h'] + $g['pad_b'];

    // Kutu koseleri - kenar uclari icin.
    $nodeX = function ($n) use ($g) { return $g['label_w'] + $n['col'] * $g['col_w'] + ($g['col_w'] - $g['node_w']) / 2; };
    $nodeY = function ($n) use ($g) { return $g['top'] + $n['row'] * $g['row_h'] + ($g['row_h'] - $g['node_h']) / 2; };

    $out  = '<svg class="sg-svg" xmlns="http://www.w3.org/2000/svg" width="' . $W . '" height="' . $H . '" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="' . $h(t('series_graph.aria')) . '">';

    // --- markers: yalniz mevcut asimetrik turler icin ---
    $out .= '<defs>';
    foreach ($graph['types'] as $type) {
        if (anime_relation_symmetric($type)) { continue; }
        $out .= '<marker id="sg-arrow-' . $h(series_graph_type_class($type)) . '" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse">'
              . '<path d="M 0 0 L 10 5 L 0 10 z" fill="' . $h(series_graph_edge_color($type)) . '"/></marker>';
    }
    $out .= '</defs>';

    // --- arka: satir bantlari + etiketleri ---
    foreach ($graph['rows'] as $ri => $row) {
        $y = $g['top'] + $ri * $g['row_h'];
        $out .= '<rect class="sg-band' . ($ri % 2 ? ' sg-band-alt' : '') . ' sg-band-' . $h($row['kind']) . '" x="0" y="' . $y . '" width="' . $W . '" height="' . $g['row_h'] . '"/>';
        $labelLines = series_graph_wrap($row['label'], 17, 2);
        $ly = $y + $g['row_h'] / 2 - (count($labelLines) - 1) * 7;
        $out .= '<text class="sg-rowlabel sg-rowlabel-' . $h($row['kind']) . '" x="12" y="' . $fmt($ly) . '">';
        $out .= '<title>' . $h($row['label']) . '</title>';
        foreach ($labelLines as $li => $line) {
            $out .= '<tspan x="12" dy="' . ($li === 0 ? '0.35em' : '1.2em') . '">' . $h($line) . '</tspan>';
        }
        $out .= '</text>';
    }

    // --- arka: yil ekseni ---
    $prevYear = null;
    foreach ($graph['cols'] as $ci => $col) {
        $x = $g['label_w'] + $ci * $g['col_w'];
        if ($col['year'] !== $prevYear) {
            $out .= '<line class="sg-yearline" x1="' . $x . '" y1="' . $g['top'] . '" x2="' . $x . '" y2="' . ($H - $g['pad_b']) . '"/>';
            $out .= '<text class="sg-year" x="' . ($x + 8) . '" y="22">' . $h($col['year']) . '</text>';
            $prevYear = $col['year'];
        }
    }

    // Ayni satirda iki kutu ARASINDA baska kutu var mi? Varsa duz cizgi
    // aradaki kutunun altindan gecer ve "62 -> 63" gibi okunurdu; boyle
    // kenarlar kutularin ustunden kemerle gecer.
    $rowCols = [];
    foreach ($graph['nodes'] as $n) {
        $rowCols[$n['row']][$n['col']] = true;
    }
    $blocked = function ($a, $b) use ($rowCols) {
        if ($a['row'] !== $b['row']) { return false; }
        $lo = min($a['col'], $b['col']) + 1;
        $hi = max($a['col'], $b['col']) - 1;
        for ($c = $lo; $c <= $hi; $c++) {
            if (isset($rowCols[$a['row']][$c])) { return true; }
        }
        return false;
    };

    // --- orta: kenarlar ---
    foreach ($graph['edges'] as $e) {
        $a = $graph['nodes'][$e['from']]; // turetilmis is ("from, to'nun <turu>dur")
        $b = $graph['nodes'][$e['to']];
        $type  = $e['type'];
        $cls   = 'sg-edge sg-edge-' . series_graph_type_class($type);
        $sym   = anime_relation_symmetric($type);
        $label = anime_relation_type_label($type);
        if (!$sym) { $label .= ' / ' . anime_relation_type_label($type, true); }
        $title = $b['title'] . ($sym ? ' ↔ ' : ' → ') . $a['title'] . ' — ' . $label;

        $ax = $nodeX($a); $ay = $nodeY($a);
        $bx = $nodeX($b); $by = $nodeY($b);

        if ($type === 'sequel' && $blocked($a, $b)) {
            // Ayni satir, arada kutu: ustten kemer, ok hedefin tepesine iner.
            $x1 = $bx + $g['node_w'] / 2; $y1 = $by;
            $x2 = $ax + $g['node_w'] / 2; $y2 = $ay;
            $lift = 30 + 6 * (abs($a['col'] - $b['col']) - 1);
            $d = 'M ' . $fmt($x1) . ' ' . $fmt($y1)
               . ' C ' . $fmt($x1) . ' ' . $fmt($y1 - $lift) . ', ' . $fmt($x2) . ' ' . $fmt($y2 - $lift) . ', ' . $fmt($x2) . ' ' . $fmt($y2);
        } elseif ($type === 'sequel') {
            // Yatay: oncul (to) -> devam (from). Ayni satirda duz, farkli
            // satirda yatay tegetli S egrisi.
            $dir = ($a['col'] >= $b['col']) ? 1 : -1;
            $x1 = $bx + ($dir > 0 ? $g['node_w'] : 0); $y1 = $by + $g['node_h'] / 2;
            $x2 = $ax + ($dir > 0 ? 0 : $g['node_w']); $y2 = $ay + $g['node_h'] / 2;
            if ($a['row'] === $b['row'] && abs($x2 - $x1) > 1) {
                $d = 'M ' . $fmt($x1) . ' ' . $fmt($y1) . ' L ' . $fmt($x2) . ' ' . $fmt($y2);
            } else {
                $bend = max(40, abs($x2 - $x1) / 2) * $dir;
                $d = 'M ' . $fmt($x1) . ' ' . $fmt($y1)
                   . ' C ' . $fmt($x1 + $bend) . ' ' . $fmt($y1) . ', ' . $fmt($x2 - $bend) . ' ' . $fmt($y2) . ', ' . $fmt($x2) . ' ' . $fmt($y2);
            }
        } else {
            // Dikey: ust kutunun altindan alt kutunun ustune; ayni satirda
            // ustten kemer. Ok (varsa) turetilmis ise, yani `from`a bakar.
            if ($a['row'] === $b['row']) {
                $x1 = $bx + $g['node_w'] / 2; $y1 = $by;
                $x2 = $ax + $g['node_w'] / 2; $y2 = $ay;
                $lift = 34 + 6 * max(0, abs($a['col'] - $b['col']) - 1);
                $d = 'M ' . $fmt($x1) . ' ' . $fmt($y1)
                   . ' C ' . $fmt($x1) . ' ' . $fmt($y1 - $lift) . ', ' . $fmt($x2) . ' ' . $fmt($y2 - $lift) . ', ' . $fmt($x2) . ' ' . $fmt($y2);
            } else {
                $bDown = ($b['row'] < $a['row']); // b ustte mi
                $x1 = $bx + $g['node_w'] / 2; $y1 = $by + ($bDown ? $g['node_h'] : 0);
                $x2 = $ax + $g['node_w'] / 2; $y2 = $ay + ($bDown ? 0 : $g['node_h']);
                $bend = ($bDown ? 1 : -1) * max(28, abs($y2 - $y1) / 2.5);
                $d = 'M ' . $fmt($x1) . ' ' . $fmt($y1)
                   . ' C ' . $fmt($x1) . ' ' . $fmt($y1 + $bend) . ', ' . $fmt($x2) . ' ' . $fmt($y2 - $bend) . ', ' . $fmt($x2) . ' ' . $fmt($y2);
            }
        }
        $marker = $sym ? '' : ' marker-end="url(#sg-arrow-' . $h(series_graph_type_class($type)) . ')"';
        $out .= '<path class="' . $h($cls) . '" d="' . $d . '"' . $marker . '><title>' . $h($title) . '</title></path>';
    }

    // --- on: kutular ---
    foreach ($graph['nodes'] as $n) {
        $x = $nodeX($n); $y = $nodeY($n);
        $ws = watch_status_css_class($n['watch_status']);
        $cls = 'sg-node is-' . $ws . ($n['anchor'] ? ' is-current' : '') . ($n['ghost'] ? ' is-ghost' : '');
        if ($n['ghost'] && !empty($n['series_name'])) {
            $href = 'series_timeline.php?id=' . $n['id'] . '&amp;mode=graph';
        } else {
            $href = 'anime_details.php?id=' . $n['id'];
        }
        $year  = series_graph_year_label($n);
        $meta  = $n['media_type'] . ' · ' . $year;
        $lines = series_graph_wrap($n['short'], 16, 2);

        $out .= '<a href="' . $href . '" class="' . $h($cls) . '">';
        $ghostSeries = ($n['ghost'] && !empty($n['series_name']) && mb_strtolower($n['series_name']) !== mb_strtolower($n['title']))
            ? ' (' . $n['series_name'] . ')' : '';
        $out .= '<title>' . $h($n['title'] . $ghostSeries) . '</title>';
        $out .= '<rect class="sg-box" x="' . $fmt($x) . '" y="' . $fmt($y) . '" width="' . $g['node_w'] . '" height="' . $g['node_h'] . '" rx="7"/>';
        $out .= '<rect class="sg-stripe" x="' . $fmt($x) . '" y="' . $fmt($y + 4) . '" width="4" height="' . ($g['node_h'] - 8) . '" rx="2"/>';
        $out .= '<image class="sg-poster" href="' . $h(poster_src($n['image_path'])) . '" x="' . $fmt($x + 10) . '" y="' . $fmt($y + ($g['node_h'] - $g['poster_h']) / 2) . '" width="' . $g['poster_w'] . '" height="' . $g['poster_h'] . '" preserveAspectRatio="xMidYMid slice"/>';
        $tx = $x + 10 + $g['poster_w'] + 8;
        $ty = $y + (count($lines) === 1 ? 26 : 20);
        $out .= '<text class="sg-title" x="' . $fmt($tx) . '" y="' . $fmt($ty) . '">';
        foreach ($lines as $li => $line) {
            $out .= '<tspan x="' . $fmt($tx) . '" dy="' . ($li === 0 ? '0' : '1.25em') . '">' . $h($line) . '</tspan>';
        }
        $out .= '</text>';
        $out .= '<text class="sg-meta" x="' . $fmt($tx) . '" y="' . $fmt($y + $g['node_h'] - 9) . '">' . $h($meta) . '</text>';
        $out .= '</a>';
    }

    $out .= '</svg>';
    return $out;
}

/**
 * Lejant: semada GERCEKTEN bulunan kenar turleri + kutu durum renkleri.
 * Bulunmayan tur listelenmez - lejant semanin ozetidir, sozlugun degil.
 */
function series_graph_legend(array $graph) {
    $h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
    $out = '<div class="sg-legend">';

    if (!empty($graph['types'])) {
        $out .= '<ul class="sg-legend-edges">';
        foreach ($graph['types'] as $type) {
            $cls   = 'sg-edge sg-edge-' . series_graph_type_class($type);
            $sym   = anime_relation_symmetric($type);
            $label = anime_relation_type_label($type);
            if (!$sym) { $label .= ' / ' . anime_relation_type_label($type, true); }
            $marker = $sym ? '' : ' marker-end="url(#sg-arrow-' . $h(series_graph_type_class($type)) . ')"';
            $out .= '<li><svg class="sg-legend-swatch" width="44" height="12" viewBox="0 0 44 12" aria-hidden="true">'
                  . '<path class="' . $h($cls) . '" d="M 1 6 L ' . ($sym ? '43' : '34') . ' 6"' . $marker . '/></svg>'
                  . '<span>' . $h($label) . '</span></li>';
        }
        $out .= '</ul>';
        $out .= '<p class="sg-legend-hint">' . $h(t('series_graph.legend.arrow')) . '</p>';
    }

    if (!empty($graph['statuses'])) {
        $out .= '<ul class="sg-legend-status">';
        foreach ($graph['statuses'] as $ws) {
            $out .= '<li><span class="sg-dot is-' . $h($ws) . '"></span><span>' . $h(watch_status_label_from_class($ws)) . '</span></li>';
        }
        if ($graph['ghost_count'] > 0) {
            $out .= '<li><span class="sg-dot is-ghost"></span><span>' . $h(t('series_graph.row.outside')) . '</span></li>';
        }
        $out .= '</ul>';
    }

    $out .= '</div>';
    return $out;
}

/**
 * CSS son ekinden etikete geri don (lejant icin). watch_status_css_class()
 * tersine cevrilir; 'unselected' ve bilinmeyen deger bos duruma duser.
 */
function watch_status_label_from_class($cls) {
    static $rev = [
        'watched'     => 'Watched',
        'watching'    => 'Watching',
        'plantowatch' => 'PlanToWatch',
        'onhold'      => 'OnHold',
        'dropped'     => 'Dropped',
    ];
    return watch_status_label($rev[$cls] ?? '');
}
