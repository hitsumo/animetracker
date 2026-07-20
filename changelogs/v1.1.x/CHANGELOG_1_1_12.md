# Anime Tracker 1.1.12

**Yayin tarihi:** 2026-07-15

## Yeni: Davetiye talep limiti

- **Yonetici, ayni anda kuyrukta bekleyebilecek davetiye talebi sayisina bir
  limit koyabilir (or. 50, 70, 100).** Bekleyen talep sayisi bu limite
  ulasinca herkese acik davetiye talep formu (request_invite.php) kapanir ve
  yeni talep alinmaz. Ziyaretciye "kontenjan dolu, sonra tekrar dene" bildirimi
  gosterilir.
- **Limit BEKLEYEN talepleri sayar; kendini onarir.** Yonetici bir talebi
  davet edince veya reddedince o slot yeniden acilir - yeni talep alinabilir
  hale gelir. Boylece limit, gecmis tum talepleri degil yalnizca su an isleme
  bekleyenleri baz alir.
- **Limiti istediginiz zaman kaldirabilirsiniz.** Alana `0` yazmak limiti
  kaldirir (sinirsiz) - formu tekrar tam acar.
- Kontrol iki hatlidir: form GET'te kapaliysa hic gosterilmez, ayrica gonderim
  (POST) tarafinda da sunucu yeniden dogrular (dogrudan POST ile asilamaz).

## Yeni: Kayit ekrani duyurusu

- **Yonetici, kayit sayfasinda (register.php) gosterilecek serbest metinli bir
  duyuru yazabilir.** Ornek: "Davetiye 50 kisiliktir." veya "Yeni davetiye
  slotu 1 hafta sonra acilacaktir." Metin secmeli/kaliplasmis degildir; yonetici
  ne yazarsa o gosterilir.
- **Bos birakmak duyuruyu gizler.** Duyuru alani bosken kayit ekraninda hicbir
  not cikmaz.
- Metin ham saklanir, ciktida guvenli kacislanir (HTML enjeksiyonu yok); satir
  sonlari korunur.

## Yonetici kontrolleri

- Her iki ayar da **Kayit ve Davetler** (admin_invites.php) sayfasindadir ve
  **yalnizca yonetici** tarafindan degistirilebilir:
  - "Davetiye Talep Limiti" karti (Davetiye Talepleri sekmesi): sayisal alan
    0..100000, 0 = sinirsiz. Kartta canli durum gosterilir (bekleyen / limit,
    form acik mi kapali mi).
  - "Kayit Ekrani Duyurusu" karti (Davetler sekmesi): 2000 karaktere kadar
    serbest metin.

## Nasil calisir (teknik)

- Iki yeni deger `settings` anahtar/deger tablosunda tutulur
  (`invite_request_limit`, `register_announcement`); ayri bir sema alani
  gerektirmez.
- Yeni yardimci `invite_request_limit_state($pdo)` limiti ve bekleyen talep
  sayisini okuyup formun acik/kapali oldugunu belirler; sorgu hatasinda
  guvenli tarafta kalir (formu ACIK birakir, mesru ziyaretciyi kilitlemez).
- **Merkez katalog etkisi yoktur** - ayarlar yalnizca app tarafindadir, merkez
  katalog sunucusuna hic gitmez; sunucuda elle bir islem GEREKMEZ.

## Duzeltme: Yardim sayfasi Turkce karakterleri

- **Yardim sayfalarinin (help.php ve help/ alt sayfalari) icerigi ASCII-guvenli
  yazilmisti** (or. "Izleme Durumlari", "Nasil Calisir?", "Yardim Icindekiler").
  Tum yardim metinleri artik dogru Turkce karakterlerle gosterilir
  (İ, ç, ğ, ı, ö, ş, ü). Icerik `lang/tr.php` icindeki `help.*` anahtarlarindadir;
  HTML etiketleri, kod ornekleri, capa (#anchor) baglantilari ve teknik terimler
  (JST, TZ, AnimeSchedule, broadcast_day vb.) korunmustur. Ingilizce yardim
  (`lang/en.php`) degismedi.
- **Yardim sayfasi alt bilgisindeki "GitHub sayfasi" ifadesi artik tiklanabilir
  bir baglantidir** (https://github.com/hitsumo/animetracker); daha once duz
  metindi. Hem Turkce hem Ingilizce yardimda.
- **Yardim ana sayfasina (help.php) iletisim e-postasi eklendi:** basliktaki
  mavi cizginin hemen altinda, giris metninin ustunde
  `at@animetracker.uzakdiyarlar.com` (tiklanabilir mailto). Hem Turkce hem
  Ingilizce (`help.contact`).

## Sema / migration

- `migration/1.1.12/upgrade.sql` iki ayar anahtarini varsayilanlariyla tohumlar
  (`INSERT IGNORE`, mevcut degeri ezmez) ve surumu 1.1.12'ye tasir. Sema
  degisikligi yoktur.

## Degisen / yeni dosyalar

- functions/auth_helpers.php (invite_request_limit_state + submit'te 'full')
- request_invite.php (kuyruk doluysa formu gizle + kapali bildirimi)
- admin/admin_invites.php (limit + duyuru kartlari, admin-only POST'lar)
- register.php (duyuru banneri)
- help.php (iletisim e-postasi satiri)
- lang/tr.php (davetiye + yardim Turkce karakter duzeltmesi + help.contact),
  lang/en.php (davetiye + footer/iletisim linkleri), lang/admin_tr.php,
  lang/admin_en.php
- migration/1.1.12/upgrade.sql (yeni)
- version.txt
