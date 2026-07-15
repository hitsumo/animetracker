<?php
/**
  [Anime Tracker/Anime izleme takip listesi.
    https://www.sicakcikolata.com]
  Copyright (C) 2025 [Okan Sumer]
 
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 2 as
  published by the Free Software Foundation.
 
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.
 
  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
  MA 02110-1301, USA.
*/

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Initialise the i18n layer (see lang_init() in functions.php).
lang_init($pdo);

// Editing an anime writes directly to the shared catalog (animes), so it is
// restricted to moderators and above (online only; no-op in self-host where
// the owner counts as admin). Regular users get a suggestion path instead
// once the suggestions flow lands.
require_role($pdo, 'moderator');

// Synopsis edit override (0.7.2): admin capability that lifts the Mode 2
// readonly lock on the catalog synopsis (TR/EN). Stored as a runtime
// settings key, toggled from admin_capabilities.php (admin-only, never
// shipped to clients). When ON, Mode 2 still shows the personal synopsis
// field but the catalog synopsis becomes editable instead of readonly,
// and the save path reads it from POST. Mode 1 is unaffected.
$synopsisEditOverride = (get_setting($pdo, 'synopsis_edit_override', '0') === '1');

// Anime ID'sini al
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Master genre list for the dropdown. Fetched via the helper so the
// rest of the page does not need to know which table the data lives
// in. Returns rows with id and name.
$genres = getAllGenres($pdo);

// Seri adlarini cek (datalist auto-complete icin)
$seriesNames = getAllSeriesNames($pdo);

// Tum cumleleri cek (oneri sistemi icin tag input auto-complete kaynagi)
$allTags = getAllTags($pdo);

// Anime bilgilerini cek
$stmt = $pdo->prepare('SELECT * FROM animes WHERE id = ?');
$stmt->execute([$id]);
$anime = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anime) {
    header('Location: index.php');
    exit;
}

// Kisisel izleme durumu artik user_anime'da (1.0.1). animes'ten gelen
// vestijyal kolonlarin uzerine mevcut kullanicinin user_anime degerlerini
// bindir; boylece hem asagidaki Mod 1/Mod 2 synopsis mantigi hem de form
// render kisisel veriyi dogru kaynaktan okur. user_synopsis(_en) icin
// NULL/''/deger ayrimi korunur (ua_get_state ham degeri doner; NULL =
// "o dil hala Katalog", '' = "bilincli silindi").
$uaState = ua_get_state($pdo, current_user_id(), $id);
$anime['watch_status']     = $uaState['watch_status'];
$anime['watched_episodes'] = $uaState['watched_episodes'];
$anime['notes']            = $uaState['notes'];
$anime['user_synopsis']    = $uaState['user_synopsis'];
$anime['user_synopsis_en'] = $uaState['user_synopsis_en'];
$anime['watch_start_date']  = $uaState['watch_start_date'];
$anime['watch_finish_date'] = $uaState['watch_finish_date'];

// "Siradaki anime" dropdown'u icin: tum animeleri cek (mevcut anime haric).
// series_name dolu ise ayni seridekiler basta gosterilir, diger animeler
// de listelenir cunku kullanici farkli bir seriye isaret etmek isteyebilir.
$allAnimesStmt = $pdo->prepare("
    SELECT id, title, series_name, media_type
    FROM animes
    WHERE id != ?
    ORDER BY
        CASE WHEN series_name = ? AND ? IS NOT NULL THEN 0 ELSE 1 END,
        title ASC
");
$allAnimesStmt->execute([(int)$id, $anime['series_name'], $anime['series_name']]);
$allAnimes = $allAnimesStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF kontrolu - form'dan gelen token oturumdaki ile eslesmiyorsa reddet.
    // hash_equals timing-safe karsilastirma yapar (bkz. functions.php csrf_verify).
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die(htmlspecialchars(t('add_anime.csrf.invalid'), ENT_QUOTES, 'UTF-8'));
    }

    // 1.1.8: admin-only "push ENTIRE catalog" action. A separate submit
    // button next to "Update" (rendered only for admins) sends
    // full_catalog_push=1. It ignores the edited form fields and forces a
    // full resync - the online-usable equivalent of admin_sync.php's
    // localhost-gated "Push to Server". The normal save now does a cheap
    // scoped (series) push; this is the escape hatch to resync everything
    // (incl. chronology). Branch BEFORE the update logic and redirect.
    // Server-side re-checks can('admin'); a non-admin who forges the field
    // falls through to the normal (moderator-gated) save.
    if (isset($_POST['full_catalog_push']) && MULTI_USER_MODE && can($pdo, 'admin')) {
        $fullOk = false;
        $fullCount = 0;
        $pushHelper = __DIR__ . '/admin/catalog_push.php';
        if (is_file($pushHelper)) {
            require_once $pushHelper;
            $r = catalog_push_to_server($pdo); // no id -> full catalog
            $fullOk = !empty($r['ok']);
            $fullCount = (int)($r['anime_count'] ?? 0);
            if (!$fullOk) {
                error_log('[anime_tracker] edit_anime full catalog push failed: '
                    . (isset($r['message']) ? $r['message'] : 'unknown'));
            }
        } else {
            error_log('[anime_tracker] edit_anime full catalog push skipped: helper missing');
        }
        header("Location: edit_anime.php?id=" . urlencode((string)$id)
            . ($fullOk ? '&full_pushed=' . $fullCount : '&full_push_failed=1'));
        exit();
    }

    // Mevcut anime bilgilerini kontrol et
    if ($anime['status'] == 'Yayın Tamamlandı') {
        // Eger anime yayini tamamlandiysa, durumu degistirmeye izin verme
        $_POST['status'] = 'Yayın Tamamlandı';
    }

    $title = $_POST['title'];
    $title_english = trim($_POST['title_english'] ?? '');
    $status = $_POST['status'];
    $total_episodes = $_POST['total_episodes'] ?? null;
    $aired_episodes = $_POST['aired_episodes'] ?? null;
    // 0.7 - filler bolum izleme gorunurluk bayragi (checkbox). Isaretli
    // degilse 0. Salt gorunurluk; kapatmak filler kayitlarini silmez.
    $filler_tracking = isset($_POST['filler_tracking']) ? 1 : 0;
    // 1.1.2 - yetiskin (+18) icerik bayragi (checkbox). Isaretli degilse 0.
    // Katalog meta verisi; gorunurluk ayri kullanici tercihiyle yonetilir.
    $is_adult = isset($_POST['is_adult']) ? 1 : 0;
    $watched_episodes = $_POST['watched_episodes'] ?? 0;
    if ($watched_episodes === '') { $watched_episodes = 0; }
    $notes = $_POST['notes'];
    $alternative_titles = isset($_POST['alternative_titles']) ? array_filter($_POST['alternative_titles']) : [];
    // POST'tan gelen secilen turler. Bu degisken adi DB'den cekilen tum turler
    // listesi ($genres) ile cakismamasi icin kasten "posted_genres" olarak
    // adlandirildi - aksi halde form render asamasinda dropdown icin gereken
    // tum turler listesi silinirdi (variable shadowing).
    $posted_genres = !empty($_POST['genres']) ? explode(',', $_POST['genres']) : [];
    $watch_status = $_POST['watch_status'];
    // 1.0.10: '__unselected__' form sentineli NULL'a cevrilir - durum
    // "secim yapilmamis"a geri alinabilir; ua_set_state NULL yazar.
    if ($watch_status === '__unselected__') {
        $watch_status = null;
    }
    // Kisisel izleme tarihleri (1.1.0, elle giris). Bos string -> NULL:
    // DATE kolonu bos string kabul etmez; NULL'a cevrilmezse ua_set_state'teki
    // tum upsert reddedilir. Gecersiz deger DB tarafinda yakalanir/loglanir.
    $watch_start_date  = ($_POST['watch_start_date']  ?? '') !== '' ? $_POST['watch_start_date']  : null;
    $watch_finish_date = ($_POST['watch_finish_date'] ?? '') !== '' ? $_POST['watch_finish_date'] : null;
    $next_episode_date = $_POST['next_episode_date'] ?? null;
    $anidb_link = $_POST['anidb_link'] ?? '';
    $mal_link = $_POST['mal_link'] ?? '';
    $anime_schedule_link = $_POST['anime_schedule_link'] ?? '';

    // MAL ve AniDB linkleri zorunlu - katalog senkronizasyonunda local
    // ile sunucu arasindaki eslesmeyi saglayan kimlik alanlari bunlardan
    // parse ediliyor.
    $validation_errors = [];
    $mal_id = parseMalId($mal_link);
    $anidb_id = parseAnidbId($anidb_link);

    if (empty(trim($mal_link))) {
        $validation_errors[] = t('add_anime.error.mal_link_required');
    } elseif ($mal_id === null) {
        $validation_errors[] = t('add_anime.error.mal_link_invalid');
    }

    // AniDB: hem /anime/ hem /episode/ URL'leri kabul edilir.
    // /anime/ URL'lerinden anidb_id parse edilir (sync icin).
    // /episode/ URL'lerinde anidb_id NULL kalir, sync mal_id ile calisir.
    if (empty(trim($anidb_link))) {
        $validation_errors[] = t('add_anime.error.anidb_link_required');
    } elseif (!preg_match('#^https?://anidb\.net/#i', $anidb_link)) {
        $validation_errors[] = t('add_anime.error.anidb_link_invalid');
    }

    if (!empty($validation_errors)) {
        $errorTitle   = htmlspecialchars(t('add_anime.error_page.form_error_title'), ENT_QUOTES, 'UTF-8');
        $goBackLabel  = htmlspecialchars(t('add_anime.error_page.go_back_and_fix'), ENT_QUOTES, 'UTF-8');
        die(
            '<!DOCTYPE html><html lang="' . htmlspecialchars(current_lang(), ENT_QUOTES, 'UTF-8') . '"><head><meta charset="UTF-8">' .
            '<title>' . $errorTitle . '</title></head><body style="font-family:Arial;max-width:600px;margin:40px auto;padding:20px;">' .
            '<h1 style="color:#d32f2f;">' . $errorTitle . '</h1>' .
            '<ul>' . implode('', array_map(function($e) {
                return '<li>' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</li>';
            }, $validation_errors)) . '</ul>' .
            '<p><a href="javascript:history.back()">' . $goBackLabel . '</a></p>' .
            '</body></html>'
        );
    }

    $episode_interval = $_POST['episode_interval'] ?? 7;
    $broadcast_day = $_POST['broadcast_day'] ?? '';
    $broadcast_time = $_POST['broadcast_time'] ?? '';
    $broadcast_timezone = $_POST['broadcast_timezone'] ?? 'Asia/Tokyo';
    // Synopsis handling (0.7.3 - language-specific personal synopsis).
    // The catalog synopsis is multi-language (synopsis_tr + synopsis_en +
    // translation_status). The personal synopsis is now also per-language:
    //   user_synopsis    = personal TR, user_synopsis_en = personal EN.
    // State is decided PER LANGUAGE, independently:
    //   <lang> "Catalog" (user_synopsis(_en) IS NULL): the editable
    //     catalog field was shown; read synopsis_<lang> from POST.
    //   <lang> "Personal" (user_synopsis(_en) NOT NULL): the catalog field
    //     was readonly, so keep its existing value (unless admin override
    //     is on, in which case it was editable and we read it from POST);
    //     read the personal field from POST.
    // An empty personal field stays '' (NOT NULL) so it counts as
    // "intentionally cleared" and sync will not restore it (see
    // catalog_import.php move logic + tasarim_0_7_3 doc).
    $trPersonal = ($anime['user_synopsis'] !== null);
    $enPersonal = ($anime['user_synopsis_en'] !== null);
    $trCatalogEditable = (!$trPersonal || $synopsisEditOverride);
    $enCatalogEditable = (!$enPersonal || $synopsisEditOverride);

    // Catalog synopsis_tr / synopsis_en: read from POST when the field was
    // editable, otherwise keep the stored value.
    $synopsis_tr = $trCatalogEditable ? ($_POST['synopsis_tr'] ?? '') : ($anime['synopsis_tr'] ?? '');
    $synopsis_en = $enCatalogEditable ? ($_POST['synopsis_en'] ?? '') : ($anime['synopsis_en'] ?? '');

    // translation_status: only meaningful when the EN catalog text could
    // change in this save (EN editable). Otherwise keep the stored status.
    if ($enCatalogEditable) {
        $markReviewed = isset($_POST['mark_reviewed']);
        $trChanged = ($synopsis_tr !== ($anime['synopsis_tr'] ?? ''));
        if (trim($synopsis_en) === '') {
            $translation_status = 'none';
        } elseif ($trChanged) {
            $translation_status = 'ai';
        } else {
            $translation_status = $markReviewed ? 'reviewed' : 'ai';
        }
    } else {
        $translation_status = $anime['translation_status'];
    }

    // Personal synopsis per language: present in the form only when that
    // language is already Personal; otherwise keep NULL (stays Catalog).
    $user_synopsis    = $trPersonal ? ($_POST['user_synopsis']    ?? '') : null;
    $user_synopsis_en = $enPersonal ? ($_POST['user_synopsis_en'] ?? '') : null;
    $release_date = $_POST['release_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;

    // Series relationship fields
    $series_name = $_POST['series_name'] ?? null;
    $media_type = $_POST['media_type'] ?? null;
    $next_in_series = $_POST['next_in_series'] ?? null;

    // MySQL'in TIME / DATE / DATETIME kolonlari bos string kabul etmez,
    // sadece NULL veya gecerli bir tarih/saat. Form bos gonderirse '' gelir,
    // bunu NULL'a cevirerek INSERT/UPDATE hatasini engelliyoruz.
    if ($broadcast_time === '') { $broadcast_time = null; }
    if ($release_date === '')   { $release_date = null; }
    if ($end_date === '')       { $end_date = null; }
    if ($next_episode_date === '') { $next_episode_date = null; }

    // Tarih format kontrolu: HTML date input YYYY-MM-DD gondermeli.
    // Tarayici hatalari (orn. 5 haneli yil 20026) veya manuel giris
    // yuzunden gecersiz tarih DB'ye ulasmadan yakalanir.
    if ($release_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $release_date)) {
        $validation_errors[] = t('add_anime.error.release_date_invalid');
    }
    if ($end_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        $validation_errors[] = t('add_anime.error.end_date_invalid');
    }
    if ($next_episode_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}/', $next_episode_date)) {
        $validation_errors[] = t('add_anime.error.next_episode_date_invalid');
    }

    if (!empty($validation_errors)) {
        $errorTitle  = htmlspecialchars(t('add_anime.error_page.form_error_title'), ENT_QUOTES, 'UTF-8');
        $goBackLabel = htmlspecialchars(t('add_anime.error_page.go_back_and_fix'), ENT_QUOTES, 'UTF-8');
        die(
            '<!DOCTYPE html><html lang="' . htmlspecialchars(current_lang(), ENT_QUOTES, 'UTF-8') . '"><head><meta charset="UTF-8">' .
            '<title>' . $errorTitle . '</title></head><body style="font-family:Arial;max-width:600px;margin:40px auto;padding:20px;">' .
            '<h1 style="color:#d32f2f;">' . $errorTitle . '</h1>' .
            '<ul>' . implode('', array_map(function($e) {
                return '<li>' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</li>';
            }, $validation_errors)) . '</ul>' .
            '<p><a href="javascript:history.back()">' . $goBackLabel . '</a></p>' .
            '</body></html>'
        );
    }

    // Episode fields: bos string'leri NULL'a cevir (v0.5 ile total artik nullable)
    if ($total_episodes === '') { $total_episodes = null; }
    if ($aired_episodes === '') { $aired_episodes = null; }

    // Series fields: bos string'leri NULL'a cevir
    if ($series_name === '') { $series_name = null; }
    if ($media_type === '')  { $media_type = null; }
    if ($next_in_series === '' || $next_in_series === '0') { $next_in_series = null; }

    // Circular reference check: A -> B -> A dongusu olusmasin
    if ($next_in_series !== null && !validateNextInSeries($pdo, $id, $next_in_series)) {
        $next_in_series = null;
        error_log('[anime_tracker] Circular next_in_series prevented: anime ' . $id . ' -> ' . $_POST['next_in_series']);
    }

    // Status-based normalization for episode counts.
    // Frontend (JS) already hides aired_episodes when status is 'Yayin Tamamlandi',
    // but we enforce the same rule server-side as a safety net in case JS is
    // disabled or someone posts directly.
    if ($status === 'Yayın Tamamlandı') {
        // If user left total blank but filled aired (e.g. switching an
        // ongoing anime to finished at the end of its run), promote aired
        // into total so the final count is preserved. This is the One Piece
        // archival case: when a long-running series finally ends, the
        // last known aired count becomes the final total.
        if ($total_episodes === null && $aired_episodes !== null) {
            $total_episodes = $aired_episodes;
        }
        // aired_episodes is meaningless for finished anime - clear it.
        $aired_episodes = null;
    } elseif ($status !== 'Yayın Devam Ediyor') {
        // 1.1.10: aired_episodes only applies to an actively airing show.
        // For the non-airing states (Başlamadı / Seçim Yapılmadı / İptal
        // Edildi) clear any leftover value the hidden field may have posted.
        $aired_episodes = null;
    }

    // Madde E - Tek bolumlu animede yayin bitis tarihi anlamsiz.
    // Film, OVA, Special veya tek bolumlu herhangi bir icerik tek seferde
    // yayinlandigi icin baslangic = bitis. Frontend (JS) zaten bu durumda
    // end-date alanini gizliyor, server-side de ayni kurali uyguluyoruz
    // (JS kapaliysa veya direkt POST yapilirsa savunma).
    // Kullanici 2->1 degisikligi yaparsa eski end_date degeri NULL'a cevrilir
    // (Karar 2 - Secenek A). Bu sayede eski kayitlar bir sonraki edit'te
    // organik olarak temizlenir.
    if ((int)$total_episodes === 1) {
        $end_date = null;
    }

    // Resim yukleme - yeni dosya secildiyse functions.php icindeki guvenli
    // helper ile kaydet, sonra eski resmi sil. Hicbir dosya secilmediyse
    // mevcut image_path korunur.
    try {
        $newImagePath = handleImageUpload($_FILES['image'] ?? null);
    } catch (Exception $e) {
        $imageErrorTitle = htmlspecialchars(t('add_anime.error_page.image_error_title'), ENT_QUOTES, 'UTF-8');
        $goBackLabel     = htmlspecialchars(t('add_anime.error_page.go_back_and_retry'), ENT_QUOTES, 'UTF-8');
        die(
            '<!DOCTYPE html><html lang="' . htmlspecialchars(current_lang(), ENT_QUOTES, 'UTF-8') . '"><head><meta charset="UTF-8">' .
            '<title>' . $imageErrorTitle . '</title></head><body style="font-family:Arial;max-width:600px;margin:40px auto;padding:20px;">' .
            '<h1 style="color:#d32f2f;">' . $imageErrorTitle . '</h1>' .
            '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>' .
            '<p><a href="javascript:history.back()">' . $goBackLabel . '</a></p>' .
            '</body></html>'
        );
    }

    if ($newImagePath !== null) {
        // Yeni resim yuklendi - eski resmi sil
        $target_file = $newImagePath;
        // Eski resmi SILMIYORUZ! UPDATE basarili olduktan sonra silinecek.
        // proje_durumu_01 madde 17 "Bilinen kalan iki problem" #2: eski
        // siralamada UPDATE patlarsa hem eski hem yeni resim sorunluydu.
        // Yeni siralama: once UPDATE, basari sonrasi eski resmi temizle.
    } else {
        // Yeni resim yok - mevcut yolu koru
        $target_file = $anime['image_path'];
    }

    // Sonraki bolum tarihini hesapla
    if ($status === 'Yayın Devam Ediyor' && !empty($broadcast_day) && !empty($broadcast_time)) {
        $next_episode_date = calculateNextEpisodeDate([
            'status' => $status,
            'broadcast_day' => $broadcast_day,
            'broadcast_time' => $broadcast_time,
            'broadcast_timezone' => $broadcast_timezone
        ]);
    }

    // Animeyi guncelle.
    // Genres no longer live on this row - they are written to the
    // anime_genres join table after the UPDATE via
    // setAnimeGenresByNames(), mirroring the tags handler below.
    $sql = "UPDATE animes SET 
            title = ?,
            title_english = ?,
            alternative_titles = ?,
            status = ?,
            total_episodes = ?,
            aired_episodes = ?,
            image_path = ?,
            next_episode_date = ?,
            anidb_link = ?,
            mal_link = ?,
            anime_schedule_link = ?,
            episode_interval = ?,
            broadcast_day = ?,
            broadcast_time = ?,
            broadcast_timezone = ?,
            synopsis_tr = ?,
            synopsis_en = ?,
            translation_status = ?,
            release_date = ?,
            end_date = ?,
            series_name = ?,
            media_type = ?,
            next_in_series = ?,
            mal_id = ?,
            anidb_id = ?,
            filler_tracking = ?,
            is_adult = ?
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);

    // UPDATE execute'u try/catch icinde calistir. mal_id, anidb_id ve
    // catalog_uuid UNIQUE oldugu icin kullanici bir alanin degerini baska
    // bir kaydin degeriyle eslesecek sekilde duzenlerse MySQL 1062 hatasi
    // firlatir (23000). Yakalanmazsa kullaniciya ham fatal error sizar.
    //
    // Bonus: Eski resim hala diskte (yeni resim yuklendi ama eski silinmedi).
    // UPDATE basarili olursa eski resmi simdi sileriz. Patlarsa yeni resim
    // yetim kaldigi icin onu silip eski resmi diskte tutariz - kullanici
    // duzeltip tekrar denerse mevcut anime'nin resmi bozulmaz.
    try {
        $stmt->execute([
            $title,
            $title_english !== '' ? $title_english : null,
            implode('|', $alternative_titles),
            $status,
            $total_episodes,
            $aired_episodes,
            $target_file,
            $next_episode_date,
            $anidb_link,
            $mal_link,
            $anime_schedule_link,
            $episode_interval,
            $broadcast_day,
            $broadcast_time,
            $broadcast_timezone,
            $synopsis_tr,
            $synopsis_en,
            $translation_status,
            $release_date,
            $end_date,
            $series_name,
            $media_type,
            $next_in_series,
            $mal_id,
            $anidb_id,
            $filler_tracking,
            $is_adult,
            $id
        ]);

        // Kisisel izleme durumu (watched_episodes / notes / watch_status)
        // ve dil-ozel Kisisel Konu (user_synopsis / user_synopsis_en) artik
        // user_anime'da (1.0.1) - animes UPDATE'inden cikarildilar. Mevcut
        // kullanicinin satirina yaziliyorlar. POST toplama asamasi (Mod 1/
        // Mod 2 + admin override) $user_synopsis(_en)'i zaten dogru hesapladi:
        // o dil Katalog ise NULL, Kisisel ise POST degeri ('' = bilincli
        // silindi). NULL yazmak o dili Katalog modunda birakir.
        ua_set_state($pdo, current_user_id(), $id, [
            'watch_status'      => $watch_status,
            'watched_episodes'  => $watched_episodes,
            'notes'             => $notes,
            'user_synopsis'     => $user_synopsis,
            'user_synopsis_en'  => $user_synopsis_en,
            'watch_start_date'  => $watch_start_date,
            'watch_finish_date' => $watch_finish_date,
        ]);

        // UPDATE basarili - simdi guvenle eski resmi sil. Yeni resim
        // yuklenmemisse $newImagePath null, atlanir.
        if ($newImagePath !== null
            && !empty($anime['image_path'])
            && $anime['image_path'] !== $newImagePath
            && file_exists(__DIR__ . '/' . $anime['image_path'])) {
            @unlink(__DIR__ . '/' . $anime['image_path']);
        }
    } catch (PDOException $e) {
        if ($e->getCode() !== '23000') {
            // Beklenmedik bir hata - fatal error yerine logla ve devam et
            // (eski davranis ham exception sizdiriyor).
            error_log('[anime_tracker] edit_anime UPDATE failed: ' . $e->getMessage());
            throw $e;
        }

        // Yeni resim yuklenmisse yetim kaldi - sil. Eski resme dokunma,
        // mevcut anime'nin gorseli zarar gormesin.
        if ($newImagePath !== null && file_exists(__DIR__ . '/' . $newImagePath)) {
            @unlink(__DIR__ . '/' . $newImagePath);
        }

        // Hangi UNIQUE index patladi? add_anime.php ile ayni mantik.
        $indexName = '';
        if (preg_match("/for key '([^']+)'/", $e->getMessage(), $m)) {
            $indexName = $m[1];
        }

        $fieldLabel  = '';
        $duplicateValue = '';
        $existingId    = null;
        $existingTitle = null;

        if ($indexName === 'idx_mal_id' && !empty($mal_id)) {
            $fieldLabel = t('add_anime.duplicate.field_mal_id');
            $duplicateValue = (string)$mal_id;
            // ON edit, mevcut animenin kendi MAL ID'si conflict olamaz
            // cunku WHERE id = ? var; conflict baska bir kayittan geliyor.
            // Sirf netlik icin yine de farkli ID donduren WHERE ekledim.
            $look = $pdo->prepare("SELECT id, title FROM animes WHERE mal_id = ? AND id != ? LIMIT 1");
            $look->execute([$mal_id, $id]);
            $row = $look->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $existingId    = (int)$row['id'];
                $existingTitle = $row['title'];
            }
        } elseif ($indexName === 'idx_anidb_id' && !empty($anidb_id)) {
            $fieldLabel = t('add_anime.duplicate.field_anidb_id');
            $duplicateValue = (string)$anidb_id;
            $look = $pdo->prepare("SELECT id, title FROM animes WHERE anidb_id = ? AND id != ? LIMIT 1");
            $look->execute([$anidb_id, $id]);
            $row = $look->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $existingId    = (int)$row['id'];
                $existingTitle = $row['title'];
            }
        } elseif ($indexName === 'idx_catalog_uuid') {
            $fieldLabel = t('add_anime.duplicate.field_catalog_uuid');
        } else {
            $fieldLabel = t('add_anime.duplicate.field_unknown');
        }

        $headerMsg = htmlspecialchars(t('add_anime.error_page.duplicate_title'), ENT_QUOTES, 'UTF-8');
        $detailMsg = htmlspecialchars($fieldLabel, ENT_QUOTES, 'UTF-8');
        if ($duplicateValue !== '') {
            $detailMsg .= ' (' . htmlspecialchars($duplicateValue, ENT_QUOTES, 'UTF-8') . ')';
        }
        $detailMsg .= ' ' . htmlspecialchars(t('edit_anime.duplicate.already_used_suffix'), ENT_QUOTES, 'UTF-8');
        if ($existingId !== null && $existingTitle !== null) {
            $detailMsg .= ' ' . htmlspecialchars(t('edit_anime.duplicate.conflicting_record_prefix'), ENT_QUOTES, 'UTF-8')
                . ' <strong>' . htmlspecialchars($existingTitle, ENT_QUOTES, 'UTF-8') . '</strong>';
        }

        $existingLink = '';
        if ($existingId !== null) {
            $existingLink =
                '<p><a href="anime_details.php?id=' . (int)$existingId . '" '
                . 'style="color:#1976d2;">' . htmlspecialchars(t('edit_anime.error_page.go_to_conflicting'), ENT_QUOTES, 'UTF-8') . '</a></p>';
        }

        $goBackLabel = htmlspecialchars(t('add_anime.error_page.go_back_and_fix'), ENT_QUOTES, 'UTF-8');
        $goToListLabel = htmlspecialchars(t('add_anime.error_page.go_to_list'), ENT_QUOTES, 'UTF-8');

        die(
            '<!DOCTYPE html><html lang="' . htmlspecialchars(current_lang(), ENT_QUOTES, 'UTF-8') . '"><head><meta charset="UTF-8">' .
            '<title>' . $headerMsg . '</title></head>' .
            '<body style="font-family:Arial;max-width:600px;margin:40px auto;padding:20px;">' .
            '<h1 style="color:#d32f2f;">' . $headerMsg . '</h1>' .
            '<p>' . $detailMsg . '</p>' .
            $existingLink .
            '<p><a href="javascript:history.back()">' . $goBackLabel . '</a></p>' .
            '<p><a href="index.php">' . $goToListLabel . '</a></p>' .
            '</body></html>'
        );
    }

    // Turleri guncelle (kanonik taksonomi).
    // setAnimeGenres icinde DELETE + INSERT pattern'i var, mevcut tur
    // baglarini tamamen replace eder. Form'dan gelen isimler
    // findOrCreateGenre ile master listede yoksa olusturulur, sonra
    // anime_genres tablosuna yazilir.
    setAnimeGenresByNames($pdo, $id, $posted_genres);

    // Cumleleri guncelle (oneri sistemi).
    // setAnimeTags icinde DELETE + INSERT pattern'i var, mevcut cumle
    // baglarini tamamen replace eder. Yeni cumleler varsa
    // findOrCreateTag once tags tablosuna ekler.
    $tag_names_raw = $_POST['tags'] ?? '';
    $tag_names = array_filter(array_map('trim', explode(',', $tag_names_raw)));
    $tag_ids = [];
    foreach ($tag_names as $tn) {
        $tid = findOrCreateTag($pdo, $tn);
        if ($tid > 0) {
            $tag_ids[] = $tid;
        }
    }
    setAnimeTags($pdo, $id, $tag_ids);

    // 1.0.11: online katalog duzenlemesi merkez sunucuya otomatik gider.
    // Karar 14'teki terfi push sozlesmesinin ek tetikleyicisi: yalniz
    // MULTI_USER_MODE'da ve yalniz katalog kaydi (source='catalog')
    // duzenlendiginde calisir - pending (source='local') kayit terfi
    // aninda admin_pending uzerinden zaten gonderilir, burada gonderilmez.
    // Self-host'ta blok hic calismaz; admin/catalog_push.php pakete
    // girmedigi icin require lazy ve dosya-varlik kosulu icindedir.
    // Basarisiz push kaydi GERI ALMAZ (terfi sozlesmesinin aynisi):
    // kullanici index'te uyari bandiyla bilgilendirilir, ayrinti
    // error_log'a yazilir; animeyi yeniden kaydetmek push'u tekrar dener.
    $pushFailed = false;
    if (MULTI_USER_MODE && ($anime['source'] ?? '') === 'catalog') {
        $pushHelper = __DIR__ . '/admin/catalog_push.php';
        if (is_file($pushHelper)) {
            require_once $pushHelper;
            // 1.1.8: scoped push - only this anime's series (or just it), not
            // the whole catalog. The admin "full push" button forces a resync.
            $push = catalog_push_to_server($pdo, (int)$id);
            if (empty($push['ok'])) {
                $pushFailed = true;
                error_log('[anime_tracker] edit_anime catalog push failed: '
                    . (isset($push['message']) ? $push['message'] : 'unknown'));
            }
        } else {
            $pushFailed = true;
            error_log('[anime_tracker] edit_anime catalog push skipped: helper missing');
        }
    }

    // 1.1.5: guncelleme sonrasi index yerine AYNI duzenleme sayfasina don
    // (PRG korunur - hala redirect, F5 POST'u tekrarlamaz). Kullanici
    // duzenledigi animede kalir; ?updated=1 basari bandini tetikler, taze
    // DB degerleri yeniden yuklenir. Push basarisizlik bayragi bu sayfada
    // gosterilir (asagidaki banner bloku). id her zaman GET'ten gelir.
    header("Location: edit_anime.php?id=" . urlencode((string)$id) . "&updated=1"
        . ($pushFailed ? '&catalog_push=failed' : ''));
    exit();
}

// Alternatif isimleri diziye cevir
$alternative_titles = !empty($anime['alternative_titles']) ? explode('|', $anime['alternative_titles']) : [];
// Mevcut turleri JOIN tablosundan cek (form yuklenirken rozet ve hidden
// input dolumu icin). Helper id+name doner; alttaki kullanim noktalari
// (hidden input value, JS init) sadece name listesine ihtiyac duyuyor.
$current_genres = getAnimeGenres($pdo, $id);
$selected_genres = array_map(function($g) { return $g['name']; }, $current_genres);
// Mevcut cumleleri cek (form yuklenirken rozet olarak gostermek icin)
$current_tags = getAnimeTags($pdo, $id);
$selected_tag_names = array_map(function($t) { return $t['name']; }, $current_tags);
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(current_lang(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('edit_anime.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <div class="container">
        <?php // 1.1.5: guncelleme sonrasi bu sayfada kalinca gosterilen basari bandi. ?>
        <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
            <div style="max-width: 700px; margin: 15px auto; background: #d1e7dd; border: 1px solid #badbcc; color: #0f5132; padding: 12px 16px; border-radius: 8px;">
                <?php echo htmlspecialchars(t('edit_anime.notice.saved'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        <?php // Online katalog push basarisiz oldugunda (index.php ile ayni uyari, artik burada gosterilir). ?>
        <?php if (isset($_GET['catalog_push']) && $_GET['catalog_push'] === 'failed'): ?>
            <div style="max-width: 700px; margin: 15px auto; background: #fff3cd; border: 1px solid #ffe69c; color: #664d03; padding: 12px 16px; border-radius: 8px;">
                <?php echo htmlspecialchars(t('index.warn.catalog_push_failed'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        <?php // 1.1.8: admin tam-katalog push sonucu (admin-only buton). ?>
        <?php if (isset($_GET['full_pushed'])): ?>
            <div style="max-width: 700px; margin: 15px auto; background: #d1e7dd; border: 1px solid #badbcc; color: #0f5132; padding: 12px 16px; border-radius: 8px;">
                <?php echo htmlspecialchars(sprintf(t('edit_anime.notice.full_pushed'), (int)$_GET['full_pushed']), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php elseif (isset($_GET['full_push_failed'])): ?>
            <div style="max-width: 700px; margin: 15px auto; background: #f8d7da; border: 1px solid #f5c2c7; color: #842029; padding: 12px 16px; border-radius: 8px;">
                <?php echo htmlspecialchars(t('edit_anime.notice.full_push_failed'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        <div class="header-section">
            <a href="about.php" class="about-link"><?php echo htmlspecialchars(t('nav.about'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php // SECTION: Language switcher (snippet copy - see _lang_switcher_reference.php) ?>
            <?php echo auth_nav_links(); ?>
        </div>
        <div class="page-title">
            <?php echo htmlspecialchars(t('edit_anime.heading'), ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <div class="button-container">
            <a class="anime-list-button" href="index.php"><?php echo htmlspecialchars(t('index.list_title'), ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
        <div class="button-spacing"></div>

        <div class="section-spacing"></div>

        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="form-group">
                <label for="title"><?php echo htmlspecialchars(t('add_anime.label.title'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <input type="text" name="title" value="<?php echo htmlspecialchars($anime['title']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label><?php echo htmlspecialchars(t('add_anime.label.alternative_titles'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <div id="alternative-titles" class="dynamic-fields">
                        <?php foreach ($alternative_titles as $alt_title): ?>
                            <div class="field-group">
                                <input type="text" name="alternative_titles[]" value="<?php echo htmlspecialchars($alt_title); ?>">
                                <button type="button" class="remove-button" onclick="removeField(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-button" onclick="addAlternativeTitle()">
                        <i class="fas fa-plus"></i> <?php echo htmlspecialchars(t('add_anime.btn.add_alternative_title'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="title_english"><?php echo htmlspecialchars(t('add_anime.label.title_english'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <input type="text" id="title_english" name="title_english" value="<?php echo htmlspecialchars($anime['title_english'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.title_english'), ENT_QUOTES, 'UTF-8'); ?>">
                    <small class="form-text text-muted"><?php echo htmlspecialchars(t('add_anime.hint.title_english'), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
            </div>

            <?php
            // Synopsis display (0.7.3 - language-specific personal synopsis).
            // State is per language, independent:
            //   <lang> Catalog  (user_synopsis(_en) IS NULL): editable
            //     catalog field (synopsis_<lang>).
            //   <lang> Personal (user_synopsis(_en) NOT NULL): catalog field
            //     readonly + a personal field that is always editable.
            // Admin override (synopsis_edit_override) keeps the catalog field
            // editable even when Personal - applied to TR and EN separately.
            $trPersonal = ($anime['user_synopsis']    !== null);
            $enPersonal = ($anime['user_synopsis_en'] !== null);
            $trCatalogEditable = (!$trPersonal || $synopsisEditOverride);
            $enCatalogEditable = (!$enPersonal || $synopsisEditOverride);
            ?>

            <?php /* ---- Catalog synopsis TR ---- */ ?>
            <?php if ($trCatalogEditable): ?>
                <div class="form-group">
                    <label for="synopsis_tr"><?php echo htmlspecialchars(t('add_anime.label.synopsis'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="input-area">
                        <textarea id="synopsis_tr" name="synopsis_tr" rows="6" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.synopsis'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($anime['synopsis_tr'] ?? ''); ?></textarea>
                        <button type="button" class="btn-copy-synopsis"
                                onclick="navigator.clipboard.writeText(document.getElementById('synopsis_tr').value);"
                                style="margin-top:6px; padding:6px 12px; background:#5a4ed1; color:#fff; border:none; border-radius:4px; cursor:pointer;">
                            <i class="fas fa-copy"></i> <?php echo htmlspecialchars(t('edit_anime.btn.copy_synopsis_tr'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label for="synopsis_tr_readonly"><?php echo htmlspecialchars(t('add_anime.label.synopsis'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="input-area">
                        <textarea id="synopsis_tr_readonly" rows="6" readonly
                                  style="background-color: #f5f5f5; color: #555; cursor: not-allowed;"><?php echo htmlspecialchars($anime['synopsis_tr'] ?? ''); ?></textarea>
                        <small class="form-text text-muted"><?php echo htmlspecialchars(t('edit_anime.hint.synopsis_readonly'), ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                </div>
            <?php endif; ?>

            <?php /* ---- Personal synopsis TR (only when TR is Personal) ---- */ ?>
            <?php if ($trPersonal): ?>
                <div class="form-group">
                    <label for="user_synopsis"><?php echo htmlspecialchars(t('edit_anime.label.user_synopsis'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="input-area">
                        <textarea id="user_synopsis" name="user_synopsis" rows="4" placeholder="<?php echo htmlspecialchars(t('edit_anime.ph.user_synopsis'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($anime['user_synopsis'] ?? ''); ?></textarea>
                        <small class="form-text text-muted"><?php echo htmlspecialchars(t('edit_anime.hint.user_synopsis'), ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                </div>
            <?php endif; ?>

            <?php /* ---- Catalog synopsis EN ---- */ ?>
            <?php if ($enCatalogEditable): ?>
                <div class="form-group">
                    <label for="synopsis_en"><?php echo htmlspecialchars(t('add_anime.label.synopsis_en'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="input-area">
                        <textarea id="synopsis_en" name="synopsis_en" rows="6" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.synopsis_en'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($anime['synopsis_en'] ?? ''); ?></textarea>
                        <small class="form-text text-muted"><?php echo htmlspecialchars(t('edit_anime.hint.synopsis_en'), ENT_QUOTES, 'UTF-8'); ?></small>
                        <label style="display:block; margin-top:8px; font-weight:normal;">
                            <input type="checkbox" name="mark_reviewed" value="1"<?php echo (($anime['translation_status'] ?? 'none') === 'reviewed') ? ' checked' : ''; ?>>
                            <?php echo htmlspecialchars(t('edit_anime.label.mark_reviewed'), ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <small class="form-text text-muted"><?php echo htmlspecialchars(t('edit_anime.hint.mark_reviewed'), ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label for="synopsis_en_readonly"><?php echo htmlspecialchars(t('add_anime.label.synopsis_en'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="input-area">
                        <textarea id="synopsis_en_readonly" rows="6" readonly
                                  style="background-color: #f5f5f5; color: #555; cursor: not-allowed;"><?php echo htmlspecialchars($anime['synopsis_en'] ?? ''); ?></textarea>
                        <small class="form-text text-muted"><?php echo htmlspecialchars(t('edit_anime.hint.synopsis_readonly'), ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                </div>
            <?php endif; ?>

            <?php /* ---- Personal synopsis EN (only when EN is Personal) ---- */ ?>
            <?php if ($enPersonal): ?>
                <div class="form-group">
                    <label for="user_synopsis_en"><?php echo htmlspecialchars(t('edit_anime.label.user_synopsis_en'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="input-area">
                        <textarea id="user_synopsis_en" name="user_synopsis_en" rows="4" placeholder="<?php echo htmlspecialchars(t('edit_anime.ph.user_synopsis_en'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($anime['user_synopsis_en'] ?? ''); ?></textarea>
                        <small class="form-text text-muted"><?php echo htmlspecialchars(t('edit_anime.hint.user_synopsis'), ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="total_episodes"><?php echo htmlspecialchars(t('add_anime.label.total_episodes'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <input type="number" name="total_episodes" value="<?php echo htmlspecialchars($anime['total_episodes'] ?? ''); ?>" min="0" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.total_episodes'), ENT_QUOTES, 'UTF-8'); ?>" oninput="toggleEndDateBySingleEpisode()">
                </div>
            </div>

            <div id="aired-episodes-section" style="display: <?php echo $anime['status'] == 'Yayın Devam Ediyor' ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label for="aired_episodes"><?php echo htmlspecialchars(t('add_anime.label.aired_episodes'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="input-area">
                        <input type="number" name="aired_episodes" id="aired_episodes" value="<?php echo htmlspecialchars($anime['aired_episodes'] ?? ''); ?>" min="0" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.aired_episodes'), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php
                        // Senkronize butonu sadece MAL ID dolu animelerde gosterilir.
                        // mal_id yoksa AnimeSchedule timetable'da eslestirme yapilamaz,
                        // butonu gostermek anlamsiz olur. Anime durumu kontrolu zaten
                        // parent div ile saglaniyor (sadece "Yayin Devam Ediyor" iken
                        // bu tum bolum gorunur).
                        if (!empty($anime['mal_id'])):
                        ?>
                        <button type="button" id="aired-sync-btn" onclick="syncAiredEpisodes()" style="margin-top:8px; padding:8px 14px; background:#27ae60; color:#fff; border:none; border-radius:4px; cursor:pointer;">
                            <i class="fas fa-sync"></i> <?php echo htmlspecialchars(t('add_anime.btn.animeschedule_fetch'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                        <div id="aired-sync-status" style="margin-top:8px; font-size:13px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php // 0.7 - filler bolum izleme gorunurluk toggle'i. Mevcut
                  // deger ($anime['filler_tracking']) ile on-isaretli. Acilinca
                  // anime_details.php'de filler ozeti + Duzenle linki gorunur;
                  // kapatmak filler kayitlarini SILMEZ, gizler. Standart
                  // form-group deseni: label sol + input-area sag. KARARLAR
                  // Bolum 8. ?>
            <div class="form-group">
                <label for="filler_tracking_chk"><?php echo htmlspecialchars(t('add_anime.label.filler_tracking'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <label class="filler-toggle">
                        <input type="checkbox" name="filler_tracking" id="filler_tracking_chk" value="1"<?php echo !empty($anime['filler_tracking']) ? ' checked' : ''; ?>>
                        <span class="filler-toggle-hint"><?php echo htmlspecialchars(t('add_anime.hint.filler_tracking'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </label>
                </div>
            </div>

            <?php // 1.1.2 - yetiskin (+18) icerik bayragi. Mevcut deger
                  // ($anime['is_adult']) ile on-isaretli. Isaretli anime +18
                  // damgalanir; gorunurluk ayar kapaliyken gizlenir. Gorsel
                  // duzen filler-toggle sinifiyla paylasilir. ?>
            <div class="form-group">
                <label for="is_adult_chk"><?php echo htmlspecialchars(t('add_anime.label.is_adult'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <label class="filler-toggle">
                        <input type="checkbox" name="is_adult" id="is_adult_chk" value="1"<?php echo !empty($anime['is_adult']) ? ' checked' : ''; ?>>
                        <span class="filler-toggle-hint"><?php echo htmlspecialchars(t('add_anime.hint.is_adult'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="release_date"><?php echo htmlspecialchars(t('add_anime.label.release_date'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <input type="date" name="release_date" id="release_date"
                           value="<?php echo isset($anime['release_date']) ? date('Y-m-d', strtotime($anime['release_date'])) : ''; ?>">
                </div>
            </div>

            <?php
                // Madde E - Tek bolumlu animede end-date bastan gizli olur.
                // Status finished AND total_episodes != 1 ise gosterilir.
                $endDateInitialDisplay = ($anime['status'] == 'Yayın Tamamlandı'
                                          && (int)($anime['total_episodes'] ?? 0) !== 1)
                                         ? 'block' : 'none';
            ?>
            <div id="end-date-section" style="display: <?php echo $endDateInitialDisplay; ?>;">
                <div class="form-group">
                    <label for="end_date"><?php echo htmlspecialchars(t('add_anime.label.end_date'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="input-area">
                        <input type="date" name="end_date" id="end_date"
                               value="<?php echo isset($anime['end_date']) ? date('Y-m-d', strtotime($anime['end_date'])) : ''; ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
    <label for="status"><?php echo htmlspecialchars(t('add_anime.label.status'), ENT_QUOTES, 'UTF-8'); ?></label>
    <div class="input-area">
        <?php if ($anime['status'] == 'Yayın Tamamlandı'): ?>
            <!-- Yayin tamamlandiysa, alan kilitli olsun -->
            <input type="text" name="status" value="Yayın Tamamlandı" readonly class="locked-field">
<div style="margin-top: 10px;"></div>
<input type="hidden" name="status" value="Yayın Tamamlandı">
<small class="form-text text-muted"><?php echo htmlspecialchars(t('edit_anime.status.locked_hint'), ENT_QUOTES, 'UTF-8'); ?></small>
        <?php else: ?>
            <select name="status" onchange="toggleBroadcastDetails()" required>
                <?php // 1.1.10: five states via the broadcast_status helper. Only
                      // non-finished rows reach this select (finished is locked
                      // above), so any of the other four can be the current value. ?>
                <?php foreach (broadcast_status_options() as $bs_value => $bs_label): ?>
                <option value="<?php echo htmlspecialchars($bs_value, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $anime['status'] === $bs_value ? ' selected' : ''; ?>><?php echo htmlspecialchars($bs_label, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </div>
</div>

            <div id="broadcast-details" style="display: <?php echo $anime['status'] == 'Yayın Devam Ediyor' ? 'block' : 'none'; ?>">
                <div class="form-group">
                    <label for="episode_interval"><?php echo htmlspecialchars(t('add_anime.label.episode_interval'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="input-area">
                        <input type="number" name="episode_interval" value="<?php echo htmlspecialchars($anime['episode_interval'] ?? 7); ?>" min="1">
                    </div>
                </div>

                <div class="form-group">
                    <label for="broadcast_day"><?php echo htmlspecialchars(t('add_anime.label.broadcast_day'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="input-area">
                        <select name="broadcast_day">
                            <option value="" <?php echo empty($anime['broadcast_day']) ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('add_anime.option.choose'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php
                            // DB enum legacy: broadcast_day kolonu Turkce string olarak
                            // tutulur (Pazartesi, Sali, ...). UI label'i t() ile cevirilir
                            // ama value DB ile esleshik kalir - eski kayitlar bozulmaz.
                            $days = [
                                'Pazartesi' => 'add_anime.day.monday',
                                'Salı'      => 'add_anime.day.tuesday',
                                'Çarşamba'  => 'add_anime.day.wednesday',
                                'Perşembe'  => 'add_anime.day.thursday',
                                'Cuma'      => 'add_anime.day.friday',
                                'Cumartesi' => 'add_anime.day.saturday',
                                'Pazar'     => 'add_anime.day.sunday',
                            ];
                            foreach ($days as $dayValue => $dayKey) {
                                $selected = ($anime['broadcast_day'] == $dayValue) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($dayValue, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>'
                                   . htmlspecialchars(t($dayKey), ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="broadcast_time"><?php echo htmlspecialchars(t('add_anime.label.broadcast_time'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="input-area">
                        <input type="time" name="broadcast_time" value="<?php echo htmlspecialchars($anime['broadcast_time'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="broadcast_timezone"><?php echo htmlspecialchars(t('add_anime.label.broadcast_timezone'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="input-area">
                        <?php
                        $current_tz = $anime['broadcast_timezone'] ?? 'Asia/Tokyo';
                        // TZ value sabit IANA timezone ID, label sozlukten cekilir.
                        $tz_options = [
                            'Asia/Tokyo'          => 'add_anime.tz.tokyo',
                            'Europe/Istanbul'     => 'add_anime.tz.istanbul',
                            'UTC'                 => 'add_anime.tz.utc',
                            'America/New_York'    => 'add_anime.tz.new_york',
                            'America/Los_Angeles' => 'add_anime.tz.los_angeles',
                            'Europe/London'       => 'add_anime.tz.london',
                        ];
                        ?>
                        <select name="broadcast_timezone">
                            <?php foreach ($tz_options as $tz_val => $tz_key): ?>
                                <option value="<?php echo $tz_val; ?>" <?php echo ($current_tz === $tz_val) ? 'selected' : ''; ?>><?php echo htmlspecialchars(t($tz_key), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="watch_status"><?php echo htmlspecialchars(t('add_anime.label.watch_status'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <select name="watch_status" onchange="toggleWatchedEpisodes()" required>
                        <option value=""><?php echo htmlspecialchars(t('add_anime.option.choose'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach (watch_status_options() as $ws_value => $ws_label): ?>
                            <option value="<?php echo htmlspecialchars($ws_value); ?>" <?php echo $anime['watch_status'] === $ws_value ? 'selected' : ''; ?>><?php echo htmlspecialchars($ws_label); ?></option>
                        <?php endforeach; ?>
                        <option value="__unselected__" <?php echo $anime['watch_status'] === null ? 'selected' : ''; ?>><?php echo htmlspecialchars(watch_status_label('__unselected__')); ?></option>
                    </select>
                </div>
            </div>

            <div id="watched-episodes-section" style="display: <?php echo in_array($anime['watch_status'], ['Watching', 'OnHold', 'Dropped'], true) ? 'block' : 'none'; ?>">
                <div class="form-group">
                    <label for="watched_episodes"><?php echo htmlspecialchars(t('add_anime.label.watched_episodes'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <div class="input-area">
                        <input type="number" name="watched_episodes" value="<?php echo htmlspecialchars($anime['watched_episodes']); ?>" min="0">
                    </div>
                </div>
            </div>

            <?php /* 1.1.0: kisisel izleme tarihleri (elle giris, opsiyonel).
                     Duruma bagli gizlenmez; her durumda gorunur. Bos = NULL. */ ?>
            <div class="form-group">
                <label><?php echo htmlspecialchars(t('add_anime.label.watch_dates'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <div class="watch-date-row">
                        <label for="watch_start_date" class="watch-date-sublabel"><?php echo htmlspecialchars(t('add_anime.label.watch_start_date'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="date" name="watch_start_date" id="watch_start_date" value="<?php echo htmlspecialchars($anime['watch_start_date'] ?? ''); ?>" onchange="checkWatchDateOrder()">
                    </div>
                    <div class="watch-date-row">
                        <label for="watch_finish_date" class="watch-date-sublabel"><?php echo htmlspecialchars(t('add_anime.label.watch_finish_date'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="date" name="watch_finish_date" id="watch_finish_date" value="<?php echo htmlspecialchars($anime['watch_finish_date'] ?? ''); ?>" onchange="checkWatchDateOrder()">
                    </div>
                    <small id="watch-date-warning" class="form-text" style="display:none; color:#d32f2f;"><?php echo htmlspecialchars(t('add_anime.warn.date_order'), ENT_QUOTES, 'UTF-8'); ?></small>
                    <small class="form-text text-muted"><?php echo htmlspecialchars(t('add_anime.hint.watch_dates'), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
            </div>

            <div class="form-group">
                <label><?php echo htmlspecialchars(t('add_anime.label.genres'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <div class="genre-selection-container">
                        <?php // data-no-enhance: this picker resets its own value and gains
                              // options at runtime (anime_form.js), so it is left native. ?>
                        <select id="genre-select" onchange="addSelectedGenre(this)" data-no-enhance>
                            <option value=""><?php echo htmlspecialchars(t('add_anime.option.choose_from_existing'), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php foreach ($genres as $genre): ?>
                                <option value="<?php echo htmlspecialchars($genre['name']); ?>">
                                    <?php echo htmlspecialchars($genre['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="new-genre-input">
                            <input type="text" id="new-genre" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.new_genre'), ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="button" class="add-button" onclick="addNewGenre()">
                                <i class="fas fa-plus"></i> <?php echo htmlspecialchars(t('add_anime.btn.add_genre'), ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                        </div>
                    </div>
                    <div id="genre-tags" class="genre-tags">
                        <!-- Secilen tur etiketleri JavaScript ile doldurulacak -->
                    </div>
                    <input type="hidden" name="genres" id="genres-input" value="<?php echo htmlspecialchars(implode(',', $selected_genres)); ?>">
                </div>
            </div>

            <div class="form-group">
                <label><?php echo htmlspecialchars(t('add_anime.label.tags'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <div class="tag-input-wrapper" style="position: relative;">
                        <input type="text" id="tag-input" autocomplete="off" maxlength="150"
                               placeholder="<?php echo htmlspecialchars(t('add_anime.ph.tag_input'), ENT_QUOTES, 'UTF-8'); ?>"
                               style="width: 100%; padding: 8px;">
                        <div id="tag-suggestions" class="tag-suggestions"
                             style="display: none; position: absolute; top: 100%; left: 0; right: 0;
                                    background: #fff; border: 1px solid #ccc; border-top: none;
                                    max-height: 200px; overflow-y: auto; z-index: 100;"></div>
                    </div>
                    <div id="selected-tags" class="genre-tags" style="margin-top: 8px;">
                        <!-- Secilen cumle rozetleri JS ile doldurulacak -->
                    </div>
                    <input type="hidden" name="tags" id="tags-input" value="<?php echo htmlspecialchars(implode(',', $selected_tag_names)); ?>">
                    <small class="form-text text-muted">
                        <?php echo htmlspecialchars(t('add_anime.hint.tags'), ENT_QUOTES, 'UTF-8'); ?>
                        <a href="manage_tags.php"><?php echo htmlspecialchars(t('add_anime.link.manage_tags'), ENT_QUOTES, 'UTF-8'); ?></a>
                    </small>
                </div>
            </div>

            <div class="form-group">
                <label for="notes"><?php echo htmlspecialchars(t('add_anime.label.notes'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <textarea name="notes" rows="4"><?php echo htmlspecialchars($anime['notes']); ?></textarea>
                    <small class="form-text text-muted"><?php echo htmlspecialchars(t('add_anime.hint.notes'), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
            </div>

            <div class="form-group">
                <label for="series_name"><?php echo htmlspecialchars(t('add_anime.label.series_name'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <input type="text" name="series_name" id="series_name" list="series-name-list" value="<?php echo htmlspecialchars($anime['series_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.series_name'), ENT_QUOTES, 'UTF-8'); ?>">
                    <datalist id="series-name-list">
                        <?php foreach ($seriesNames as $sn): ?>
                            <option value="<?php echo htmlspecialchars($sn, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <small class="form-text text-muted"><?php echo htmlspecialchars(t('add_anime.hint.series_name'), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
            </div>

            <div class="form-group">
                <label for="media_type"><?php echo htmlspecialchars(t('add_anime.label.media_type'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <select name="media_type" id="media_type">
                        <option value=""><?php echo htmlspecialchars(t('add_anime.option.choose'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="TV" <?php echo ($anime['media_type'] ?? '') === 'TV' ? 'selected' : ''; ?>>TV</option>
                        <option value="Film" <?php echo ($anime['media_type'] ?? '') === 'Film' ? 'selected' : ''; ?>>Film</option>
                        <option value="OVA" <?php echo ($anime['media_type'] ?? '') === 'OVA' ? 'selected' : ''; ?>>OVA</option>
                        <option value="Special" <?php echo ($anime['media_type'] ?? '') === 'Special' ? 'selected' : ''; ?>>Special</option>
                        <option value="ONA" <?php echo ($anime['media_type'] ?? '') === 'ONA' ? 'selected' : ''; ?>>ONA</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="next_in_series"><?php echo htmlspecialchars(t('edit_anime.label.next_in_series'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <select name="next_in_series" id="next_in_series">
                        <option value=""><?php echo htmlspecialchars(t('add_anime.option.choose'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($allAnimes as $a): ?>
                            <option value="<?php echo (int)$a['id']; ?>" <?php echo ((int)($anime['next_in_series'] ?? 0)) === (int)$a['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (!empty($a['media_type'])): ?>(<?php echo htmlspecialchars($a['media_type']); ?>)<?php endif; ?>
                                <?php if (!empty($a['series_name']) && $a['series_name'] === ($anime['series_name'] ?? '')): ?>★<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted"><?php echo htmlspecialchars(t('edit_anime.hint.next_in_series'), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
            </div>

            <div class="form-group">
                <label for="anidb_link"><?php echo htmlspecialchars(t('add_anime.label.anidb_link'), ENT_QUOTES, 'UTF-8'); ?> <span style="color:#d32f2f;">*</span></label>
                <div class="input-area">
                    <input type="url" name="anidb_link" required placeholder="<?php echo htmlspecialchars(t('add_anime.ph.anidb_link'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($anime['anidb_link'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="mal_link"><?php echo htmlspecialchars(t('add_anime.label.mal_link'), ENT_QUOTES, 'UTF-8'); ?> <span style="color:#d32f2f;">*</span></label>
                <div class="input-area">
                    <input type="url" name="mal_link" required placeholder="<?php echo htmlspecialchars(t('add_anime.ph.mal_link'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($anime['mal_link'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="anime_schedule_link"><?php echo htmlspecialchars(t('add_anime.label.animeschedule_link'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <input type="url" name="anime_schedule_link" id="anime_schedule_link" value="<?php echo htmlspecialchars($anime['anime_schedule_link'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.animeschedule_link'), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="button" id="animeschedule-fetch-btn" onclick="fetchAnimeScheduleData()" style="margin-top:8px; padding:8px 14px; background:#5a4ed1; color:#fff; border:none; border-radius:4px; cursor:pointer;">
                        <i class="fas fa-magic"></i> <?php echo htmlspecialchars(t('add_anime.btn.animeschedule_fetch'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <div id="animeschedule-status" style="margin-top:8px; font-size:13px;"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="image"><?php echo htmlspecialchars(t('add_anime.label.image'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <div class="file-upload">
                        <input type="file" name="image" id="image" accept="image/*" onchange="updateFileName(this)">
                        <label for="image" class="file-upload-label">
                            <i class="fas fa-upload"></i> <?php echo htmlspecialchars(t('add_anime.btn.choose_file'), ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <span class="file-name-display" id="file-name">
                            <?php echo basename($anime['image_path']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="button-group">
                <input type="submit" value="<?php echo htmlspecialchars(t('edit_anime.btn.submit'), ENT_QUOTES, 'UTF-8'); ?>" class="submit-button">
                <?php // 1.1.8: admin-only tam-katalog push. Ayri submit - name'i yalniz ?>
                <?php // tiklaninca gonderilir, "Guncelle" bu butonu TETIKLEMEZ. Sunucu ?>
                <?php // tarafi ayrica can('admin') dogrular. Yalniz online + admin gorur. ?>
                <?php if (MULTI_USER_MODE && can($pdo, 'admin')): ?>
                <button type="submit" name="full_catalog_push" value="1" class="submit-button" style="background:#fd7e14;" onclick="return confirm('<?php echo htmlspecialchars(t('edit_anime.confirm.full_push'), ENT_QUOTES, 'UTF-8'); ?>');"><?php echo htmlspecialchars(t('edit_anime.btn.full_push'), ENT_QUOTES, 'UTF-8'); ?></button>
                <?php endif; ?>
                <a href="index.php" class="cancel-button"><?php echo htmlspecialchars(t('add_anime.btn.cancel'), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php // 1.1.5: duzenlenen animenin detay sayfasi butonu, aksiyon butonlarinin yaninda (Anime Listesi ust bolumde kalir). ?>
                <a class="anime-list-button" href="anime_details.php?id=<?php echo (int)$id; ?>"><?php echo htmlspecialchars(t('edit_anime.btn.view_detail'), ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
        </form>
    </div>

    <script>
        const LANG = <?php echo json_encode([
            'no_file'                       => t('add_anime.file.no_file'),
            'alternative_title_placeholder' => t('add_anime.ph.alternative_title'),
            'genre_add_failed'              => t('add_anime.js.genre_add_failed'),
            'create_new_tag_prefix'         => t('add_anime.js.create_new_tag_prefix'),
            'enter_animeschedule_url'       => t('add_anime.js.enter_animeschedule_url'),
            'fetching'                      => t('add_anime.js.fetching'),
            'unknown_error'                 => t('add_anime.js.unknown_error'),
            'field_not_found_suffix'        => t('add_anime.js.field_not_found_suffix'),
            'no_empty_fields'               => t('add_anime.js.no_empty_fields'),
            'fields_filled_prefix'          => t('add_anime.js.fields_filled_prefix'),
            'request_failed_prefix'         => t('add_anime.js.request_failed_prefix'),
            'aired_sync_fetching'           => t('edit_anime.js.aired_sync.fetching'),
            'aired_sync_this_week'          => t('edit_anime.js.aired_sync.this_week'),
            'aired_sync_last_week'          => t('edit_anime.js.aired_sync.last_week'),
            'aired_sync_weeks_ago_fmt'      => t('edit_anime.js.aired_sync.weeks_ago_fmt'),
            'aired_sync_updated_prefix'     => t('edit_anime.js.aired_sync.updated_prefix'),
            'aired_sync_no_change_prefix'   => t('edit_anime.js.aired_sync.no_change_prefix'),
        ], JSON_UNESCAPED_UNICODE); ?>;

        // Paylasilan form JS'i icin baslangic durumu (edit: mevcut secimler).
        const ANIME_FORM = {
            allTags: <?php echo json_encode(array_map(function($t) { return $t['name']; }, $allTags), JSON_UNESCAPED_UNICODE); ?>,
            genres: <?php echo json_encode($selected_genres); ?>,
            tags: <?php echo json_encode($selected_tag_names, JSON_UNESCAPED_UNICODE); ?>
        };

        // edit_anime'a OZGU: yayin tamamlandi anime icin yayin detaylarini sayfa
        // yuklenince gizle (readonly status durumunda toggleBroadcastDetails select
        // bulamayip erken donerdi).
        document.addEventListener('DOMContentLoaded', function() {
            const status = "<?php echo $anime['status']; ?>";
            if (status === 'Yayın Tamamlandı') {
                const bd = document.getElementById('broadcast-details');
                if (bd) bd.style.display = 'none';
            }
        });

        // edit_anime'a OZGU: "Senkronize Et" (Madde C). aired_episodes'i
        // fetch_aired_episodes.php uzerinden gunceller; sunucu DB'yi bu AJAX
        // donmeden once gunceller, input'u da guncel tutariz.
        function syncAiredEpisodes() {
            const btn       = document.getElementById('aired-sync-btn');
            const statusDiv = document.getElementById('aired-sync-status');
            const input     = document.getElementById('aired_episodes');
            if (!btn || !statusDiv || !input) return;

            const csrfInput = document.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfInput ? csrfInput.value : '';
            const animeId   = <?php echo (int)$id; ?>;

            btn.disabled = true;
            statusDiv.style.color = '#555';
            statusDiv.textContent = LANG.aired_sync_fetching;

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('anime_id', animeId);

            fetch('fetch_aired_episodes.php', {
                method: 'POST',
                body: formData,
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    statusDiv.style.color = '#c0392b';
                    statusDiv.textContent = data.error || LANG.unknown_error;
                    return;
                }

                input.value = data.aired_episodes;

                const newVal = data.aired_episodes;
                const oldVal = data.old_value;
                const offset = data.week_offset;

                let weekNote = '';
                if (offset === 0) {
                    weekNote = LANG.aired_sync_this_week;
                } else if (offset === 1) {
                    weekNote = LANG.aired_sync_last_week;
                } else {
                    weekNote = LANG.aired_sync_weeks_ago_fmt.replace('%d', offset);
                }

                statusDiv.style.color = '#27ae60';
                if (data.changed) {
                    statusDiv.textContent = LANG.aired_sync_updated_prefix + ' ' + (oldVal === null ? '?' : oldVal) + ' -> ' + newVal + weekNote;
                } else {
                    statusDiv.textContent = LANG.aired_sync_no_change_prefix + ' ' + newVal + weekNote;
                }
            })
            .catch(err => {
                statusDiv.style.color = '#c0392b';
                statusDiv.textContent = LANG.request_failed_prefix + ' ' + err.message;
            })
            .finally(() => {
                btn.disabled = false;
            });
        }
    </script>
    <script src="js/anime_form.js"></script>
    <script src="js/select_enhance.js" defer></script>
</body>
</html>
