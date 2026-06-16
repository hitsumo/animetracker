# Anime Tracker 1.0.14

**Yayin tarihi:** 16.06.2026

## Iyilestirmeler

- Kronoloji notlari artik liste disa/ice aktarmaya dahil. Liste Ayarlari ->
  Disa Aktar ile alinan yedek, her anime icin o animeye bagli kronoloji
  notlarini da tasir; Ice Aktar bunlari geri yukler. Daha once yedek
  kronoloji notlarini hic icermiyordu, bu yuzden listeyi baska bir kuruluma
  tasirken veya geri yuklerken notlar kayboluyordu.

  Ice aktarma sonuc mesajina bir satir eklendi. Yedekte not varsa mesaj
  ornegin "84 anime ice aktarildi, 0 atlandi. 21 kronoloji notu baglandi,
  0 atlandi." bicimine gelir. Not icermeyen eski yedekler bu ek satiri
  gostermez.

  Bir kronoloji notunun baglanabilmesi icin gosterdigi her iki anime de
  hedef kurulumda bulunmalidir. Karsi anime yoksa o not atlanir ve sayilir;
  ice aktarmanin geri kalani normal tamamlanir.

## Notlar

- Veritabani semasi degismedi.
- Arayuz dili TR ve EN icin guncellendi.
