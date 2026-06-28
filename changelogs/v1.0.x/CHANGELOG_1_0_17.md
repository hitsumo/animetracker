# Anime Tracker 1.0.17

**Yayin tarihi:** 23.06.2026

## Duzeltmeler

- Online kurulumda yeni bir kronoloji notu eklendiginde, not artik eklendigi
  anda merkez kataloga otomatik gonderiliyor. Onceden eklenen not yalniz yerel
  kuruluma kaydediliyor ve Liste Ayarlari sayfasinda "katalogla senkronize
  degil" uyarisi gosteriliyordu. Artik ekleme aninda gonderim yapiliyor ve
  gonderim basariyla tamamlaninca bu uyari kendiliginden kalkiyor.

- Gonderim basarisiz olursa not yine yerel kuruluma kaydedilir (kaybolmaz) ve
  ana sayfada bir uyari bandi gosterilir; sonraki bir katalog
  gonderimi tekrar dener.

## Notlar

- Veritabani semasi degismedi.
- Bu degisiklik yalniz online (cok kullanicili) kurulumu etkiler. Self-host
  kurulumda kronoloji notu ekleme davranisi degismedi.
- Kronoloji notu SILME hala merkeze tasinmaz; silme yalniz yerel kurulumu
  etkiler.
- Arayuz metinleri icin yeni bir ceviri eklenmedi; mevcut uyari metni
  kullanildi.
