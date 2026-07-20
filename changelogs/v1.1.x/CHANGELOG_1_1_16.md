# Anime Tracker 1.1.16

**Yayin tarihi:** 2026-07-17

## Yeni: Uye olmayan ziyaretciler icin dil secimi

- **Giris yapmamis ziyaretciler artik arayuz dilini secebilir.** Cok
  kullanicili (online) modda, uyeligi olmayan bir ziyaretci onceden arayuzu
  yalnizca Turkce goruyordu ve dili degistirmenin hicbir yolu yoktu. Artik
  **Turkce / English** arasinda gecis yapabilirler.
- **Secim, giris / kayit / davet isteme sayfalarindaki kucuk bir dil
  secicisinden yapilir.** Sayfa kartinin ustunde, sag tarafta duran bir
  acilir kutudur; bir dil sectiginizde arayuz aninda o dile geciler.
- **Secim oturum boyunca korunur.** Bir kez sectiginizde, tarayici oturumunuz
  boyunca gezdiginiz tum sayfalarda gecerli olur; her sayfada yeniden
  secmeniz gerekmez.
- **Uye olan kullanicilarda ve self-host (tek kullanici) kurulumda hicbir sey
  degismez.** Onlar arayuz dilini yine "Liste Ayarlari" sayfasindaki "Arayuz
  Dili" bolumunden secer; bu kucuk secici onlara gorunmez.

## Yeni: Kayit ekrani duyurusu artik dile gore

- **Kayit ekrani duyurusu (register.php) artik Turkce ve Ingilizce ayri
  yazilabilir.** Onceden tek bir metin vardi ve hangi dil secili olursa olsun
  o metin gorunuyordu; simdi yonetici panelinde (Davetiyeler) iki ayri alan
  vardir: "Duyuru metni (Turkce)" ve "Duyuru metni (Ingilizce)".
- **Ingilizce alan bos birakilirsa, Ingilizce arayuzde de Turkce duyuru
  gosterilir.** Yani yalnizca tek dilde duyuru yazan bir operator icin
  davranis eskisi gibidir - duyuru her iki dilde de gorunur. Yalniz iki alani
  da dolduran operator, her arayuze o dile ozel metni verir.

## Degisiklik: Davet uretiminde e-posta artik zorunlu

- **Davet kodu uretirken e-posta adresi girmek artik zorunludur.** Onceden
  "(istege bagli)" idi ve bos birakilabiliyordu; boylece hangi davetin kime ait
  oldugu belirsiz kalabiliyordu. Artik her davet bir alici e-postasina baglanir.
- **Davet yine elle gonderilir - otomatik e-posta ATILMAZ.** E-posta yalnizca
  davetin kime ait oldugunu kaydeder; kodu ilgili kisiye siz iletirsiniz. Bos
  ya da gecersiz e-posta ile "Kod uret"e basildiginda kod uretilmez ve form
  bir uyari gosterir.

## Nasil calisir (teknik)

- Giris yapmis kullanicilarin dil tercihi `user_pref` tablosunda saklanir.
  Anonim ziyaretcinin bir kullanici kimligi (ve dolayisiyla `user_pref`
  satiri) olmadigi icin secim, PHP oturumunda (`$_SESSION`) tutulur.
  `lang_init()` guest icin secimi oturumdan okur; `set_language.php` ayni yere
  yazar. `set_language.php` endpoint'i, form yapisi ve CSRF korumasi
  degismemistir.
- Guest secicisi `guest_lang_switcher()` yardimci fonksiyonuyla uretilir ve
  yalnizca "cok kullanicili mod + giris yapilmamis" durumunda HTML dondurur;
  diger tum durumlarda bos dizi dondurur, boylece sayfalar aynen eskisi gibi
  gorunur. Ayarlar sayfasindaki secici ile ayni ceviri anahtarlarini kullanir
  (yeni metin eklenmedi).

## Sema / migration

- `migration/1.1.16/upgrade.sql` yalnizca surumu 1.1.16'ya tasir; **sema
  degisikligi yoktur** (calistirilacak SQL ifadesi yok). Cozum tamamen
  uygulama katmanindadir. Merkez katalog etkilenmez, sunucuda elle bir islem
  GEREKMEZ.

## Degisen / yeni dosyalar

- functions/i18n_helpers.php (lang_init: guest icin dili oturumdan oku; yeni
  `guest_lang_switcher()` yardimci fonksiyonu)
- set_language.php (guest secimini `user_pref` yerine oturuma yaz)
- login.php, register.php, request_invite.php (guest dil secicisini goster;
  register/request_invite: `.auth-alt` linkleri buton gorunumu)
- register.php (kayit ekrani duyurusu: secili dile gore Turkce/Ingilizce metin,
  Ingilizce bos ise Turkce'ye geri dusme)
- admin/admin_invites.php (duyuru icin ikinci, Ingilizce metin alani; kaydetme
  islemi iki alani birden yazar - davet uretiminde e-posta zorunlu: sunucu
  dogrulamasi + form `required` + hatali gonderimde uyari)
- lang/admin_tr.php, lang/admin_en.php (duyuru Ingilizce alan etiketleri:
  announce.label / label_en / placeholder_en / hint_en; davet e-posta metinleri:
  generate.desc / email_label / err_email)
- css/lang.css (guest dil secicisi stilleri; 1.1.4'te bosaltilan dosya yeniden
  tek kural icerir)
- migration/1.1.16/upgrade.sql (yeni)
- version.txt

Not: kayit duyurusunun Ingilizce metni `register_announcement_en` ayar
anahtarinda saklanir. `settings` tablosu bir anahtar-deger deposudur ve satir
ilk kaydetmede olusur; yeni tablo/kolon GEREKMEZ (sema degismez).
