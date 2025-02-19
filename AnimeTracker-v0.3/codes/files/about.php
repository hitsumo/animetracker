<?php
/**
  [Anime Tracker/Anime izleme takip listesi.
    https://www.sicakcikolata.com]
  Copyright (C) 2025 [Okan Sümer]
 
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
    <title>Hakkında - Anime Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <style>
        .about-container {
            max-width: 800px;
            margin: 50px auto;
            text-align: center;
            padding: 20px;
        }

        .about-logo {
            width: 200px;
            height: 200px;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .about-title {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 30px;
            font-family: 'Poppins', sans-serif;
        }

        .about-description {
            font-size: 1.2em;
            line-height: 1.6;
            color: #666;
            margin-bottom: 30px;
        }

        .about-link {
            color: #4a90e2;
            text-decoration: none;
            font-size: 1.1em;
            transition: color 0.3s ease;
            display: inline-block;
            margin: 10px;
        }

        .about-link:hover {
            color: #357abd;
        }

        .button-container {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="about-container">
            <img src="img/anime-tracker-logo.jpg" alt="Anime Tracker Logo" class="about-logo">
            <h1 class="about-title">Anime Tracker</h1>
            <p class="about-description">
                Anime Tracker, <a href="https://chatllm.abacus.ai/LRRykgrhHp">Abacus AI</a> ile geliştirilmiş bir anime liste oluşturma ve yayın takip sistemidir.

                <a href="https://www.sicakcikolata.com" class="about-link">sicakcikolata.com</a>
                <h5><center class="about-description">Anime Tracker 2025 v-0.1</center></h5>
                
            </p>
            <div class="button-container">
                
                <a href="index.php" class="anime-list-button">Anime Listesine Dön</a>
            </div>
        </div>
    </div>
</body>
</html>