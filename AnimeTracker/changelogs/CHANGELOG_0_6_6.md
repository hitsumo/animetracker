# Anime Tracker 0.6.6 - Degisiklikler

**Yayin tarihi:** Mayis 2026 (0.6.5 sonrasi patch)

## Yeni: Istatistik sayfasinda duygu dagilimi

Istatistik sayfasina **"Duygulara Gore"** karti eklendi. Hangi duyguyu
en cok isaretledigini, coktan aza sirali olarak gosterir. 0.6.1'de
eklenen duygu isaretleri (`user_anime_emotion`) artik istatistik
tarafinda da ozetleniyor.

- Mevcut medya / yayin / izleme kartlarinin altinda tam genislik yeni
  kart
- Her duygu kendi rozet renginde, yaninda kac kez isaretlendigi
- Ust satirda ozet: toplam isaret sayisi + kac farkli anime
- Henuz hicbir animeye duygu isareti koymadiysan: tablo yerine bilgi
  notu gosterilir, detay sayfasindan nasil isaret konulacagi hatirlatilir
- Sadece isaretledigin duygular listelenir; hic isaretlenmemis olanlar
  gosterilmez (tum duygu seti detay ve oneri sayfalarinda zaten gorunur)

## Iyilestirme: Pasif duygu butonu daha okunakli

Bir animeye 3 duygu isaretledikten sonra kalan butonlar pasiflesir
(ust sinir dolu). Bu pasif butonlarin solukluk degeri 0.45'ten 0.70'e
cekildi - etiket yazisi artik daha rahat okunuyor. Isaretli (aktif)
butonlar dolu renkli gosterildigi icin aktif / pasif ayrimi net kalir.

## Diger

### Migration

Bu surumde sema degisikligi yok. `migration/0.6.6/upgrade.sql` sadece
surum numarasini ilerletmek icin bos halka.

### i18n

`tr.php` + `en.php`'ye 4 yeni anahtar (istatistik duygu karti icin):
- `statistics.section.by_emotion`
- `statistics.col.emotion`
- `statistics.emotion.summary`
- `statistics.emotion.empty`

Toplam sozluk boyutu: 461 -> 465 anahtar (TR/EN paralel).

### Tek dosya degisikligi

Yalnizca `statistics.php` (+ iki sozluk + bir CSS satiri) degisti.
Helper'lar, tablo semasi ve duygu rozet renkleri 0.6.1'den beri
hazirdi - yeni altyapi eklenmedi.
