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
 * sistemi, bolum sayisi senkronizasyonu (Madde C) ve veri guvenligi
 * konularinda kullaniciyi bilgilendirir.
 *
 * Statik icerik - ne DB baglantisi ne PHP mantigi gerektirir.
 * Menuden veya Hakkinda sayfasindan linklenebilir.
 *
 * NOT: PHP yorumlari ASCII (Turkce karaktersiz). Kullaniciya gosterilen
 * HTML icerigi Turkce karakter kullanir (UTF-8).
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
            border-left-color: #4a90e2;
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
        Anime Tracker'ın nasıl çalıştığı, hangi alanların neye yaradığı ve
        neye dikkat etmeniz gerektiği burada. Bir özelliği merak
        ediyorsanız ilgili bölümü okuyun.
    </p>

    <div class="toc">
        <strong>İçindekiler:</strong>
        <ul style="margin: 8px 0 0;">
            <li><a href="#alanlar">Anime Alanları — Hangisi Ne Yapar?</a></li>
            <li><a href="#sync">Katalog Sync — Nasıl Çalışır?</a></li>
            <li><a href="#bolum-sync">Bölüm Sayısı Senkronizasyonu</a></li>
            <li><a href="#kisisel-alanlar">Kişisel Alanlar — Notlar ve Kişisel Konu</a></li>
            <li><a href="#oneri">Ne İzlesem? — Öneri Sistemi</a></li>
            <li><a href="#kronoloji">Seriler ve Kronoloji</a></li>
            <li><a href="#silme-uyarilari">Silme Uyarıları</a></li>
            <li><a href="#guncelleme">Güncelleme Sistemi</a></li>
            <li><a href="#saat-dilimi">Saat Dilimi (TZ)</a></li>
        </ul>
    </div>

    <!-- =============================================================== -->
    <h2 id="alanlar">Anime Alanları — Hangisi Ne Yapar?</h2>

    <p>
        Anime ekleme ve düzenleme ekranındaki alanlar iki gruba ayrılır:
        <strong>katalog alanları</strong> (sunucudan gelir, sync ile
        güncellenir) ve <strong>kişisel alanlar</strong> (size özel,
        hiçbir zaman sunucuya gitmez).
    </p>

    <h3><i class="fas fa-cloud icon-inline"></i> Katalog Alanları (sync edilir)</h3>
    <ul>
        <li><strong>Anime İsmi, Alternatif İsimler</strong></li>
        <li><strong>Konu</strong> — Animenin resmi özeti</li>
        <li><strong>Türler</strong> — Aksiyon, Komedi, vb.</li>
        <li><strong>Cümleler (Etiketler)</strong> — "Ne İzlesem?" sistemi için</li>
        <li><strong>Yayın durumu, bölüm sayısı, yayın gün/saati</strong></li>
        <li><strong>MAL / AniDB / AnimeSchedule linkleri</strong> — animenin dış sitelerdeki sayfaları</li>
        <li><strong>Seri bilgileri</strong> (seri adı, medya türü, sonraki seri)</li>
    </ul>
    <p>
        Bu alanları elle değiştirirseniz, bir sonraki sync'te
        <strong>üzerine yazılır</strong> (sunucunun dediği geçer).
    </p>

    <div class="box info">
        <strong><i class="fas fa-info-circle"></i> AnimeSchedule Linki Neden Önemli?</strong>
        Yayını devam eden animeler için bu link, "yayınlanan bölüm sayısı"
        bilgisinin otomatik güncellenmesini sağlar. Detaylar için aşağıdaki
        <a href="#bolum-sync">Bölüm Sayısı Senkronizasyonu</a> bölümüne bakın.
    </div>

    <h3><i class="fas fa-user icon-inline"></i> Kişisel Alanlar (sync edilmez)</h3>
    <ul>
        <li><strong>İzlenen Bölüm sayısı</strong></li>
        <li><strong>İzleme Durumu</strong> (İzlendi / İzleniyor / İzlenmesi Planlandı)</li>
        <li><strong>Notlar</strong> — Size özel hatırlatmalar, yorumlar</li>
        <li><strong>Kişisel Konu</strong> — Kendi yorumunuz / açıklamanız</li>
        <li><strong>Poster (kendi yüklediyseniz)</strong></li>
        <li><strong>Sonraki bölüm tarihi</strong> (lokal hesap)</li>
    </ul>
    <p>
        Bu alanlara sunucu <strong>dokunmaz</strong>. İstediğiniz kadar
        yazabilir, değiştirebilirsiniz.
    </p>

    <!-- =============================================================== -->
    <h2 id="sync">Katalog Sync — Nasıl Çalışır?</h2>

    <p>
        Liste Ayarları sayfasında "Katalogdan İçe Aktar" düğmesine
        bastığınızda, sunucudaki katalog lokal veritabanınızla
        birleştirilir.
    </p>

    <div class="box safe">
        <strong><i class="fas fa-shield-alt"></i> Kaybolmaz:</strong>
        İzleme verileriniz, notlarınız, Kişisel Konu, kendi yüklediğiniz
        poster — bunlar size özeldir ve sync'te asla dokunulmaz.
    </div>

    <div class="box warning">
        <strong><i class="fas fa-exclamation-triangle"></i> Üzerine Yazılır:</strong>
        Anime ismi, konu, türler, yayın bilgileri gibi katalog alanları
        her sync'te sunucunun son haline göre güncellenir. Elle
        değiştirdiyseniz kaybolur.
    </div>

    <h3>Kendi Eklediğim Animeler Ne Olur?</h3>
    <p>
        Siz bir anime ekledikten sonra admin tarafından kataloğa alınmamışsa
        (yani sizin özel kayıtlarınız), bu animeler sync'te <strong>hiç
        dokunulmaz</strong>. Tüm alanları korunur.
    </p>

    <h3>Sync Ne Zaman Çalışır?</h3>
    <p>
        Katalog sync'i <strong>otomatik değil</strong> — sadece siz
        istedikçe çalışır. Liste Ayarları → "Katalogdan İçe Aktar"
        düğmesine basınca bir defa çalışır. (Bölüm sayısı
        senkronizasyonu farklı bir mekanizmadır ve otomatik çalışır;
        bkz. aşağıdaki bölüm.)
    </p>

    <!-- =============================================================== -->
    <h2 id="bolum-sync">Bölüm Sayısı Senkronizasyonu</h2>

    <p>
        Yayını devam eden animeler için "Yayınlanan Bölüm Sayısı"
        alanının değeri, AnimeSchedule sitesinden otomatik olarak
        güncel tutulur. Yani her hafta yeni bölüm çıktığında bu sayıyı
        elle güncellemeniz gerekmez.
    </p>

    <h3>İki Yolla Çalışır</h3>

    <p>
        <strong>1. Otomatik (sessiz):</strong> Liste Ayarları sayfasını
        her açtığınızda günde bir kez arka planda çalışır. Tüm "Yayın
        Devam Ediyor" durumundaki animelerin bölüm sayısı tek seferde
        güncellenir. Sayfa biraz yavaş yüklenebilir (5-15 saniye), bu
        normaldir.
    </p>

    <p>
        <strong>2. Manuel:</strong> Anime düzenleme sayfasında
        ("Yayınlanan Bölüm Sayısı" alanının altında) yeşil
        <strong>"Senkronize Et"</strong> butonu vardır. Tek bir anime
        için anlık güncelleme isterseniz buradan yapabilirsiniz.
        Liste Ayarları'ndaki "Şimdi Senkronize Et" düğmesi de tüm
        animeler için manuel toplu güncelleme yapar.
    </p>

    <h3>Gereksinimler</h3>
    <ul>
        <li>Animenin durumu <strong>"Yayın Devam Ediyor"</strong> olmalı (yayını biten animelerde anlamsız)</li>
        <li><strong>MyAnimeList linki</strong> dolu olmalı</li>
        <li><strong>AnimeSchedule linki</strong> dolu olmalı — eşleştirme bu URL üzerinden yapılır</li>
    </ul>

    <div class="box info">
        <strong><i class="fas fa-info-circle"></i> Mola Haftaları:</strong>
        Anime bir hafta yayın yapmadıysa (recap, ara, vb.) sistem
        otomatik olarak son 3 haftaya bakar ve en son yayınlanan
        bölüm sayısını bulur. Yani uzun aralar bile genelde sorunsuz
        atlanır.
    </div>

    <div class="box warning">
        <strong><i class="fas fa-exclamation-triangle"></i> Link Eksikse:</strong>
        AnimeSchedule linki olmayan animeler senkronize edilemez.
        "Senkronize Et" butonu hata verir, otomatik run da o animeyi
        atlar. Bu yüzden ongoing animeleri eklerken bu alanı doldurmanız
        önerilir.
    </div>

    <h3>Veriniz Korunur</h3>
    <p>
        Bu özellik <strong>sadece "Yayınlanan Bölüm Sayısı"</strong>
        alanını günceller. <strong>İzlenen Bölüm Sayısı</strong> dahil
        diğer kişisel alanlarınıza dokunmaz.
    </p>

    <!-- =============================================================== -->
    <h2 id="kisisel-alanlar">Kişisel Alanlar — Notlar ve Kişisel Konu</h2>

    <p>
        İki farklı kişisel metin alanınız var. Farkları:
    </p>

    <table>
        <tr>
            <th>Alan</th>
            <th>Amaç</th>
            <th>Örnek</th>
        </tr>
        <tr>
            <td><strong>Notlar</strong></td>
            <td>Kısa hatırlatmalar</td>
            <td>"Arkadaşla beraber izle", "ilk 3 bölümden sonra hızlı izle"</td>
        </tr>
        <tr>
            <td><strong>Kişisel Konu</strong></td>
            <td>Uzun yorumlar, kendi özetiniz</td>
            <td>Kendi çevirinizi, kendi yorumunuzu, kendi özetinizi</td>
        </tr>
    </table>

    <h3><i class="fas fa-sync icon-inline"></i> Kişisel Konu Nasıl Oluşur?</h3>
    <p>
        <strong>İlk durumda tek "Konu" alanı vardır.</strong> Kendiniz
        yazarsanız veya sunucudan gelen konu orada durur. Eğer katalogdan
        gelen yeni bir şey varsa ve siz o alana kendi yazınızı yazmışsanız,
        ilk sync sırasında:
    </p>
    <ul>
        <li>Sizin yazdığınız metin otomatik olarak <strong>"Kişisel Konu"</strong> alanına taşınır</li>
        <li>Sunucudan gelen metin "Konu" alanına yazılır (düzenleyemezsiniz, salt okunur olur)</li>
        <li>Artık iki alan göreceksiniz, düzenlediğiniz her şey "Kişisel Konu"ya gider</li>
    </ul>

    <div class="box warning">
        <strong><i class="fas fa-exclamation-triangle"></i> Dikkat:</strong>
        Kişisel Konu'yu silerseniz <strong>sync ile geri gelmez</strong>.
        Aynı şekilde Notlar alanını silerseniz o da geri gelmez. Bu iki
        alan size özel ve kalıcı olarak sizin kontrolünüzde.
    </div>

    <!-- =============================================================== -->
    <h2 id="oneri">Ne İzlesem? — Öneri Sistemi</h2>

    <p>
        Menüdeki "Ne İzlesem?" linki, listenizden size uygun anime
        önermesi için tasarlanmış bir araçtır.
    </p>

    <h3>Nasıl Çalışır?</h3>
    <p>
        Yönetici (admin) her animeye birkaç <strong>cümle etiketi</strong>
        atar: "Okulda geçsin", "Spor olsun", "Büyü olsun" gibi. Siz bu
        cümlelerden istediğinizi seçip "Öner" butonuna basın.
    </p>

    <h3>Kepçe Mantığı</h3>
    <p>
        Her seçilen cümleyi bir kepçe gibi düşünün. Kepçe kendi
        eşleşmesini listeden çeker. Birden fazla kepçe seçerseniz, en
        çok kepçeye uyan anime üst sırada görünür.
    </p>

    <div class="box safe">
        <strong><i class="fas fa-check"></i> Önemli:</strong>
        Çok cümle seçerseniz sonuçlar azalmaz, aksine sıralama
        netleşir. Sistem AND yerine OR + puan mantığı kullanır.
    </div>

    <h3>Sürpriz Seç</h3>
    <p>
        Hiç cümle seçmeden "Sürpriz Seç" derseniz, sistem size
        izlemediğiniz bir anime rastgele seçer. Kararsız
        kaldığınızda hızlı bir çözüm.
    </p>

    <h3>Arama Kutusu</h3>
    <p>
        Cümle listesi uzadığında arama kutusuna yazabilirsiniz. Yazdığınız
        harflerle <strong>başlayan</strong> cümleler liste halinde
        görünür. Türkçe karakterler ayırt edilir — "u" yazarsanız "U" ile
        başlayanlar, "ü" yazarsanız "Ü" ile başlayanlar gelir.
    </p>

    <!-- =============================================================== -->
    <h2 id="kronoloji">Seriler ve Kronoloji</h2>

    <p>
        Birbirine bağlı animeler için iki tür ilişki sistemi var:
    </p>

    <h3>Seri Bilgisi</h3>
    <p>
        Bir animenin hangi seriye ait olduğu <strong>seri adı</strong> ve
        <strong>medya türü</strong> (TV / Film / OVA / Special / ONA) ile
        belirlenir. Aynı seri adını paylaşan animeler anime detayında
        "Bağlı Animeler" bölümünde görünür.
    </p>

    <h3>Sonraki Seri (next_in_series)</h3>
    <p>
        Bir animeyi bitirince hangi animeyi izlemeniz gerektiği. Detay
        sayfasında "Sırada" kutusunda görünür.
    </p>

    <h3>Kronoloji İşaretleri</h3>
    <p>
        Detective Conan gibi seriler için: "54. bölümden sonra 1. filmi
        izle" gibi bölüm seviyesinde işaretler tutulur. Detay sayfasında
        aktif uyarı olarak görünür, ayrı bir "Kronoloji" sayfasında da
        timeline halinde listelenir.
    </p>

    <div class="box warning">
        <strong><i class="fas fa-exclamation-triangle"></i> Dikkat:</strong>
        Kronoloji işaretleri de sync'te katalog otoritedir. Kendiniz
        marker eklediyseniz sync sonrası kaybolur.
    </div>

    <!-- =============================================================== -->
    <h2 id="silme-uyarilari">Silme Uyarıları</h2>

    <div class="box danger">
        <strong><i class="fas fa-trash-alt"></i> Geri Alınamaz Silmeler:</strong>
        <ul style="margin: 8px 0 0;">
            <li><strong>Notlar</strong> alanını boşaltmak → sync geri getirmez</li>
            <li><strong>Kişisel Konu</strong> alanını boşaltmak → sync geri getirmez</li>
            <li>Anime silmek → kalıcı, izleme verisi dahil her şey gider</li>
            <li>Poster dosyası silmek → sync sırasında katalog posteri tekrar indirilir
                (ancak size özel yüklediğiniz poster geri gelmez)</li>
        </ul>
    </div>

    <div class="box safe">
        <strong><i class="fas fa-undo"></i> Geri Alınabilir (sync ile):</strong>
        <ul style="margin: 8px 0 0;">
            <li>Konu alanını değiştirmek / boşaltmak → bir sonraki sync'te katalog konusu geri gelir</li>
            <li>Anime ismini değiştirmek → sync'te düzelir</li>
            <li>Tür listesi / yayın bilgisi değiştirmek → sync'te düzelir</li>
        </ul>
    </div>

    <!-- =============================================================== -->
    <h2 id="guncelleme">Güncelleme Sistemi</h2>

    <p>
        Anime Tracker'ın kendisi zaman zaman yeni sürümlerle gelir. Liste
        Ayarları → "Güncelleme Kontrolü" düğmesi ile yeni sürüm olup
        olmadığını kontrol edebilirsiniz.
    </p>

    <p>
        Yeni sürüm varsa, tek tıkla otomatik güncelleme yapılır:
    </p>
    <ul>
        <li>Sunucudan yeni sürüm indirilir</li>
        <li>Dosyalar yerinde güncellenir (<code>config.php</code>, <code>uploads/</code> ve izleme verileriniz korunur)</li>
        <li>Veritabanı gerekirse otomatik güncellenir</li>
        <li>Sayfa yenilenir, yeni sürüm aktif</li>
    </ul>

    <div class="box safe">
        <strong><i class="fas fa-shield-alt"></i> Güncelleme Sırasında Kaybolmaz:</strong>
        Animeleriniz, izleme verileriniz, notlarınız, posterleriniz,
        DB kimlik bilgileriniz — hiçbiri etkilenmez.
    </div>

    <!-- =============================================================== -->
    <h2 id="saat-dilimi">Saat Dilimi — Yayın Saati Nasıl Gösterilir?</h2>

    <p>
        Anime Tracker tüm tarih ve saatleri veritabanında <strong>UTC</strong>
        olarak saklar. Size gösterirken iki ayrı saat dilimi devreye girer:
    </p>

    <h3>1. Yayın Saat Dilimi (animenin TZ'i)</h3>
    <p>
        Anime ekleme/düzenleme formundaki "Yayın Saat Dilimi" alanı.
        Animenin gerçekten yayınlandığı yer (çoğu Japon animesi için
        <code>Asia/Tokyo</code>). Burayı <strong>animenin yayın yeri</strong>
        olarak doldurun, kendi saat diliminiz olarak değil. Liste tüm IANA
        saat dilimlerini içerir; istediğinizi seçebilirsiniz.
    </p>

    <h3>2. Görüntü Saat Dilimi (sizin TZ'iniz)</h3>
    <p>
        Liste Ayarları → "Görüntü Saat Dilimi" alanı. Yayın saatleri ve
        "sonraki bölüme kalan süre" hesabı bu saat diliminize göre gösterilir.
        Yeni kurulumda varsayılan <code>Europe/Istanbul</code>'dur. Başka bir
        ülkeden kullanıyorsanız buradan kendi TZ'inizi seçin.
    </p>

    <p>
        Örnek: Bir anime <code>Asia/Tokyo</code> saatinde Perşembe 23:30'da
        yayınlanıyorsa ve görüntü TZ'iniz <code>Europe/Berlin</code> ise,
        anime detay sayfası:
    </p>
    <ul>
        <li><strong>Yayın Saati:</strong> 23:30 (JST)</li>
        <li><strong>Sizin saatinizle:</strong> 16:30 (CET) — yaz/kış saatine göre değişir</li>
    </ul>

    <div class="box info">
        <strong><i class="fas fa-info-circle"></i> Yaz/Kış Saati (DST):</strong>
        Avrupa ve ABD gibi yaz/kış saati uygulayan TZ'lerde, animenin
        yerel saatinizdeki gösterimi yılda iki kez 1 saat kayar
        (Mart sonu ve Ekim sonu). Bu beklenen davranıştır — animenin
        gerçek yayın saati Tokyo'da hep aynı, sadece sizin yerel
        karşılığınız mevsime göre değişir. Türkiye 2016'dan beri DST
        kullanmadığı için Istanbul TZ'inde bu kayma yoktur.
    </div>

    <p style="margin-top: 40px; color: #888; font-size: 0.9em;">
        Daha fazla soru veya ayrıntılı teknik bilgi için:
        proje GitHub sayfasında bulunur.
    </p>

    <a href="index.php" class="back-link">&larr; Ana Sayfaya Dön</a>
</div>
</body>
</html>
