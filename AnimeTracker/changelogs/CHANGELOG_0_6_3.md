# Anime Tracker 0.6.3

**Sürüm tarihi:** 27 Mayıs 2026
**Tür:** Düzeltme (şema senkronizasyonu + sunucu uyumlanması)

Bu sürüm otomatik güncelleme ile gelir; ekstra bir şey yapmanıza
gerek yoktur. Mevcut verilerinize dokunulmaz - migration sadece
eksik kolonları ekler, var olan kolonlara veya satırlara müdahale
etmez.

## Düzeltme

- **Eski kurulumlarda eksik olabilen iki kolon eklendi.** `end_date`
  (animenin son bölüm yayın tarihi) ve `user_synopsis` (animeye
  kendi yorumunuzu yazdığınız ikinci konu kutusu) kolonları bir
  süre önce uygulamaya eklenmişti ama eski kurulumlara migration
  ile yansımamıştı. Bu sürüm boşluğu kapatır.

- **Migration idempotent.** Kolonlar zaten varsa MariaDB "duplicate
  column" uyarısı verir, migration manager bunu tanır ve sessizce
  geçer. Yani lokal kurulumunuzda kolonlar varsa hiçbir şey
  olmaz; eski bir kurulumdaysanız iki kolon eklenir.

## Bilinen Davranışlar

- **`user_synopsis` boş kalır.** Bu kolon kullanıcıya özel veridir,
  hiçbir senkronizasyondan etkilenmez. Anime detay sayfasında
  doldurursanız "Kendi Yorumum" başlığı altında görünür, boş
  bırakırsanız gizlenir.

- **`end_date` yayın tamamlanmış animeler için kullanışlıdır.**
  Yayın durumu "Yayın Tamamlandı" olan animelerin son bölüm
  tarihini düzenleme sayfasından girebilirsiniz. Devam eden
  animelerde NULL kalır.

## Teknik Notlar

Bu sürümün migration kapsamı dar tutuldu (tek dosya, iki ALTER).
Ancak sunucu API tarafında ve admin senkronizasyon zincirinde birkaç
şema uyumlama işi de aynı sürümün parçası olarak yapıldı. Bunlar
self-host kullanıcılara doğrudan dokunmaz (admin ve sunucu tarafı
kodları); yine de release kayıt altında durması için listeleniyor.

### Migration (kullanıcıya gider)

- 0.6.2'den 0.6.3'e geçiş tek migration adımı: iki ALTER TABLE.
- Şema değişikliği sadece animes tablosunda, başka hiçbir tabloya
  dokunulmaz.
- Geri alma gerekirse (örneğin sorun çıkarsa), iki kolon nullable
  olduğu için DROP COLUMN ile temiz geri dönülebilir; veri kaybı
  riski sadece dolduran kullanıcılar için söz konusudur.

### Sunucu API (sicakcikolata.com tarafı, self-host'a dokunmaz)

- **`admin_push.php` yeniden yazıldı.** Eski sürüm `animes.genres`
  text kolonuna yazıyordu; bu kolon 0.6'da `anime_genres` join
  tablosuna geçirilmişti ama push endpoint'i o sürümde uyumlanmamıştı.
  Sonuç: admin sync HTTP 500 veriyordu. Yeni sürüm tag pattern'ine
  paralel olarak `genres` master tablosunu ve `anime_genres` join
  tablosunu kullanır (idempotent: race-safe `INSERT IGNORE` + UNIQUE
  constraint).

- **`admin_push.php` `end_date` kolonunu da yazar artık.** UPDATE/
  INSERT/params listesi güncellendi. Lokal admin tarafından girilen
  son bölüm tarihleri sunucuya da akar.

- **`catalog.php` iki bug birden düzeltildi.** SELECT'inden artık
  var olmayan `genres` kolonu çıkarıldı (önceki sürümde catalog.php
  sessizce kırıktı, sadece 1 saatlik cache TTL ile gizlenmişti).
  Yerine `anime_genres + genres` JOIN'ı ile CSV üreten bir blok
  eklendi (wire format değişmedi, lokal client tarafı aynı CSV'yi
  bekler). `end_date` de SELECT'e eklendi.

### Lokal admin tarafı

- **`admin_sync.php` SELECT'i `end_date` çeker.** Sunucuya tam
  payload gönderebilmek için. Diğer kolonlar zaten doğruydu.

### Sonraki sürümler için disiplin notu

Bu sürümün ortaya çıkardığı bulguları KARARLAR Bölüm 2'ye disiplin
maddesi olarak işledik: şema değişikliği yapıldığında schema.sql
ile birlikte migration/{sürüm}/upgrade.sql aynı commit'te yazılmalı.
Aksi takdirde yeni kurulumlar doğru gelir, mevcut kurulumlar
atlanır (bu sürümün doğuş sebebi olan yarım deploy pattern'i).
