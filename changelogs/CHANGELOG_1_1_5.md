# Anime Tracker 1.1.5

**Yayin tarihi:** 2026-07-11

## Yeni

- **Ekleme ve duzenlemede kaydettikten sonra ayni sayfada kalinir.** Onceden anime
  ekleme ve duzenleme formunda "Ekle" / "Guncelle" denince liste sayfasina (index)
  donuluyordu. Artik:
  - **Duzenleme:** "Guncelle" sonrasi ayni duzenleme sayfasinda kalinir; ustte
    "Degisiklikler kaydedildi" bandi gorunur ve kayitli degerler taze yuklenir.
  - **Detay butonu:** duzenleme sayfasina ilgili animenin detay sayfasina giden
    bir buton ("Anime Detayi") eklendi (kaydet sonrasi bu sayfada kalinca detaya
    dogrudan erisim).
  - **Ekleme:** kaydedince yeni animenin duzenleme sayfasina gecilir (eklediginle
    duzenlemeye / gozden gecirmeye devam). Online'da yalniz duzenleme yetkisi olanlar
    (moderator ve ustu) icin; normal uye eskisi gibi listeye doner.
  - **Yenileme guvenli:** islem hala Post-Redirect-Get - sayfa yenilendiginde
    (F5) form tekrar gonderilmez.

- **Istatistikte bir duyguya tiklayinca o duygudaki animeler listelenir.**
  Istatistik sayfasindaki kisisel duygu dagiliminda bir duygu rozetine tiklandiginda,
  liste sayfasi o duyguyla isaretledigin animelere filtrelenir.
  - **Cok animede kabarmaz:** liste, index'in mevcut sayfalama / siralama / arama
    altyapisindan gecer; ayri bir liste yoktur, bu yuzden cok animede de 10'ar
    (veya secilen sayfa boyutunda) sayfalanir.
  - **Aktif filtre bandi:** filtre aciktayken ustte "Duygu filtresi: X" bandi ve
    "Filtreyi temizle" bagi gosterilir.
  - **Kisisel + guvenli:** filtre yalniz senin isaretlerine scope'ludur; global
    duygu dagilimi (baskalarinin verisi) tiklanmaz. Duygu degeri bilinen listeyle
    dogrulanir.

## Notlar

- Sema veya migration degisikligi yoktur (migration/1.1.5 no-op halka). Iki ozellik
  de yalniz mevcut tablolari ve UI / yonlendirme akisini degistirir.
- Kaydet-sonrasi yonlendirme POST + CSRF ve ayni-host akisini korur; ekleme
  yonlendirmesi role gore secilir (edit_anime moderator-kapili oldugu icin normal
  uye yeni animenin duzenleme sayfasina atilmaz - eski davranisla listeye doner).
- Duygu filtresi mevcut filtrelerle (tur, izleme durumu, harf, arama, siralama,
  sayfalama) birlikte calisir ve sayfalar arasi korunur.

## Degisen dosyalar

- add_anime.php, edit_anime.php
- index.php, statistics.php
- lang/tr.php, lang/en.php
- css/components.css
- version.txt
- migration/1.1.5/upgrade.sql
