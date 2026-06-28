# Anime Tracker 1.0.19

**Yayin tarihi:** 27.06.2026

## Yenilikler

- Yayinlanan bolum senkronu artik komut satirindan da calistirilabilir
  (Linux'ta cron, Windows'ta Gorev Zamanlayici). Boylece otomatik guncelleme,
  birinin Liste Ayarlari sayfasini acmasina bagli kalmadan, zamanlanmis bir
  gorevle istedigin siklikta calisabilir. Yeni dosya: sync_aired.php (kurulum
  tamamen istege baglidir).

## Duzeltmeler

- IPv6 cikisi calismayan ama animeschedule.net'i IPv6 adresine cozen
  sunucularda yayin senkronu baglantiyi kuramayip zaman asimina ugruyordu.
  AnimeSchedule istekleri artik IPv4 kullanacak sekilde sabitlendi.

## Notlar

- Komut satiri senkronu her calistirildiginda calisir; ne siklikta calisacagini
  kurdugun zamanlanmis gorev belirler. Liste Ayarlari sayfasinin gunde bir kez
  calisan tetikleyicisi, o gun senkron zaten yapildiysa kendi islemini atlar.
- Zamanlanmis gorevin kurulumu isletim sistemine gore farklidir ve pakete dahil
  degildir; isteyen kullanici kendi gorevini kurar.
- Veritabani semasi degismedi.
- Web arayuzunun davranisi degismedi.
