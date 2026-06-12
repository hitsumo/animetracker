# Anime Tracker 1.0.9 - Degisiklikler

**Yayin tarihi:** Haziran 2026 (internal milestone)

> Not: Bu surum istatistik sayfasini, anime listesindeki izleme durumu
> gosterimini ve kuratore yonelik kucuk bir hatirlatmayi ilgilendirir.
> Surum numaralari internal gelistirme adimlaridir. Bu surum sema
> (veritabani yapisi) degisikligi icermez.

## Ozet

Bu surum istatistik sayfasini iki sekmeye ayirir (kullanici ve global);
global sekmede tum kullanicilarin duygu dagilimini gosterir; ve izleme
durumu icin yeni bir "Secim Yapilmamis" durumu ekler - kullanicinin henuz
dokunmadigi animeler artik "Izlenme Planlandi" yerine "Secim Yapilmamis"
gorunur.

## Istatistik sayfasi iki sekmeli

Istatistik sayfasi artik iki sekme icerir. "Kullanici Istatistigi"
sekmesinde kisiye ozel veriler bulunur: toplam izlenen bolum, izleme
durumuna gore dagilim ve duygulara gore dagilim. "Global Istatistik"
sekmesinde katalog geneli veriler bulunur: toplam anime, medya turune gore
ve yayin durumuna gore dagilim. Toplam izlenen bolum sayisi kisisel bir
veri oldugu icin yalniz kullanici sekmesinde gosterilir, global sekmede
yer almaz.

## Global duygu dagilimi

Duygulara gore dagilim artik her iki sekmede de var. Kullanici sekmesinde
yalniz senin isaretlerin sayilir; global sekmede tum kullanicilarin
isaretleri toplanir. Tek kullanicili (self-host) kurulumda iki gorunum
aynidir; cok kullanicili kurulumda global gorunum herkesin toplamidir.

## "Secim Yapilmamis" izleme durumu

Onceden, bir kullanici listeyi ilk actiginda hic dokunmadigi tum animeler
"Izlenme Planlandi" gorunuyordu - oysa kullanici henuz bir secim
yapmamisti. Artik dokunulmamis animeler "Secim Yapilmamis" gorunur;
kullanici izleme durumunu degistirdiginde (ornegin + ile bir bolum
isaretledinde) durum gercek degerine gecer. "Izlenme Planlandi" artik
yalniz kullanicinin bilincli olarak sectigi bir durumdur.

Bu durum listede filtreye de eklendi (izleme durumu filtresinde "Secim
Yapilmamis" secenegi) ve istatistik kullanici sekmesindeki izleme durumu
dagiliminda ayri bir satir olarak sayilir.

## Kurator notu (admin sayfasi)

Admin sayfasinin giris metnine, online veya offline eklenen bir animenin
gorselinin merkez sunucuya elle yuklenmesi gerektigini belirten bir
hatirlatma eklendi. Bu yalniz katalog sahibinin gordugu admin sayfasini
ilgilendirir.

## Self-host kullanicilar icin ne degisti

Iki sey: (1) istatistik sayfasi iki sekmeli oldu (kullanici / global); tek
kullanicili kurulumda global ve kullanici gorunumleri ayni veriyi gosterir.
(2) Listede hic dokunulmamis animeler artik "Secim Yapilmamis" gorunur,
"Izlenme Planlandi" degil; davranis (+ / - ile bolum isaretleme) aksi
belirtilmedikce eskisi gibidir.
