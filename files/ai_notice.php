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
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>AI Kullanım Beyanı - Anime Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        .notice-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .notice-container h1 {
            color: #333;
            font-size: 1.8em;
            margin-bottom: 8px;
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 10px;
        }
        .notice-container h2 {
            color: #4a90e2;
            font-size: 1.25em;
            margin-top: 28px;
            margin-bottom: 10px;
        }
        .notice-container .meta {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 0.95em;
        }
        .notice-container .meta p {
            margin: 5px 0;
        }
        .notice-container .meta strong {
            color: #555;
            display: inline-block;
            min-width: 130px;
        }
        .notice-container p {
            margin: 10px 0;
            color: #444;
        }
        .notice-container ul {
            padding-left: 24px;
            margin: 10px 0;
        }
        .notice-container li {
            margin: 6px 0;
        }
        .notice-container .info-box {
            background: #e8f4fd;
            border-left: 4px solid #4a90e2;
            padding: 12px 18px;
            margin: 15px 0;
            border-radius: 4px;
            font-size: 0.95em;
        }
        .notice-container .footer-note {
            margin-top: 35px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 0.85em;
            color: #888;
            text-align: center;
            font-style: italic;
        }
        .notice-container .footer-note a {
            color: #4a90e2;
            text-decoration: none;
        }
        .notice-container .footer-note a:hover {
            text-decoration: underline;
        }
        .notice-container .lang-switch {
            text-align: right;
            margin-bottom: 10px;
            font-size: 0.9em;
        }
        .notice-container .lang-switch a {
            color: #4a90e2;
            text-decoration: none;
        }
        .notice-container .lang-switch a:hover {
            text-decoration: underline;
        }
        .back-link {
            display: inline-block;
            margin: 20px 0 10px;
            color: #4a90e2;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="notice-container">
    <a href="about.php" class="back-link">&larr; Hakkında Sayfasına Dön</a>

    <div class="lang-switch">
        <a href="ai_notice_en.php"><i class="fas fa-globe"></i> English</a>
    </div>

    <h1><i class="fas fa-robot"></i> AI Kullanım Beyanı</h1>

    <p>
        Bu sayfa, Anime Tracker projesinin nasıl üretildiğine dair şeffaf
        bir beyandır. Hukuki bir lisans değildir — projenin GPL v2
        lisansını değiştirmez veya kısıtlamaz.
    </p>

    <div class="meta">
        <p><strong>Proje:</strong> Anime Tracker</p>
        <p><strong>Sahibi:</strong> Okan Sümer</p>
        <p><strong>Web:</strong> <a href="https://www.sicakcikolata.com">sicakcikolata.com</a></p>
        <p><strong>Lisans:</strong> GNU General Public License v2 (GPL v2)</p>
        <p><strong>Beyan tarihi:</strong> 2026-05-12</p>
    </div>

    <h2>1. Kullanılan AI Araçları</h2>
    <p>Bu projenin üretiminde aşağıdaki AI araçları kullanılmıştır:</p>
    <ul>
        <li><strong>Abacus AI</strong> — <a href="https://chatllm.abacus.ai">chatllm.abacus.ai</a></li>
        <li><strong>Claude</strong> — <a href="https://claude.ai">claude.ai</a></li>
        <li><strong>ChatGPT</strong> — <a href="https://chatgpt.com">chatgpt.com</a></li>
    </ul>
    <p>
        <strong>Kullanım alanı:</strong> Kod, Dokümantasyon, Veritabanı şeması, Logo tasarımı
    </p>

    <h2>2. İnsan Katkısı</h2>
    <p>
        AI araçları kullanılmış olsa da, aşağıdaki kararlar tamamen
        proje sahibi tarafından verilmiştir. Bu kararlar projenin özgün
        fikri mülkiyet boyutunu oluşturur:
    </p>
    <ul>
        <li>Proje amacı, kapsamı ve hedef kitlesi</li>
        <li>Mimari kararlar (WordPress tarzı iki yollu kurulum, katalog
            otoritesi modeli, öneri sistemi için kepçe metaforu)</li>
        <li>Veri modeli tasarımı (genres / tags / cümleler ayrımı,
            catalog-vs-local kaynak ayrımı, kişisel alan koruma kuralı)</li>
        <li>AI araçlarına verilen tüm yönlendirmeler (prompt'lar)</li>
        <li>Terminoloji kararları (örn: "etiket" → "cümle")</li>
        <li>Test, hata ayıklama ve entegrasyon kararları</li>
        <li>Sürüm yönetimi, versiyon numaralandırma ve güvenlik kararları</li>
        <li>Arayüz akışı ve kullanım kolaylığı tercihleri</li>
    </ul>

    <h2>3. Telif Hakkı</h2>
    <p>
        Anime Tracker projesinin seçim, düzenleme, yönlendirme ve
        yaratıcı karar boyutlarından doğan telif hakları Okan Sümer'e
        aittir. AI araçları proje sahibinin yönlendirmesi altında
        kullanılan araçlardır — tıpkı bir editör, derleyici veya
        geliştirme ortamı gibi.
    </p>

    <h2>4. Lisans ile İlişki</h2>
    <div class="info-box">
        Bu beyan <strong>bir lisans değildir</strong>. Projenin GPL v2
        lisansını değiştirmez, kısıtlamaz veya ek yükümlülük getirmez.
        Yalnızca şeffaflık amacıyla yayınlanmıştır.
        <br><br>
        Bu beyandaki herhangi bir madde GPL v2 ile çelişir gibi
        yorumlanırsa, GPL v2 öncelikli kabul edilir.
    </div>

    <h2>5. Downstream için Tavsiye</h2>
    <p>
        Bu projeden türetilen eserlerde aynı şeffaflık anlayışı ile AI
        kullanımının belirtilmesi <em>önerilir</em>. Bu bir tavsiyedir;
        GPL v2 kapsamında bağlayıcı değildir.
    </p>

    <div class="footer-note">
        Bu beyan
        <a href="https://github.com/hitsumo/ai-authorship-notice" target="_blank">ai-authorship-notice</a>
        şablonu temel alınarak hazırlanmıştır (CC0).
    </div>
</div>
</body>
</html>
