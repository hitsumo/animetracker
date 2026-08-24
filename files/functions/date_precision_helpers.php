<?php

/**
 * Anime Tracker - Kismi (parcasi bilinmeyen) tarih yardimcilari
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * 1.1.31. Bir animenin yayin/bitis tarihinin GUNU, bazen de AYI bilinmez -
 * ozellikle eski yapimlarda kaynaklar yalnizca yili verir. Surum oncesinde
 * form yalnizca tam tarih kabul ediyordu, yani "1979" bilgisi ya tahmin
 * edilmis bir gunle (1979-01-01, ekranda 01.01.1979 diye YANLIS okunur) ya
 * da hic girilmeden kaliyordu.
 *
 * COZUM: tarih DATE kolonunda durmaya devam eder, yaninda bir HASSASIYET
 * kolonu tasir. Bilinmeyen parcalar depoda 01 yazilir, EKRANDA "??" basilir:
 *
 *   full   2005-04-08  ->  08.04.2005    (bugunku davranis, on tanimli)
 *   month  2005-04-01  ->  ??.04.2005
 *   year   2005-01-01  ->  ??.??.2005
 *   none   NULL        ->  ??.??.????
 *
 * NEDEN AYRI KOLON, NEDEN '2005-00-00' DEGIL: MySQL 5.7+ ve MySQL 8'in on
 * tanimli sql_mode'u NO_ZERO_IN_DATE tasir, yani sifir parcali tarih yazma
 * denemesi hata verir (MariaDB'de gecse bile kurulumlar arasi tasinmaz olur).
 * Kolonu VARCHAR yapmak ise YEAR() filtresini (index.php yil suzgeci),
 * ORDER BY release_date siralamasini (seri kronolojisi) ve tarih
 * aritmetigini (geri sayim) bir anda bozardi. Ayri hassasiyet kolonu bu
 * uclusunu de oldugu gibi birakir: yalnizca yili bilinen bir anime kendi
 * yilinin ocak ayinda siralanir, dogru yil suzgecinde gorunur.
 *
 * "none" ile BOS tarih ayni sey DEGILDIR:
 *   - tarih NULL + hassasiyet 'full'  = HENUZ GIRILMEDI -> "Belirtilmemis"
 *   - tarih NULL + hassasiyet 'none'  = GERCEKTEN BILINMIYOR -> "??.??.????"
 * Kurator boylece doldurulmayi bekleyen kayitla, kaynagi olmayan kaydi
 * birbirinden ayirabilir.
 */

/**
 * Gecerli hassasiyet degerleri (DB enum'u ile birebir ayni sira).
 *
 * @return string[]
 */
function date_precision_values()
{
    return ['full', 'month', 'year', 'none'];
}

/**
 * Bir hassasiyet degerini guvenli hale getir.
 *
 * Taninmayan / bos / NULL her sey 'full'e duser. Bu bilincli bir secim:
 * 1.1.31 oncesi yazilmis her satirda kolon ya yoktur (eski katalog JSON'i,
 * eski istemci POST'u) ya da varsayilan degerdedir, ve o satirlarin tamami
 * tam tarih anlamina gelir.
 *
 * @param mixed $precision
 * @return string 'full' | 'month' | 'year' | 'none'
 */
function date_precision_normalize($precision)
{
    $p = strtolower(trim((string)$precision));
    return in_array($p, date_precision_values(), true) ? $p : 'full';
}

/**
 * Form acilir kutusunun secenekleri (deger => cevrilmis etiket).
 *
 * broadcast_status_options() kalibinin esi: secenek listesinin tek sahibi
 * burasidir, iki form da (add_anime, edit_anime) bu listeyi basar.
 *
 * @return array<string,string>
 */
function date_precision_options()
{
    return [
        'full'  => t('add_anime.date_precision.full'),
        'month' => t('add_anime.date_precision.month'),
        'year'  => t('add_anime.date_precision.year'),
        'none'  => t('add_anime.date_precision.none'),
    ];
}

/**
 * Kismi tarihi ekrana basilacak metne cevir.
 *
 * Bicim uygulamanin her yerindeki gun.ay.yil duzenidir; degisen tek sey
 * bilinmeyen parcalarin yerine "??" gelmesidir. 'full' hassasiyetinde cikti
 * eski date('d.m.Y', strtotime($d)) cagrisiyla BIREBIR aynidir.
 *
 * Tarih bos ya da bozuksa $unsetText doner ('none' hassasiyeti bunun
 * disindadir - orada tarihin NULL olmasi zaten beklenen durumdur).
 *
 * @param mixed  $date      'YYYY-MM-DD' (saat tasiyabilir), NULL veya ''
 * @param mixed  $precision date_precision_normalize() ile suzulur
 * @param string $unsetText tarih yokken donecek metin
 * @return string
 */
function format_partial_date($date, $precision = 'full', $unsetText = '')
{
    $precision = date_precision_normalize($precision);

    if ($precision === 'none') {
        return '??.??.????';
    }

    // strtotime KULLANILMAZ: '0000-00-00' gibi bozuk bir degeri sessizce
    // baska bir tarihe cevirir. Bicimi dogrudan okumak, gosterilen tarihin
    // saklanan tarih oldugunu garanti eder.
    $iso = substr((string)$date, 0, 10);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m)) {
        return $unsetText;
    }
    // Eski kurulumlarda MySQL'in "sifir tarih"i ('0000-00-00') kalmis
    // olabilir. Bicim olarak gecerlidir ama "00.00.0000" diye basmak
    // anlamsizdir - bu bir tarih degil, tarihin yoklugudur.
    if ($m[1] === '0000' || $m[2] === '00' || $m[3] === '00') {
        return $unsetText;
    }

    if ($precision === 'year') {
        return '??.??.' . $m[1];
    }
    if ($precision === 'month') {
        return '??.' . $m[2] . '.' . $m[1];
    }
    return $m[3] . '.' . $m[2] . '.' . $m[1];
}

/**
 * Bu tarihin gosterilecek bir degeri var mi?
 *
 * "Bilinmiyor" ('none') bir DEGERDIR - satiri bastirir - ama tarih kolonu
 * NULL oldugu icin !empty($date) kontrolu onu yanlislikla eler. Tarih satiri
 * basip basmamaya karar veren her yer bu fonksiyonu kullanir.
 *
 * @param mixed $date
 * @param mixed $precision
 * @return bool
 */
function has_partial_date($date, $precision = 'full')
{
    if (date_precision_normalize($precision) === 'none') {
        return true;
    }
    return !empty($date);
}

/**
 * Saklanan tarihi + hassasiyeti forma dagitilacak uc parcaya ayir.
 *
 * Donen anahtarlar formdaki uc girdinin adlariyla eslesir:
 *   'date'  -> <input type="date"   name="<alan>">
 *   'month' -> <select             name="<alan>_month">   ('01'..'12')
 *   'year'  -> <input type="number" name="<alan>_year">
 * 'precision' ise acilir kutunun secili degeridir.
 *
 * @param mixed $date
 * @param mixed $precision
 * @return array{precision:string,date:string,month:string,year:string}
 */
function partial_date_form_parts($date, $precision = 'full')
{
    $precision = date_precision_normalize($precision);
    $out = ['precision' => $precision, 'date' => '', 'month' => '', 'year' => ''];

    $iso = substr((string)$date, 0, 10);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m)) {
        // Tarih yoksa hassasiyet 'none' olabilir (bilincli "bilinmiyor")
        // ya da 'full' (hic girilmemis); ikisinde de doldurulacak parca yok.
        return $out;
    }

    $out['year'] = $m[1];

    // Ay yalnizca GERCEKTEN bilindiginde secili gelir. 'year' hassasiyetinde
    // depodaki ay 01'dir ama bu bir veri degil, dolgu rakamidir; secili
    // gostermek kullanici "Ay ve yil"a gectiginde ona bilmedigi bir ayi
    // (Ocak) bilinmis gibi sunardi.
    if ($precision === 'full' || $precision === 'month') {
        $out['month'] = $m[2];
    }
    if ($precision === 'full') {
        $out['date'] = $iso;
    }
    return $out;
}

/**
 * Formdan gelen uclunun (hassasiyet + tarih/ay/yil) DB'ye yazilacak halini uret.
 *
 * Donen dizi:
 *   'date'      -> DATE kolonuna yazilacak deger ('YYYY-MM-DD' veya null)
 *   'precision' -> hassasiyet kolonuna yazilacak deger
 *   'error'     -> null | 'date' | 'year'  (cagiran taraf ceviri anahtarini secer)
 *
 * KURALLAR
 *  - Hassasiyet alani HIC gelmezse 'full' varsayilir: eski bir istemci ya da
 *    elle atilmis bir POST, bu surumden onceki davranisi aynen gorur.
 *  - Bos deger HATA DEGILDIR, "girilmedi"dir: hassasiyet 'full'e doner ve
 *    tarih NULL yazilir. Tek istisna 'none' - onun degeri zaten yoklugudur.
 *  - "Ay ve yil" secilip ay secilmemisse hassasiyet kendiliginden 'year'a
 *    duser. Kullaniciyi bilmedigi bir ayi secmeye zorlamak yerine, bildigi
 *    kadarini kaydeder.
 *
 * @param array  $post $_POST (ya da ayni sekilli bir dizi)
 * @param string $base 'release_date' | 'end_date'
 * @return array{date:?string,precision:string,error:?string}
 */
function partial_date_from_post($post, $base)
{
    $unset    = ['date' => null, 'precision' => 'full', 'error' => null];
    $precision = date_precision_normalize($post[$base . '_precision'] ?? 'full');

    if ($precision === 'none') {
        return ['date' => null, 'precision' => 'none', 'error' => null];
    }

    if ($precision === 'full') {
        $raw = trim((string)($post[$base] ?? ''));
        if ($raw === '') {
            return $unset;
        }
        // Tarayici hatalari (orn. 5 haneli yil 20026) ve elle POST'a karsi
        // 1.1.31 oncesinden beri var olan ayni kontrol.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return ['date' => null, 'precision' => 'full', 'error' => 'date'];
        }
        return ['date' => $raw, 'precision' => 'full', 'error' => null];
    }

    // 'month' ve 'year' dallarinin ikisi de once yili ister.
    $year = trim((string)($post[$base . '_year'] ?? ''));
    if ($year === '') {
        return $unset;
    }
    if (!preg_match('/^\d{4}$/', $year)) {
        return ['date' => null, 'precision' => 'full', 'error' => 'year'];
    }

    if ($precision === 'year') {
        return ['date' => $year . '-01-01', 'precision' => 'year', 'error' => null];
    }

    $month = trim((string)($post[$base . '_month'] ?? ''));
    if (!preg_match('/^(0[1-9]|1[0-2])$/', $month)) {
        return ['date' => $year . '-01-01', 'precision' => 'year', 'error' => null];
    }
    return ['date' => $year . '-' . $month . '-01', 'precision' => 'month', 'error' => null];
}

/**
 * Bir tarih alanini (hassasiyet kutusu + uc girdi) forma bas.
 *
 * Iki formun (add_anime, edit_anime) ayni bloktan iki kopya tasimasi
 * istenmedi: girdi adlari, veri nitelikleri ve baslangic gorunurlugu ayni
 * anda js/anime_form.js'in toggleDatePrecision() sozlesmesine bagli. Iki
 * kopya, er ya da gec birbirinden ayrilirdi. Cikti seo_head()/asset_styles()
 * gibi dogrudan yazilir.
 *
 * GORUNURLUK. Baslangicta yalniz secili hassasiyetin girdileri acik gelir;
 * JS kapali olsa bile form dogru calisir (kullanici hassasiyeti degistiremez
 * ama kayitli deger bozulmaz - sunucu her zaman secili hassasiyeti okur).
 * "Ay ve yil" ikisini birden gosterir; "yalniz yil" yalniz yil kutusunu;
 * "bilinmiyor" hicbirini.
 *
 * GIZLI GIRDI DE POST EDILIR. Bu bilerek boyle: sunucu tarafi
 * partial_date_from_post() yalnizca SECILI hassasiyetin ihtiyac duydugu
 * parcalari okur, digerleri gormezden gelinir.
 *
 * @param string $base      'release_date' | 'end_date'
 * @param string $labelText Cevrilmis etiket metni (ham, kacislanmamis)
 * @param mixed  $date      Kayitli tarih ya da null (yeni kayit)
 * @param mixed  $precision Kayitli hassasiyet ya da 'full'
 * @return void
 */
function render_partial_date_field($base, $labelText, $date = null, $precision = 'full')
{
    $parts = partial_date_form_parts($date, $precision);
    $p     = $parts['precision'];

    $showDate  = ($p === 'full');
    $showMonth = ($p === 'month');
    $showYear  = ($p === 'month' || $p === 'year');
    $hide      = function ($visible) { return $visible ? '' : ' style="display: none;"'; };
    $e         = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
    ?>
    <div class="form-group">
        <label for="<?php echo $e($base); ?>"><?php echo $e($labelText); ?></label>
        <div class="input-area">
            <div class="date-precision-group" data-date-base="<?php echo $e($base); ?>">
                <select name="<?php echo $e($base); ?>_precision"
                        id="<?php echo $e($base); ?>_precision"
                        class="date-precision-select"
                        data-date-base="<?php echo $e($base); ?>"
                        onchange="toggleDatePrecision(this)">
                    <?php foreach (date_precision_options() as $dp_value => $dp_label): ?>
                    <option value="<?php echo $e($dp_value); ?>"<?php echo $dp_value === $p ? ' selected' : ''; ?>><?php echo $e($dp_label); ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="date" name="<?php echo $e($base); ?>" id="<?php echo $e($base); ?>"
                       data-date-part="full"
                       value="<?php echo $e($parts['date']); ?>"<?php echo $hide($showDate); ?>>

                <select name="<?php echo $e($base); ?>_month" id="<?php echo $e($base); ?>_month"
                        data-date-part="month"<?php echo $hide($showMonth); ?>>
                    <option value=""><?php echo $e(t('add_anime.option.month')); ?></option>
                    <?php for ($mm = 1; $mm <= 12; $mm++):
                        $mv = sprintf('%02d', $mm); ?>
                    <option value="<?php echo $mv; ?>"<?php echo $parts['month'] === $mv ? ' selected' : ''; ?>><?php echo $mv; ?></option>
                    <?php endfor; ?>
                </select>

                <input type="number" name="<?php echo $e($base); ?>_year" id="<?php echo $e($base); ?>_year"
                       data-date-part="year" min="1900" max="2200" step="1"
                       placeholder="<?php echo $e(t('add_anime.ph.date_year')); ?>"
                       value="<?php echo $e($parts['year']); ?>"<?php echo $hide($showYear); ?>>
            </div>
            <small class="form-text text-muted"><?php echo $e(t('add_anime.hint.date_precision')); ?></small>
        </div>
    </div>
    <?php
}
