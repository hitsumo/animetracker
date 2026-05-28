# Anime Tracker 0.5.7

**Sürüm tarihi:** 22 Mayıs 2026
**Tür:** Yeni özellik (otomasyon, simetrik tamamlama)

Bu sürüm otomatik güncelleme ile gelir; ekstra bir şey yapmanıza
gerek yoktur.

## Yeni özellik

- **İzleme durumu artık ters yönde de otomatik güncellenir:** 0.5.6
  ile `+` basarken otomatik durum geçişleri gelmişti (İzlenme
  Planlandı → İzleniyor, tavana ulaşınca → İzlendi). 0.5.7 ile aynı
  otomasyonun ters yönü de eklendi: `−` basarken durum gerekirse
  geri çekilir.

  - **"İzlendi" durumundayken `−` ile bölüm sayısını tavandan aşağı
    düşürürseniz** durum otomatik olarak "İzleniyor"a döner. Yani
    İzlendi + 12/12 üzerinde `−` basışında durum İzleniyor + 11/12
    olur. Bu hem normal durumda (anime bitti, biraz geri sardınız)
    hem de istisnai bir durumda (manuel olarak İzlendi yapılmış,
    tavan altındaki bir animede `−` basıldığında) çalışır.

  - **"İzleniyor" durumundayken bölüm sayısını 0'a düşürürseniz**
    durum otomatik olarak "İzlenme Planlandı"ya döner. Yani
    İzleniyor + 1/12 üzerinde `−` basışında durum İzlenme Planlandı
    + 0/12 olur. Anlam: "izlemeye başlamamışım gibi en başa
    döndüm".

  - **Tek aksiyon, iki adım da olabilir:** Eğer bir animeyi
    "İzlendi" işaretlemişseniz ve son bölümün hemen üstünde
    duruyorsanız (örn. 1/12), tek `−` basışıyla "İzlendi" →
    "İzleniyor" → "İzlenme Planlandı" geçişi tek seferde yapılır.
    Bu, 0.5.6'daki "Planlandı + 11/12 → `+` ile Planlandı →
    İzleniyor → İzlendi" zincirinin aynadaki yansımasıdır.

## Notlar

- **Artık her iki yön de çalışıyor.** 0.5.6 sadece `+` ile ileri
  yön otomasyonu (yeni başlangıç + tavana ulaşma) getirmişti; 0.5.7
  ile `−` ile geri yön otomasyonu (tavandan aşağı düşüş + 0'a
  iniş) eklendi.

- **Ara durumlarda hiçbir otomatik geçiş olmaz.** Örneğin İzleniyor
  + 7/12 üzerinde `−` basışında durum İzleniyor + 6/12 olarak
  kalır — otomasyon sadece sınır geçişlerinde devreye girer (tavan
  veya 0).

- **Manuel düzenleme her zaman serbest.** Otomatik durum geçişleri
  sadece liste içindeki `+` / `−` butonlarına basarken devreye
  girer. "Düzenle" formundan istediğiniz durumu manuel olarak her
  zaman seçebilirsiniz; otomasyon ona karışmaz.

- **Tavan bilgisi olmayan animede İzlendi'den çıkış otomasyonu
  atlanır.** Toplam bölüm sayısı veya yayınlanan bölüm sayısı
  bilinmeyen bir animede manuel olarak İzlendi durumuna alınmışsa,
  `−` basıldığında durum İzlendi olarak kalır (sistem güvenli bir
  geçiş yapamadığı için manuel duruma dokunmaz). Bölüm sayısı 0'a
  inerse İzleniyor → İzlenme Planlandı geçişi yine çalışır
  (mutlak sıfır kontrolü, tavandan bağımsız).

- **Bu sürüm yalnızca arayüz ve kullanım kolaylığı içerir;
  veritabanı yapısında herhangi bir değişiklik veya ek işlem
  gerekmez.** Mevcut izleme verileriniz olduğu gibi korunur.
