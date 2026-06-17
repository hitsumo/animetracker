# Anime Tracker 1.0.15

**Yayin tarihi:** 17.06.2026

## Yeni

- Ice aktarmadan gelen kronoloji notlari artik moderatore ulasiyor. Online
  bir uye listesini Ice Aktar ile yukledginde, katalogda olmayan bir anime
  oneri olarak moderasyon kuyruguna dusuyordu; o animeye bagli kronoloji
  notlari ise kayboluyordu. Artik bu notlar oneriyle birlikte tasiniyor ve
  moderator oneriyi onayladiginda ilgili notlar otomatik baglaniyor.

  Oneri listesinde her satir kac kronoloji notu tasidigini gosteren bir
  Kronoloji sutunu eklendi; moderator onaylamadan once gorebilir. Onaydan
  sonra sonuc mesajina "X kronoloji notu baglandi, Y atlandi" satiri
  eklenir.

  Bir notun baglanabilmesi icin gosterdigi karsi anime de katalogda
  bulunmalidir. Karsi anime yoksa o not atlanir ve sayilir; onayin geri
  kalani normal tamamlanir. Ayni partide onaylanan iki anime birbirine not
  veriyorsa bunlar da baglanir.

## Notlar

- Veritabani semasi degisti: catalog_requests tablosuna pending_markers
  alani eklendi. Guncellemeyi calistirmadan once veritabani yedegi alin.
- Davranis degisikligi yalniz online (cok kullanicili) kurulumu etkiler.
  Self-host kurulumda oneri akisi zaten olusmadigi icin davranis degismez.
- Arayuz dili TR ve EN icin guncellendi.
