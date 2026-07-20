# Anime Tracker 1.1.11

**Yayin tarihi:** 2026-07-15

## Yeni: AniList ice aktarma kaynak limiti

- **Online (cok kullanicili) modda, normal bir uye en fazla 3 FARKLI AniList
  hesabindan ice aktarabilir.** Boylece tek bir uye, sinirsiz sayida baska
  kisinin herkese acik listesini cekip moderasyon kuyrugunu / katalogu
  bogamaz.
  - **Ayni hesap sinirsiz yeniden senkronlanabilir.** Daha once ice aktardigin
    bir hesabi istedigin kadar tekrar cekebilirsin; bu yeni bir slot harcamaz
    (kisi AniList'i aktif kullanip bir sure sonra donup yeniden cekebilir - bu
    mesru).
  - **Slot yalnizca gercek, basarili bir ice aktarmada dolar.** Yanlis yazilan
    ad, onizleyip vazgecme veya hata bir slot yakmaz; dilediginiz kadar
    onizleyebilirsiniz.
  - Kullanici adi buyuk/kucuk harf duyarsiz sayilir: `Mahmut`, `mahmut` ve
    `MAHMUT` tek slottur.

## Muafiyetler

- **Self-host (tek kullanicili) kurulumda limit HIC uygulanmaz** - tek sahip,
  istedigi kadar farkli ad cekebilir.
- **Moderator ve ustu roller muaftir** - toplu tohumlama onlarin mesru isidir.

## Yonetici kontrolleri

- **Limit ayarlanabilir.** Yonetici Yetenekleri sayfasindan "AniList ice
  aktarma kaynak limiti" alani ile farkli hesap sayisi degistirilir
  (varsayilan 3). **0 yazmak limiti kaldirir (sinirsiz)** - acil kapatma valfi.
- **Yonetici bir kullanicinin limitini sifirlayabilir.** Kullanici Yonetimi
  sayfasinda, kaynak kullanmis her uyenin satirinda "Sifirla" butonu vardir;
  o kullanicinin kaydettigi kaynaklar temizlenir ve yeniden tam hak kazanir.

## Nasil calisir (teknik)

- Yeni app-tarafi tablo `anilist_import_sources` her uyenin kullandigi FARKLI
  AniList adlarini tutar (kullanici basina bir satir, `(user_id, kullanici_adi)`
  UNIQUE). Kullanilan kaynak sayisi = o kullanicinin satir sayisi.
- Kontrol iki hatlidir: onizlemede (AniList'e istek ATMADAN, bosa cagri yok) ve
  ice aktarma aninda (ikinci savunma + kayit).
- Limit degeri `settings.anilist_import_source_limit` anahtarinda tutulur; ayri
  bir sema alani gerektirmez.
- **Merkez katalog etkisi yoktur** - bu tablo yalnizca app tarafindadir, merkez
  katalog sunucusuna hic gitmez; sunucuda elle bir islem GEREKMEZ.

## Iyilestirme: Uzun acilir menuler 8 satirla sinirlandi

- **Uzun acilir menuler (8'den fazla secenek) artik en fazla 8 satir gosterir,
  gerisi kaydirilir.** Ornegin ana listedeki "Ture Gore Filtrele" ve
  form/detay sayfalarindaki uzun secim kutulari eskiden acildiginda neredeyse
  tum ekrani kapliyordu; simdi kompakt, kaydirilabilir menuler.
- **Tum site genelinde uygulanir:** kural tek bir menuye ozel degil - 8'den
  fazla secenegi olan HER yerel `<select>` otomatik kapsanir; kisa menuler
  (<=8) oldugu gibi yerel kalir.
- Masaustunde (fare) yerel `<select>` kompakt bir menuye donusturulur; secim
  yine ayni form alanina yazilir (gonderim/filtreleme davranisi degismez).
  Dokunmatik cihazlarda yerel secici korunur. JS kapaliysa yerel liste calisir.
- `required` menuler dogru calismaya devam eder (yerel select gizlenir ama
  odaklanabilir kalir, tarayici dogrulamasi bozulmaz). Cok ozel bir menu
  (or. tur EKLEME secicisi, kendi degerini sifirlar) `data-no-enhance` ile
  muaf tutulabilir.

## Guvenlik / dagitim: ana uygulama .htaccess

- **Ana uygulama belge kokune (`files/`) ozel bir `.htaccess` eklendi.** http
  isteklerini 301 ile https'e yonlendirir + HSTS gonderir, boylece oturum
  cerezi (db.php'de HTTPS'te `Secure` bayragiyla yazilir) her zaman gonderilir
  ve "https'te giriliyim ama http'de cikis gorunuyorum" durumu ortadan kalkar.
- Ayrica: dizin listelemesi kapali, hassas dosyalar reddedilir (config.php,
  *.sql, *.md/*.txt, dotfile'lar), include/arac dizinlerine (functions/,
  migration/, tek_kullanimlik/) web erisimi ve uploads/ altinda PHP calismasi
  engellenir. PHP varsayilan-reddedilmez (uygulama endpoint'leri acik kalir) -
  bu, MERKEZ KATALOG sunucusunun default-deny `.htaccess`'inden farklidir; ikisi
  karistirilmamalidir.

## Sema / migration

- `migration/1.1.11/upgrade.sql` yeni tabloyu olusturur (idempotent,
  `CREATE TABLE IF NOT EXISTS`). Taze kurulumlar tabloyu `schema.sql`'den alir.

## Degisen / yeni dosyalar

- functions/anilist_import_helpers.php (limit yardimcilari)
- list_settings.php (onizleme + ice aktarma limit kontrolu, kaynak kaydi)
- admin/admin_capabilities.php (global limit ayari)
- admin/admin_users.php (kullanici basi limit sifirlama)
- lang/tr.php, lang/en.php, lang/admin_tr.php, lang/admin_en.php
- schema.sql (anilist_import_sources tablosu)
- migration/1.1.11/upgrade.sql (yeni)
- js/select_enhance.js (yeni - genel acilir menu gelistiricisi)
- css/components.css (ozel acilir menu stilleri)
- index.php, add_anime.php, edit_anime.php, list_settings.php,
  anime_details.php, admin/admin_users.php, admin/admin_invites.php
  (select_enhance.js script etiketi; add/edit'te tur ekleme secicisine
  data-no-enhance)
- .htaccess (yeni - ana uygulama docroot: https zorlama + HSTS + sertlestirme)
- version.txt
