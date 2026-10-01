# Anime Tracker 0.6.5 - Degisiklikler

**Yayin tarihi:** Mayis 2026 (0.6.4.2 sonrasi patch)

## Yeni: Recommendations sayfasinda duygu filtresi

"Ne Izlesem?" sayfasinda cumle (tag) filtresinin yaninda artik **duygu
filtresi** de var. Onceden 0.6.1'de eklenmis olan duygu isaretleri
(`user_anime_emotion` tablosu) sonunda arama tarafinda da kullaniliyor.

### Calisma mantigi - OR (kepce sistemi)

Cumle ve duygu paralel iki kepce gibi calisir. Mevcut tag patternine
simetrik:

- "Okulda gecsin" + "Guldurdu" secersen: bu cumleye sahip animeler
  **veya** bu duyguyu isaretledigin animeler gelir
- Score = eslesen cumle sayisi + eslesen duygu sayisi
- Yuksek score'lu animeler ust sirada
- AND degil OR mantigi - sonuc bos kalmaz

### UI

- Cumle paneli (mevcut) altinda yeni **Duygulari Goster** dugmesi
- Acildiginda 9 duygu rozet seklinde gosterilir
  (renkler 0.6.1'deki emotion-badge-* class'larindan gelir)
- Henuz hicbir animeye duygu isareti koymamissan: panel yerine bilgi
  notu gosterilir, ve detay sayfasindan nasil isaret konulacagi
  hatirlatilir
- Sonuc kartlarinda eslesen duygular badge olarak listelenir
  (mevcut cumle pill'lerinin altinda)

### Devir borc kapanisi

KARARLAR_GECMIS'te 0.6.1 sonrasi acik kalemler arasinda yer alan
"filtre/recommendations entegrasyonu" maddesi 8 ay sonra kapatildi.
Spec'te 0.6.3 veya sonrasi diye yazmisti, 0.6.5'e dustu.

## Mimari notlar

- Recommendations'in DB tarafi `idx_emotion` index'i 0.6.1'de
  zaten "placeholder for filter queries" olarak schema.sql'e
  konmustu - 0.6.5 bu altyapiyi sonunda kullaniyor
- Tag SQL'i bozulmadi. Emotion query ayri pass olarak calisir,
  PHP-side merge edilir. Cartesian product COUNT inflation riski
  yok (iki tablonun cross-JOIN'ine girmiyoruz)
- Mevcut tag-only UX bozulmadi. Eski tag anahtarlari aynen tutuldu
  (count, no_match, group.matched). Emotion seciliyse `_combined`
  varyantlar devreye girer
- Tek dosya degisikligi (recommendations.php). schema.sql, helpers,
  CSS class'lari hicbiri degismedi - hepsi 0.6.1'de hazirdi

## Diger

### Migration

Bu surumde sema degisikligi yok. `migration/0.6.5/upgrade.sql`
sadece `settings.version` bump'lamak icin bos halka (KARARLAR
Bolum 2 "bos migration kurali" - 0.5.5 yakin kacisi ve 0.6.4.1
atlanma fiyaskosu derslerinin gerekceledigi disiplin).

### i18n

`tr.php` + `en.php`'ye 8 yeni anahtar eklendi (recommendations
emotion bloğu icin):
- `recommendations.emotion.toggle.show` / `hide` / `count_selected`
- `recommendations.emotion.empty_marks`
- `recommendations.matched.emotion_prefix`
- `recommendations.no_match_combined`
- `recommendations.result.count_combined`
- `recommendations.group.matched_combined`

Toplam sozluk boyutu: 453 -> 461 anahtar (TR/EN paralel).

## Bilinen acik kalemler (devir borc, 0.6.6 veya sonrasi)

0.6.1'den kalan diger acik kalemler henuz kapanmadi:

- **Statistics.php emotion sayimi**: "en cok hangi duyguyu hissetmis"
  istatistigi yok
- **Index.php quick-tap emotion**: KARARLAR'da "acik tasarim sorusu"
  olarak duruyor, karar verilmedi
- **Pasif buton opacity 0.45 vs 0.55**: UX karari
- **Set genisletme**: kullanim sonrasi karar (Rahatlatti / Duygulandirdi /
  Ilham verdi / Bagimlilik yapti adaylari)
