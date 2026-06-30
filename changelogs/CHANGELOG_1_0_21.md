# Anime Tracker 1.0.21

**Yayin tarihi:** 29.06.2026

## Duzeltmeler

- Yayini devam eden ve toplam bolum sayisi bilinmeyen bir animede, son
  yayinlanan bolume yetismek artik izleme durumunu yanlislikla "Izlendi"
  yapmiyor; durum "Izleniyor" olarak kaliyor. Yeni bir bolum yayinlandiginda
  da dogru sekilde "Izleniyor" kalir. Izleme durumu yalnizca dizi gercekten
  bittiginde "Izlendi" olur: bilinen toplam bolume ulasildiginda ya da yayini
  tamamlanmis bir dizinin tum bolumleri izlendiginde.

## Notlar

- Bu surum veritabani semasini degistirmez.
- Bu duzeltmeden once yanlislikla "Izlendi" durumunda kalmis bir kayit, izlenen
  bolum sayisini bir azaltinca (eksi) ya da Duzenle ekranindan dogru duruma
  geri doner.
