# Anime Tracker 0.6.1

**Sürüm tarihi:** 24 Mayıs 2026
**Tür:** Özellik (Duygu Etiketleri v1 - kişisel tepki takibi)

Bu sürüm otomatik güncelleme ile gelir; ekstra bir şey yapmanıza
gerek yoktur. Güncelleme sırasında yeni bir tablo eklenir, mevcut
verilerinize dokunulmaz.

## Yeni

- **Duygu Etiketleri.** Bir animeye tek bir puan (1-10, yıldız vs.)
  vermek istemiyorsanız ama "nasıl hissettim" bilgisini de
  kaybetmek istemiyorsanız - işte tam bunun için. Detay sayfasında
  9 etiket arasından seçim yapabilirsiniz: Hüzünlendirdi,
  Heyecanlandırdı, Sıktı, Güldürdü, Korkuttu, Düşündürdü, Şaşırttı,
  Dinlendirdi, Motive Etti. Bir animede aynı anda en fazla 3
  etiket işaretlenebilir - çünkü birden fazla duyguyu tetiklemiş
  olabilir, ama 9'unu birden işaretlemek bilgiyi sulandırır.

- **Felsefe: puan değil, işaret.** Bir film/anime hakkında
  "8/10" demek, deneyiminizi tek bir sayıya sıkıştırıyor ve
  birçok şeyi kaybediyor. Bunun yerine: "Bu anime beni
  hüzünlendirdi VE düşündürdü" demek, "8/10" demekten daha çok
  şey anlatır. Duygu Etiketleri, izlenen bir animenin sizde
  uyandırdığı tepkiyi - puana indirgemeden - kaydetmenize izin
  verir.

- **Tıkla = aç/kapa.** Bir etikete tıklayınca işaretlenir, tekrar
  tıklayınca kaldırılır. 3 etiket sınırına ulaştığınızda diğer
  pasif etiketler silikleşir, ama aktif etiketler her zaman
  kaldırılabilir (yeni bir etikete yer açmak için).

## Değişiklikler

- **"İzleme Durumu" rozeti artık içerik kadar geniş.** Önceden
  bazı detay sayfalarında rozet tüm satırı kaplıyordu (mavi/yeşil
  bir uzun şerit gibi). Şimdi sadece etiket yazısı kadar yer
  kaplıyor, etrafında nefes alacak boşluk var.

- **"Anime Tracker" başlığı Ara butonu ile aynı boyutta.**
  Önceden ana sayfa başlığı orantısız büyük durabiliyordu.
  Şimdi daha dengeli görünür.

## Önemli Düzeltmeler

Bu sürüm 0.6'dan kalan ve sessizce çalışmaya devam eden üç
önemli hatayı düzeltir. Hatalar 0.6.1 test sürecinde keşfedildi
(farklı veritabanı yapılandırmasıyla yapılan test sayesinde).

- **Kronoloji sayfası artık doğru durum gösteriyor.** 0.6'dan
  beri kronoloji sayfasındaki seri akışında her anime "sırada"
  olarak görünüyordu, izleme durumu ne olursa olsun. Bunun
  sebebi sürüm geçişinde gözden kaçan bir kod parçasıydı.
  Şimdi animeler doğru izleme durumunda (İzlendi / İzleniyor /
  Planlandı / Ertelendi) görüntüleniyor.

- **Katalogdan içeri aktarma artık daha güvenli.** Sunucudan
  anime kataloğunu çekerken nadir bir yapılandırmada hata
  oluşabiliyordu. Bu artık olmuyor.

- **Yeni eklenen animelerin izleme durumu artık doğru atanıyor.**
  Önceden bazı veritabanı yapılandırmalarında yeni anime
  eklendiğinde izleme durumu boş kalabiliyordu (statistiklerde
  toplam sayı uyuşmazlığı yaratıyordu). Şimdi her yeni anime
  varsayılan olarak "İzlenme Planlandı" durumuyla başlıyor.

## Bilinen Davranışlar

- **Aynı duygu etiketini iki kez işaretleyemezsiniz.** Bu
  kasıtlı: bir anime sizi "Sıktı" ise, bunu iki kere işaretlemek
  zaten bilgi katmıyor. İşaretleme bir oy değil, tepki kaydı.

- **3 etiketten fazla yapılamaz.** Eğer dördüncü etiket
  eklemek istiyorsanız, önce mevcut üçten birini kaldırmanız
  gerekir. Sınırın amacı: anlamlı seçim yapmaya zorlamak. Eğer
  bir anime 4-5 farklı duyguya yol açtıysa, hangileri *en güçlü*
  olduğuna karar vererek o üçü seçmek, hepsini birden
  işaretlemekten daha bilgi taşır.

- **Aktif bir etiketi kaldırmak her zaman serbest.** 3 sınırına
  ulaşsanız bile, mevcut bir etiketi tek tıkla kaldırabilirsiniz.
  Yer açılır, başka etiket eklenebilir.

- **Duygu etiketleri kişiseldir.** Bu sürümde sadece kendi
  işaretlemenizi görürsünüz. İleride çoklu kullanıcı modu
  açıldığında, herkesin işaretlerinin bir araya gelmesiyle
  bir dağılım grafiği gösterilebilir - ama bu ileri bir sürüm.

## Teknik Notlar

- Yeni bir tablo eklendi (`user_anime_emotion`). Mevcut
  tablolarınıza dokunulmadı; bu sürüm risksiz, geri alınabilir.

- Etiket listesi `functions.php` içinde tek bir yerden okunur.
  İleride etiket eklemek/çıkarmak için tek dosya değişir,
  başka kod dokunulmaz.
