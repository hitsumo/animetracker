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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Authorship Notice - Anime Tracker</title>
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
    <a href="about.php" class="back-link">&larr; Back to About</a>

    <div class="lang-switch">
        <a href="ai_notice.php"><i class="fas fa-globe"></i> Türkçe</a>
    </div>

    <h1><i class="fas fa-robot"></i> AI Authorship Notice</h1>

    <p>
        This page is a transparent statement about how the Anime Tracker
        project was produced. It is not a legal license — it does not
        modify or restrict the project's GPL v2 license.
    </p>

    <div class="meta">
        <p><strong>Project:</strong> Anime Tracker</p>
        <p><strong>Owner:</strong> Okan Sümer</p>
        <p><strong>Web:</strong> <a href="https://www.sicakcikolata.com">sicakcikolata.com</a></p>
        <p><strong>License:</strong> GNU General Public License v2 (GPL v2)</p>
        <p><strong>Notice date:</strong> 2026-05-12</p>
    </div>

    <h2>1. AI Tools Used</h2>
    <p>The following AI tools were used in producing this project:</p>
    <ul>
        <li><strong>Abacus AI</strong> — <a href="https://chatllm.abacus.ai">chatllm.abacus.ai</a></li>
        <li><strong>Claude</strong> — <a href="https://claude.ai">claude.ai</a></li>
        <li><strong>ChatGPT</strong> — <a href="https://chatgpt.com">chatgpt.com</a></li>
    </ul>
    <p>
        <strong>Used for:</strong> Code, Documentation, Database schema, Logo design
    </p>

    <h2>2. Human Contribution</h2>
    <p>
        While AI tools were used, the following decisions were made
        entirely by the owner. These decisions constitute the
        project's original intellectual property:
    </p>
    <ul>
        <li>Project purpose, scope, and target audience</li>
        <li>Architecture decisions (WordPress-style two-path installation,
            catalog-as-authority model, "bucket" metaphor for recommendations)</li>
        <li>Data model design (genres vs tags vs sentences separation,
            catalog-vs-local source distinction, personal field protection)</li>
        <li>All prompts and direction given to the AI tools</li>
        <li>Terminology choices (e.g. "tag" → "sentence")</li>
        <li>Testing, debugging, and integration decisions</li>
        <li>Release management, versioning, and security decisions</li>
        <li>UI/UX flow and accessibility choices</li>
    </ul>

    <h2>3. Copyright</h2>
    <p>
        Copyright over the Anime Tracker project — arising from the
        selection, arrangement, direction, and creative decision
        boundaries — belongs to Okan Sümer. AI tools are instruments
        used under the owner's direction, much like an editor,
        compiler, or development environment.
    </p>

    <h2>4. Relation to License</h2>
    <div class="info-box">
        This notice is <strong>not a license</strong>. It does not
        modify, restrict, or add obligations to the project's GPL v2
        license. It is published purely for transparency.
        <br><br>
        In case of any conflict, the GPL v2 license takes precedence.
    </div>

    <h2>5. Recommendation for Downstream</h2>
    <p>
        Derivative works based on this project are <em>encouraged</em>
        to disclose AI use in the same spirit of transparency. This is
        a recommendation only; it is not binding under GPL v2.
    </p>

    <div class="footer-note">
        This notice is based on the
        <a href="https://github.com/hitsumo/ai-authorship-notice" target="_blank">ai-authorship-notice</a>
        template (CC0).
    </div>
</div>
</body>
</html>
