# Anime Tracker 1.0.10

**Yayın tarihi:** (deploy günü doldurulacak)

## Yenilikler

### "İzleme Bırakıldı" durumu kullanıma açıldı
Veritabanında öteden beri var olan beşinci izleme durumu artık arayüzde:
- Ekleme ve düzenleme formlarında seçilebilir, ana listede filtrelenebilir.
- Türkçe etiketi "İzleme Bırakıldı", İngilizce etiketi "Dropped".
- Tüm sayfalarda kendi kırmızı rozet rengiyle gösterilir.
- Bırakılmış bir animede "+" tuşu "devam ediyorum" sinyalidir ve durumu
  otomatik "İzleniyor" yapar; "-" tuşu durumu bozmaz, yalnızca sayıyı azaltır.
- Bırakılan animede izlenen bölüm sayısı korunur ve formda görünür
  (kaçıncı bölümde bırakıldığı anlamlı bilgidir).

### "Seçim Yapılmamış" artık gerçek bir durum
- Ekleme ve düzenleme formlarında "Seçim Yapılmamış" seçilebilir; bir
  animeyi izleme durumu atamadan eklemek veya mevcut durumu geri
  "seçilmemiş"e almak mümkün.
- Düzenleme formu seçim yapılmamış animeyi artık "İzlenecek" olarak
  açmıyor; yalnızca başlık düzeltmek gibi alakasız bir kayıt işlemi
  durumu sessizce değiştirmiyor.
- Yalnızca not veya kişisel konu eklemek artık animeye otomatik
  "İzlenecek" durumu atamıyor.
- Seçim yapılmamış animede "+" tuşu durumu otomatik "İzleniyor" yapar.
- Detay, son eklenenler, seri zaman çizelgesi, kronoloji uyarıları,
  ilişkili animeler ve öneriler sayfalarında seçim yapılmamış durum
  kendi gri rozetiyle gösterilir.

### Duruma göre sıralama dile duyarlı alfabetik oldu
- Ana listede "Durum" sütunu artık ekranda görünen etikete göre
  alfabetik sıralanır ve arayüz dili değişince sıra da değişir.
  Türkçede: İzleme Bırakıldı → İzleme Ertelendi → İzlendi → İzleniyor →
  İzlenme Planlandı → Seçim Yapılmamış. İngilizcede: Dropped →
  Not Selected → On Hold → Plan to Watch → Watched → Watching.
- "Seçim Yapılmamış" ayrı bir blokta sabitlenmez; etiketiyle alfabetik
  yerini alır, ters sıralamada diğerleri gibi yer değiştirir.
- Doğru Türkçe alfabe sırası için PHP intl eklentisi kullanılır;
  eklenti yoksa yerleşik Türkçe karşılaştırma devreye girer.

## Düzeltmeler
- Online kurulumlarda, oturumu düşmüş bir kullanıcı admin sayfalarından
  birini açtığında giriş sayfası yerine 404 hatasına yönlendiriliyordu;
  yönlendirme adresleri kök-mutlak yapıldı.
- Ana listede durum filtresi: "İzlenecek" filtresi artık yalnızca
  gerçekten "İzlenecek" seçilmiş animeleri getirir; seçim yapılmamış
  animeler kendi filtresinde listelenir.
- Admin bekleyen animeler listesinden kişisel izleme durumu sütunu
  kaldırıldı; sayfanın işiyle ilgisi olmayan yanıltıcı bir bilgiydi.

## Veritabanı
- `user_anime.watch_status` sütunu NULL kabul eder (NULL = seçim
  yapılmamış); sütun varsayılanı NULL oldu. Mevcut kayıtlara
  dokunulmaz, veri dönüşümü yoktur. Migration otomatik uygulanır.

## Değişen dosyalar
- `index.php`
- `add_anime.php`
- `edit_anime.php`
- `update_watched.php`
- `anime_details.php`
- `recent.php`
- `recommendations.php`
- `statistics.php`
- `series_timeline.php`
- `schema.sql`
- `version.txt`
- `upgrade.sql`
- `js/anime_form.js`
- `functions/watch_status_helpers.php`
- `functions/user_anime_helpers.php`
- `functions/series_helpers.php`
- `functions/auth_helpers.php`
- `css/components.css`
- `css/series.css`
- `admin/admin_pending.php` *(online kurulumlar)*
- `lang/admin_tr.php` *(online kurulumlar)*
- `lang/admin_en.php` *(online kurulumlar)*

## Yeni dosyalar
- `migration/1.0.10/upgrade.sql`
