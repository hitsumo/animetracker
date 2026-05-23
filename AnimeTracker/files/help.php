<?php
/**
  [Anime Tracker/Anime izleme takip listesi.
    https://www.sicakcikolata.com]
  Copyright (C) 2025 [Okan Sumer]

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 2 as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
  MA 02110-1301, USA.
 */

/**
 * help.php - Kullanici yardim / nasil calisir sayfasi.
 *
 * Teknik olmayan dille sistemin nasil calistigini anlatir. Ozellikle
 * sync davranisi, kisisel alanlar (Notlar / Kisisel Konu), oneri
 * sistemi ve veri guvenligi konularinda kullaniciyi bilgilendirir.
 *
 * Statik icerik - ne DB baglantisi ne PHP mantigi gerektirir.
 * Menuden veya Hakkinda sayfasindan linklenebilir.
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yardım - Anime Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        .help-container {
            max-width: 850px;
            margin: 40px auto;
            padding: 30px 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            font-family: 'Poppins', sans-serif;
            color: #333;
            line-height: 1.7;
        }
        .help-container h1 {
            font-size: 2em;
            color: #2c3e50;
            margin-top: 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #4a90e2;
        }
        .help-container h2 {
            font-size: 1.4em;
            color: #2c3e50;
            margin-top: 35px;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #eee;
        }
        .help-container h3 {
            font-size: 1.1em;
            color: #4a90e2;
            margin-top: 22px;
            margin-bottom: 8px;
        }
        .help-container p {
            margin: 10px 0;
            color: #444;
        }
        .help-container ul {
            padding-left: 24px;
            margin: 10px 0;
        }
        .help-container li {
            margin: 6px 0;
        }
        .help-container code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 0.9em;
            color: #c7254e;
        }
        .help-container .box {
            background: #f8f9fa;
            border-left: 4px solid #4a90e2;
            padding: 12px 18px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .help-container .box.warning {
            background: #fff8e1;
            border-left-color: #ffc107;
        }
        .help-container .box.safe {
            background: #e8f5e9;
            border-left-color: #4caf50;
        }
        .help-container .box.danger {
            background: #ffebee;
            border-left-color: #e53935;
        }
        .help-container .box.info {
            background: #e3f2fd;
            border-left-color: #1976d2;
        }
        .help-container .icon-inline {
            color: #4a90e2;
            margin-right: 6px;
        }
        .help-container .toc {
            background: #f8f9fa;
            padding: 15px 25px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        .help-container .toc a {
            color: #4a90e2;
            text-decoration: none;
        }
        .help-container .toc a:hover {
            text-decoration: underline;
        }
        .help-container table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .help-container th, .help-container td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .help-container th {
            background: #f4f6f8;
            font-weight: 600;
        }
        .back-link {
            display: inline-block;
            margin: 20px 0;
            color: #4a90e2;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="help-container">
    <a href="index.php" class="back-link">&larr; Ana Sayfaya Dön</a>

    <h1><i class="fas fa-question-circle icon-inline"></i> Yardım</h1>

    <p>
        Anime Tracker'in nasil calistigi, hangi alanlarin neye yaradigi ve
        neye dikkat etmeniz gerektigi burada. Bir ozelligi merak
        ediyorsaniz ilgili bolumu okuyun.
    </p>

    <div class="toc">
        <strong>Icindekiler:</strong>
        <ul style="margin: 8px 0 0;">
            <li><a href="#alanlar">Anime Alanlari — Hangisi Ne Yapar?</a></li>
            <li><a href="#hizli-butonlar">Hizli Izleme Butonlari (+/-)</a></li>
            <li><a href="#sync">Katalog Sync — Nasil Calisir?</a></li>
            <li><a href="#kisisel-alanlar">Kisisel Alanlar — Notlar ve Kisisel Konu</a></li>
            <li><a href="#oneri">Ne İzlesem? — Öneri Sistemi</a></li>
            <li><a href="#kronoloji">Seriler ve Kronoloji</a></li>
            <li><a href="#silme-uyarilari">Silme Uyarilari</a></li>
            <li><a href="#guncelleme">Guncelleme Sistemi</a></li>
            <li><a href="#saat-dilimi">Saat Dilimi (TZ)</a></li>
        </ul>
    </div>

    <!-- =============================================================== -->
    <h2 id="alanlar">Anime Alanlari — Hangisi Ne Yapar?</h2>

    <p>
        Anime ekleme ve duzenleme ekranindaki alanlar iki gruba ayrilir:
        <strong>katalog alanlari</strong> (sunucudan gelir, sync ile
        guncellenir) ve <strong>kisisel alanlar</strong> (size ozel,
        hicbir zaman sunucuya gitmez).
    </p>

    <h3><i class="fas fa-cloud icon-inline"></i> Katalog Alanlari (sync edilir)</h3>
    <ul>
        <li><strong>Anime Ismi, Alternatif Isimler</strong></li>
        <li><strong>Konu</strong> — Animenin resmi ozeti</li>
        <li><strong>Turler</strong> — Aksiyon, Komedi, vb.</li>
        <li><strong>Cumleler (Etiketler)</strong> — "Ne İzlesem?" sistemi icin</li>
        <li><strong>Yayin durumu, bolum sayisi, yayin gun/saati</strong></li>
        <li><strong>MAL / AniDB / AnimeSchedule linkleri</strong></li>
        <li><strong>Seri bilgileri</strong> (seri adi, medya turu, sonraki seri)</li>
    </ul>
    <p>
        Bu alanlari elle degistirirseniz, bir sonraki sync'te
        <strong>uzerine yazilir</strong> (sunucunun dedigi gecer).
    </p>

    <h3><i class="fas fa-user icon-inline"></i> Kisisel Alanlar (sync edilmez)</h3>
    <ul>
        <li><strong>Izlenen Bolum sayisi</strong></li>
        <li><strong>Izleme Durumu</strong> (Izlendi / Izleniyor / Izlenme Planlandi) — listedeki <a href="#hizli-butonlar"><code>+/-</code> butonlariyla otomatik degisebilir</a></li>
        <li><strong>Notlar</strong> — Size ozel hatirlatmalar, yorumlar</li>
        <li><strong>Kisisel Konu</strong> — Kendi yorumunuz / aciklamaniz</li>
        <li><strong>Poster (kendi yuklediyseniz)</strong></li>
        <li><strong>Sonraki bolum tarihi</strong> (lokal hesap)</li>
    </ul>
    <p>
        Bu alanlara sunucu <strong>dokunmaz</strong>. Istediginiz kadar
        yazabilir, degistirebilirsiniz.
    </p>

    <!-- =============================================================== -->
    <h2 id="hizli-butonlar">Hizli Izleme Butonlari (+/-)</h2>

    <p>
        Listede her animenin yaninda <code>+</code> ve <code>-</code>
        butonlari var. Bu butonlarla "Duzenle" ekranina gitmeden Izlenen
        Bolum sayisini bir artirip azaltabilirsiniz. Sayim degisirken
        belirli kosullarda <strong>Izleme Durumu da otomatik olarak
        guncellenir</strong>.
    </p>

    <h3>Otomatik Durum Gecisleri</h3>

    <p>
        Asagidaki tablo dort temel durumu ozetler:
    </p>

    <table>
        <tr>
            <th>Su anki durum</th>
            <th>Aksiyon</th>
            <th>Yeni durum</th>
        </tr>
        <tr>
            <td>Izlenme Planlandi + 0/12</td>
            <td><code>+</code></td>
            <td>Izleniyor + 1/12</td>
        </tr>
        <tr>
            <td>Izleniyor + 11/12</td>
            <td><code>+</code></td>
            <td>Izlendi + 12/12</td>
        </tr>
        <tr>
            <td>Izlendi + 12/12</td>
            <td><code>-</code></td>
            <td>Izleniyor + 11/12</td>
        </tr>
        <tr>
            <td>Izleniyor + 1/12</td>
            <td><code>-</code></td>
            <td>Izlenme Planlandi + 0/12</td>
        </tr>
    </table>

    <p>
        Mantik basit: durum sinir gecislerinde (basa donus, sona ulasma)
        otomatik degisir, ara degerlerde dokunulmaz.
    </p>

    <h3>Tek Tikla Iki Adim</h3>

    <p>
        Bazen tek bir <code>+</code> veya <code>-</code> basisi iki gecisi
        birden tetikleyebilir:
    </p>

    <ul>
        <li><strong>Planlandi + 11/12 → <code>+</code> → Izlendi + 12/12.</strong>
        Once Planlandi'dan Izleniyor'a, sonra tavana ulastigi icin
        Izleniyor'dan Izlendi'ye tek tikla gecer.</li>
        <li><strong>Izlendi + 1/12 → <code>-</code> → Izlenme Planlandi + 0/12.</strong>
        Yukaridakinin aynadaki yansimasi: once Izleniyor'a, sonra 0'a indigi
        icin Planlandi'ya tek tikla doner.</li>
    </ul>

    <h3>Ne Zaman Tetiklenmez?</h3>

    <div class="box safe">
        <strong><i class="fas fa-info-circle"></i> Otomasyon dokunmaz:</strong>
        <ul style="margin: 8px 0 0;">
            <li><strong>Izleniyor + ara deger</strong> (ornek: 7/12) —
            <code>+</code> veya <code>-</code> basildiginda durum Izleniyor
            olarak kalir, sadece sayim degisir.</li>
            <li><strong>Izlenme Planlandi + <code>-</code></strong> —
            Planlandi durumunda <code>-</code> basmak ne sayimi ne durumu
            degistirir (zaten 0).</li>
            <li><strong>Izlendi + tavan altinda + <code>+</code></strong> —
            manuel olarak anormal duruma getirilmis bir kayda <code>+</code>
            basinca durum Izlendi olarak kalir; otomasyon zorla duzeltmez,
            manuel niyetiniz korunur.</li>
        </ul>
    </div>

    <h3>Bolum Sayisi Bilinmeyen Animeler</h3>

    <p>
        Toplam veya yayinlanan bolum sayisi bilinmiyorsa (tavansiz eski
        OVA'lar, programi belirsiz seriler gibi):
    </p>

    <ul>
        <li><strong>Tavana ulasma kontrolu yapilamaz</strong> — bu yuzden
        <code>+</code> ile otomatik "Izlendi" gecisi calismaz. Manuel
        olarak Duzenle'den isaretlemeniz gerekir.</li>
        <li><strong>0'a inis kontrolu tavandan bagimsiz calisir</strong> —
        Izleniyor + 1/? uzerinde <code>-</code> basildiginda durum yine
        Izlenme Planlandi + 0/?'e otomatik doner.</li>
        <li><strong>Manuel Izlendi yapilmis tavansiz animede <code>-</code></strong>
        basildiginda durum Izlendi olarak kalir — sistem guvenli bir gecis
        yapamadigi icin manuel duruma karismaz.</li>
    </ul>

    <h3>Manuel Duzenleme Her Zaman Serbest</h3>

    <p>
        Otomatik durum gecisleri sadece <code>+</code> ve <code>-</code>
        butonlarina basarken devreye girer. "Duzenle" formundan istediginiz
        durumu manuel olarak <strong>her zaman</strong> secebilirsiniz;
        otomasyon ona karismaz.
    </p>

    <!-- =============================================================== -->
    <h2 id="sync">Katalog Sync — Nasil Calisir?</h2>

    <p>
        Liste Ayarlari sayfasinda "Katalogdan Ice Aktar" dugmesine
        bastiginizda, sunucudaki katalog lokal veritabaninizla
        birlestirilir.
    </p>

    <div class="box safe">
        <strong><i class="fas fa-shield-alt"></i> Kaybolmaz:</strong>
        Izleme verileriniz, notlariniz, Kisisel Konu, kendi yukleginiz
        poster — bunlar size ozeldir ve sync'te asla dokunulmaz.
    </div>

    <div class="box warning">
        <strong><i class="fas fa-exclamation-triangle"></i> Uzerine Yazilir:</strong>
        Anime ismi, konu, turler, yayin bilgileri gibi katalog alanlari
        her sync'te sunucunun son haline gore guncellenir. Elle
        degistirdiyseniz kaybolur.
    </div>

    <h3>Kendi Ekledigim Animeler Ne Olur?</h3>
    <p>
        Siz bir anime ekledikten sonra admin tarafindan kataloga alinmamissa
        (yani sizin ozel kayitlariniz), bu animeler sync'te <strong>hic
        dokunulmaz</strong>. Tum alanlari korunur.
    </p>

    <h3>Sync Ne Zaman Calisir?</h3>
    <p>
        Otomatik degil — sadece siz istedikce. Liste Ayarlari → "Katalogdan
        Ice Aktar" dugmesine basinca bir defa calisir.
    </p>

    <!-- =============================================================== -->
    <h2 id="kisisel-alanlar">Kisisel Alanlar — Notlar ve Kisisel Konu</h2>

    <p>
        Iki farkli kisisel metin alaniniz var. Farklari:
    </p>

    <table>
        <tr>
            <th>Alan</th>
            <th>Amac</th>
            <th>Ornek</th>
        </tr>
        <tr>
            <td><strong>Notlar</strong></td>
            <td>Kisa hatirlatmalar</td>
            <td>"Arkadasla beraber izle", "ilk 3 bolumden sonra hizli izle"</td>
        </tr>
        <tr>
            <td><strong>Kisisel Konu</strong></td>
            <td>Uzun yorumlar, kendi ozetiniz</td>
            <td>Kendi cevirisiniz, kendi yorumunuz, kendi ozetiniz</td>
        </tr>
    </table>

    <h3><i class="fas fa-sync icon-inline"></i> Kisisel Konu Nasil Olusur?</h3>
    <p>
        <strong>Ilk durumda tek "Konu" alani vardir.</strong> Kendiniz
        yazarsaniz veya sunucudan gelen konu orada durur. Eger katalogdan
        gelen yeni bir sey varsa ve siz o alana kendi yazinizi yazmissaniz,
        ilk sync sirasinda:
    </p>
    <ul>
        <li>Sizin yazdiginiz metin otomatik olarak <strong>"Kisisel Konu"</strong> alanina tasinir</li>
        <li>Sunucudan gelen metin "Konu" alanina yazilir (duzenleyemezsiniz, salt okunur olur)</li>
        <li>Artik iki alan goreceksiniz, duzenlediginiz her sey "Kisisel Konu"ya gider</li>
    </ul>

    <div class="box warning">
        <strong><i class="fas fa-exclamation-triangle"></i> Dikkat:</strong>
        Kisisel Konu'yu silerseniz <strong>sync ile geri gelmez</strong>.
        Ayni sekilde Notlar alanini silerseniz o da geri gelmez. Bu iki
        alan size ozel ve kalici olarak sizin kontrolunuzde.
    </div>

    <!-- =============================================================== -->
    <h2 id="oneri">Ne İzlesem? — Öneri Sistemi</h2>

    <p>
        Menudeki "Ne İzlesem?" linki, listenizden size uygun anime
        onermesi icin tasarlanmis bir aractir.
    </p>

    <h3>Nasil Calisir?</h3>
    <p>
        Yonetici (admin) her animeye birkac <strong>cumle etiketi</strong>
        atar: "Okulda gecsin", "Spor olsun", "Buyu olsun" gibi. Siz bu
        cumlelerden istediginizi sec, "Oner" butonuna basin.
    </p>

    <h3>Kepce Mantigi</h3>
    <p>
        Her secilen cumle bir kepce gibi dusunun. Kepce kendi eslesmesini
        listeden ceker. Birden fazla kepce secerseniz, en cok kepceye
        uyan anime ust sirada gozukur.
    </p>

    <div class="box safe">
        <strong><i class="fas fa-check"></i> Onemli:</strong>
        Cok cumle secerseniz sonuclar azalmaz, aksine siralama
        netlesir. Sistem AND yerine OR + puan mantigi kullanir.
    </div>

    <h3>Surpriz Sec</h3>
    <p>
        Hic cumle secmeden "Surpriz Sec" derseniz, sistem size
        izlememis oldugunuz bir anime rastgele secer. Kararsiz
        kaldiginizda hizli bir cozum.
    </p>

    <h3>Arama Kutusu</h3>
    <p>
        Cumle listesi uzadiginda arama kutusuna yazabilirsiniz. Yazdiginiz
        harflerle <strong>baslayan</strong> cumleler liste halinde
        gorulur. Turkce karakterler ayirt edilir — "u" yazarsaniz "U" ile
        baslayanlar, "ü" yazarsaniz "Ü" ile baslayanlar gelir.
    </p>

    <!-- =============================================================== -->
    <h2 id="kronoloji">Seriler ve Kronoloji</h2>

    <p>
        Birbirine bagli animeler icin iki tur iliski sistemi var:
    </p>

    <h3>Seri Bilgisi</h3>
    <p>
        Bir anime'nin hangi seriye ait oldugu <strong>seri adi</strong> ve
        <strong>medya turu</strong> (TV / Film / OVA / Special / ONA) ile
        belirlenir. Ayni seri adini paylasan animeler anime detayinda
        "Bagli Animeler" bolumunde gozukur.
    </p>

    <h3>Sonraki Seri (next_in_series)</h3>
    <p>
        Bir animeyi bitirince hangi animeyi izlemeniz gerektigi. Detay
        sayfasinda "Sirada" kutusunda gozukur.
    </p>

    <h3>Kronoloji Isaretleri</h3>
    <p>
        Detective Conan gibi seriler icin: "54. bolumden sonra 1. filmi
        izle" gibi bolum seviyesinde isaretler tutulur. Detay sayfasinda
        aktif uyari olarak gorulur, ayri bir "Kronoloji" sayfasinda da
        timeline halinde listelenir.
    </p>

    <div class="box warning">
        <strong><i class="fas fa-exclamation-triangle"></i> Dikkat:</strong>
        Kronoloji isaretleri de sync'te katalog otoritedir. Kendiniz
        marker eklediyseniz sync sonrasi kaybolur.
    </div>

    <!-- =============================================================== -->
    <h2 id="silme-uyarilari">Silme Uyarilari</h2>

    <div class="box danger">
        <strong><i class="fas fa-trash-alt"></i> Geri Alinamaz Silmeler:</strong>
        <ul style="margin: 8px 0 0;">
            <li><strong>Notlar</strong> alanini bossaltmak → sync geri getirmez</li>
            <li><strong>Kisisel Konu</strong> alanini bossaltmak → sync geri getirmez</li>
            <li>Anime silmek → kalici, izleme verisi dahil her sey gider</li>
            <li>Poster dosyasi silmek → sync sirasinda katalog posteri tekrar indirilir
                (ancak kendi yuklediginiz poster geri gelmez)</li>
        </ul>
    </div>

    <div class="box safe">
        <strong><i class="fas fa-undo"></i> Geri Alinabilir (sync ile):</strong>
        <ul style="margin: 8px 0 0;">
            <li>Konu alanini degistirmek / bossaltmak → bir sonraki sync'te katalog konusu geri gelir</li>
            <li>Anime ismi degistirmek → sync'te duzelir</li>
            <li>Tur listesi / yayin bilgisi degistirmek → sync'te duzelir</li>
        </ul>
    </div>

    <!-- =============================================================== -->
    <h2 id="guncelleme">Guncelleme Sistemi</h2>

    <p>
        Anime Tracker'in kendisi zaman zaman yeni surumlerle gelir. Liste
        Ayarlari → "Guncelleme Kontrolu" dugmesi ile yeni sürüm olup
        olmadigini kontrol edebilirsiniz.
    </p>

    <p>
        Yeni surum varsa, tek tikla otomatik guncelleme yapilir:
    </p>
    <ul>
        <li>Sunucudan yeni surum indirilir</li>
        <li>Dosyalar yerinde guncellenir (<code>config.php</code>, <code>uploads/</code> ve izleme verileriniz korunur)</li>
        <li>Veritabani gerekirse otomatik guncellenir</li>
        <li>Sayfa yenilenir, yeni surum aktif</li>
    </ul>

    <div class="box safe">
        <strong><i class="fas fa-shield-alt"></i> Guncelleme Sirasinda Kaybolmaz:</strong>
        Animeleriniz, izleme verileriniz, notlariniz, poster'leriniz,
        DB kimlik bilgileriniz — hic biri etkilenmez.
    </div>

    <!-- =============================================================== -->
    <h2 id="saat-dilimi">Saat Dilimi — Yayin Saati Nasil Gosterilir?</h2>

    <p>
        Anime Tracker tum tarih ve saatleri veritabaninda <strong>UTC</strong>
        olarak saklar. Gosterirken her animenin kendi yayin saat dilimine
        cevirir.
    </p>

    <h3>Yayin Saat Dilimi (animenin TZ'i)</h3>
    <p>
        Anime ekleme/duzenleme formundaki "Yayin Saat Dilimi" alani.
        Listede 6 sabit secenek var: Asia/Tokyo (JST), Europe/Istanbul
        (TRT), UTC, America/New_York (ET), America/Los_Angeles (PT),
        Europe/London. Cogu Japon animesi icin <code>Asia/Tokyo</code>
        dogru secimdir.
    </p>

    <div class="box safe">
        <strong><i class="fas fa-magic"></i> Hizli Yol — Otomatik Doldur:</strong>
        AnimeSchedule URL'sini girip "Otomatik Doldur" (Senkronize Et)
        butonuna basarsaniz <strong>broadcast_day, broadcast_time ve
        broadcast_timezone alanlari otomatik olarak Asia/Tokyo + Tokyo
        saati ile dolar</strong>. AnimeSchedule API'si Japon animesi
        verilerini dogru sekilde Tokyo TZ'de donderir. Elle giris
        yapmaniza gerek kalmaz.
    </div>

    <h3>Iki Gecerli Workflow</h3>
    <p>
        TZ alani ve saat alani ayni saat dilimini yansitmali. Iki yol
        da gecerli:
    </p>
    <ul>
        <li><strong>Animenin yayin yeri:</strong> "JST" sec + Tokyo
        saatini gir (orn. 23:30). Anime detay sayfasi "23:30 (JST)"
        gosterir, kendi saatinizi manuel hesaplarsiniz. AnimeSchedule
        Otomatik Doldur bu yontemi kullanir.</li>
        <li><strong>Kendi yerel saatiniz:</strong> "TRT" sec + Turkiye
        saatini 24 saat formatinda gir (orn. 17:30). Anime detay sayfasi
        "17:30 (TRT)" gosterir, dogrudan okunabilir. AnimeSchedule
        sitesinden manuel okuyorsaniz site zaten Turkiye saatini
        gosteriyor; sadece am/pm'i 24 saate cevirip yazin.</li>
    </ul>
    <p>
        Onemli: TZ secimi ile saat alani <strong>tutarli</strong> olmali.
        "JST" secip Turkiye saatini girerseniz veya "TRT" secip Tokyo
        saatini girerseniz, "sonraki bolum ne zaman" hesabi yanlis olur.
    </p>

    <div class="box info">
        <strong><i class="fas fa-info-circle"></i> AnimeSchedule sitesi
        ne gosterir:</strong>
        AnimeSchedule sitesini tarayicidan acarsaniz saatleri
        <strong>am/pm (12 saat) formatinda</strong>, <strong>sizin
        lokal TZ'inizde</strong> gosterir (Turkiye'den ziyaret edenlere
        Turkiye saati, baska ulkeden ziyaret edenlere o ulkenin saati).
        Manuel doldurma yapiyorsaniz: formda lokal TZ'inizi secin
        (Turkiye icin TRT), am/pm'i 24 saat formatina cevirin (orn.
        "5:30 PM" -> 17:30), "Yayin Saati" alanina yazin. Site Tokyo
        TZ'ini gostermez — Tokyo TZ verisi sadece "Otomatik Doldur"
        butonu ile AnimeSchedule API'sinden dogrudan cekilir.
    </div>

    <div class="box info">
        <strong><i class="fas fa-info-circle"></i> Yaz/Kis Saati (DST):</strong>
        Animenin yayinlandigi TZ yaz/kis saati kullaniyorsa (Avrupa, ABD
        gibi), yayin saati yilda 2 kez 1 saat kayar (Mart sonu ve Ekim
        sonu). Asia/Tokyo DST kullanmadigi icin Japon anime saatleri yil
        boyu sabittir.
    </div>

    <h3>Eski v0.5 Kurulumlardan Yukseltme</h3>
    <p>
        v0.5.1'e gectikten sonra hicbir veriniz kaybolmaz. Yayin saatleri
        ayni gorunur (Asia/Tokyo varsayilan TZ'de eklenmis kayitlar hala
        Asia/Tokyo'da). Anime detay sayfasinda yayin saatinin yaninda TZ
        etiketi (JST, vs.) gozukur.
    </p>

    <p style="margin-top: 40px; color: #888; font-size: 0.9em;">
        Daha fazla sorunuz icin: daha fazla ayrintili teknik bilgi
        proje GitHub sayfasinda bulunur.
    </p>

    <a href="index.php" class="back-link">&larr; Ana Sayfaya Dön</a>
</div>
</body>
</html>
