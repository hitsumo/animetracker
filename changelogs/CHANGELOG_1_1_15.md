# Anime Tracker 1.1.15

**Yayın tarihi:** 2026-07-17

## Yeni: Kronolojide hikaye sırası

- **Bir kronoloji işareti artık iki ekleme noktası taşıyabilir.** Şimdiye
  kadar bir işaret, ilgili animenin (film, OVA, ...) yalnızca **yayın
  sırasındaki** yerini (gerçekte çıktığı bölüm) tutuyordu. Artık bir işaret
  ayrıca bir **hikaye sırası** noktası da taşıyabilir: anlatı içinde izlenmesi
  önerilen bölüm. Örnek: ilk Card Captor Sakura filmi 46. bölümden sonra çıktı
  ama 35. bölümden sonra izlenmesi önerilir.
- **İkinci sayıyı yalnızca farklıysa girersiniz.** Bir işaretin hikaye noktası
  boş bırakılırsa "yayınla aynı" kabul edilir; böylece mevcut işaretler
  değişmez ve iki sırada da aynı yerde görünür. Hikaye noktasını yalnızca
  gerçekten ayrışan işaretlere eklersiniz.
- **Tek düğme görünümü değiştirir: yayın → hikaye → ikisi.** Anime detay
  sayfasında (kronoloji notları listesi) ve Kronoloji sayfasında tek bir düğme
  sırayla yayın sırasını, hikaye sırasını ya da ikisini alt alta gösterir.
  Düğmeye tıklamak yalnızca o oturumdaki görünümü değiştirir; kaydettiğiniz
  varsayılanı ezmez.
- **Liste Ayarları'nda kayıtlı varsayılan.** Yeni "Kronoloji Görünümü" tercihi
  hangi sıranın varsayılan açılacağını belirler: yayın (varsayılan), hikaye ya
  da ikisi. Kişi bazlıdır, yalnızca sizi etkiler.
- **İki nokta da mevcut işaretlerde satır içinde düzenlenebilir.** Notlar
  listesinde her satırın yanındaki küçük alan o satırın bölümünü ayarlar:
  Yayın Sırası listesi yayın noktasını, Hikaye Sırası listesi hikaye noktasını
  (boş bırakınca temizlenir) düzenler — işareti silip yeniden oluşturmaya gerek
  kalmadan. İki kutu bağımsızdır; birini değiştirmek diğerini etkilemez.

## Değişmeyen davranış

- **Aktif "sırada bunu izle" uyarısı hâlâ yalnızca yayın sırasına göre
  çalışır.** İlerledikçe detay sayfasında çıkan hatırlatma değişmedi; yeni
  hikaye sırası bir listeleme/görünüm özelliğidir, ikinci bir uyarı değil.

## Nasıl çalışır (teknik)

- Yeni ve boş olabilen `chronology_markers.story_after_episode` kolonu hikaye
  noktasını tutar. `NULL` "ayrışma yok" demektir; hikaye görünümü
  `COALESCE(story_after_episode, after_episode)` ile yayın noktasına düşer.
  Kolon bilerek işaretin UNIQUE anahtarına dahil **edilmedi**, böylece katalog
  yeniden push'unda `ON DUPLICATE KEY UPDATE` ile güncellenir.
- Görünüm modu (yayın / hikaye / ikisi) şu sırayla çözülür: geçici oturum
  değeri (döngü butonu), ardından kayıtlı kişisel tercih
  (`chrono_display_mode`), ardından `release`. Düğme `set_chrono_mode.php`'ye
  POST eder; Liste Ayarları aynı endpoint'e `persist=1` ile POST ederek kalıcı
  varsayılanı yazar ve oturum değerini temizler.
- Hikaye noktası tüm katalog wire format'ından geçer, böylece işaretin geri
  kalanı gibi tüm kullanıcılarla paylaşılır: yerel push, sunucu kaydı, katalog
  çekme/içe aktarma, üye katalog-isteği yolu ve admin onayı hepsi taşır.

## Şema / migration

- `migration/1.1.15/upgrade.sql`, `chronology_markers` tablosuna
  `story_after_episode` kolonunu ekler (tek bir `ALTER TABLE`; tekrar
  çalıştırmada yinelenen-kolon hatası yok sayılır) ve sürümü 1.1.15'e yükseltir.
- **Merkez katalog için elle adım (yalnızca online):** merkez katalog
  veritabanı ayrı bir kurulumdur ve otomatik migration çalıştırıcısı yoktur.
  Hikaye noktalarını oraya göndermeden önce merkez katalog DB'sinde bir kez
  çalıştırın:
  `ALTER TABLE chronology_markers ADD COLUMN story_after_episode INT NULL AFTER after_episode;`
  Bu yapılana kadar sunucu her hikaye noktasını NULL tutar; self-host
  kurulumları etkilenmez (yerel migration otomatik çalışır).

## Değişen / yeni dosyalar

- files/migration/1.1.15/upgrade.sql (yeni: story_after_episode kolonu)
- files/schema.sql (yeni kurulum için kolon + açıklama)
- files/functions/series_helpers.php (getChronologyMarkers sıralama parametresi;
  chrono_current_mode / chrono_next_mode / chrono_display_modes / chrono_mode_label)
- files/add_chronology_marker.php (story_after_episode al + doğrula)
- files/update_chronology_marker.php (yeni: işaretin hikaye noktasını set/temizle)
- files/set_chrono_mode.php (yeni: döngü butonu + persist=1 varsayılan)
- files/anime_details.php (ekleme formunda hikaye alanı; iki-nokta gösterimi;
  satır içi hikaye düzenleme; döngü butonu; mod-duyarlı marker listesi)
- files/chronology.php (mod-duyarlı timeline; "ikisi" = iki başlıklı liste; döngü butonu)
- files/list_settings.php (Kronoloji Görünümü tercihi; yedek export/import
  marker payload'ında story)
- files/catalog_import.php, files/admin/catalog_push.php,
  files/admin/admin_catalog_requests.php, files/admin/admin_sync_example.php,
  catalog_server/catalog.php, catalog_server/admin_push.php
  (katalog wire format boyunca story_after_episode)
- files/help/help_series.php, files/lang/tr.php, files/lang/en.php
  (yayın/hikaye etiketleri, mod butonu, ayarlar, yardım metni)
- files/css/series.css (döngü butonu, bölüm başlıkları, satır içi düzenleme)
- files/version.txt
