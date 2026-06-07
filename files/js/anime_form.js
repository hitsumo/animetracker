// =====================================================================
// Anime Tracker - paylasilan form JS (add_anime.php + edit_anime.php).
//
// Bu dosya iki sayfanin ORTAK form etkilesim fonksiyonlarini icerir
// (resim adi, alternatif basliklar, yayin/izleme gorunurluk toggle'lari,
// tur secimi, etiket onerileri, AnimeSchedule otomatik doldurma).
//
// YUKLEME SOZLESMESI: sayfa bu dosyayi <script src> ile yuklemeden ONCE
// asagidaki global'leri inline <script> icinde tanimlamalidir:
//
//   const LANG = { ... };            // ceviri sabitleri (PHP json_encode)
//   const ANIME_FORM = {
//       allTags: [...],              // tum etiket kutuphanesi (isim listesi)
//       genres:  [...],              // baslangic secili turler ([] = bos)
//       tags:    [...]               // baslangic secili etiketler ([] = bos)
//   };
//
// Dosya, form HTML'inden SONRA yuklenmelidir (DOM elemanlarini yukleme
// aninda referans alir). edit_anime'a OZGU olanlar (syncAiredEpisodes ve
// duruma bagli baslangic gizlemesi) o sayfanin kendi inline script'inde
// kalir; bu dosya yalniz ortak kismi tasir.
//
// Fonksiyonlar inline onchange/onclick handler'larindan cagrildigi icin
// global kapsamda tanimlidir (IIFE icine alinmaz).
// =====================================================================

// --- Durum (sayfadan gelen baslangic degerleriyle) ---
const allTags = (typeof ANIME_FORM !== 'undefined' && ANIME_FORM.allTags) ? ANIME_FORM.allTags.slice() : [];
let selectedGenres = (typeof ANIME_FORM !== 'undefined' && ANIME_FORM.genres) ? ANIME_FORM.genres.slice() : [];
let selectedTags = (typeof ANIME_FORM !== 'undefined' && ANIME_FORM.tags) ? ANIME_FORM.tags.slice() : [];

const tagInput = document.getElementById('tag-input');
const tagSuggestions = document.getElementById('tag-suggestions');

// --- Resim dosya adi ---
function updateFileName(input) {
    const fileName = input.files[0]?.name;
    document.getElementById('file-name').textContent = fileName || LANG.no_file;
}

// --- Alternatif basliklar ---
function addAlternativeTitle() {
    const container = document.getElementById('alternative-titles');
    const newField = document.createElement('div');
    newField.className = 'field-group';
    newField.innerHTML = `
        <input type="text" name="alternative_titles[]" placeholder="${LANG.alternative_title_placeholder}">
        <button type="button" class="remove-button" onclick="removeField(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(newField);
}

function removeField(button) {
    button.parentElement.remove();
}

// --- Yayin/izleme gorunurluk toggle'lari ---
function toggleBroadcastDetails() {
    const statusEl = document.querySelector('select[name="status"]');
    if (!statusEl) return; // readonly mod (yayin tamamlandi kilidi): select yok
    const status = statusEl.value;
    const broadcastDetails = document.getElementById('broadcast-details');
    const airedSection = document.getElementById('aired-episodes-section');
    const endDateSection = document.getElementById('end-date-section');

    // Yayin detaylari (interval, gun, saat) yalniz devam eden anime icin anlamli.
    if (status === 'Yayın Devam Ediyor') {
        broadcastDetails.style.display = 'block';
        airedSection.style.display = 'block';
        endDateSection.style.display = 'none';
    } else if (status === 'Yayın Tamamlandı') {
        broadcastDetails.style.display = 'none';
        airedSection.style.display = 'none';
        // Madde E - Tek bolumde end_date gizli kalir, status finished olsa bile.
        endDateSection.style.display = isSingleEpisode() ? 'none' : 'block';
    } else {
        broadcastDetails.style.display = 'none';
        airedSection.style.display = 'none';
        endDateSection.style.display = 'none';
    }
}

// Madde E - Toplam bolum 1 ise yayin bitis tarihi alani anlamsiz.
function isSingleEpisode() {
    const totalEl = document.querySelector('input[name="total_episodes"]');
    if (!totalEl) return false;
    return parseInt(totalEl.value, 10) === 1;
}

function toggleEndDateBySingleEpisode() {
    // total_episodes degisikliginde end-date gorunurlugunu yeniden hesapla.
    // Status select bazen readonly olabilir (yayin tamamlandi animeler icin
    // kilitli alan); o durumda gorunurlugu hidden status input'tan da okuyarak
    // bagimsiz veriyoruz: yalniz status finished VE tek bolum degilse goster.
    const endDateSection = document.getElementById('end-date-section');
    if (!endDateSection) return;

    const statusSelect = document.querySelector('select[name="status"]');
    const statusHidden = document.querySelector('input[type="hidden"][name="status"]');
    const status = statusSelect ? statusSelect.value
                                : (statusHidden ? statusHidden.value : '');

    if (status === 'Yayın Tamamlandı' && !isSingleEpisode()) {
        endDateSection.style.display = 'block';
    } else {
        endDateSection.style.display = 'none';
    }
}

function toggleWatchedEpisodes() {
    const watchStatus = document.querySelector('select[name="watch_status"]').value;
    const watchedEpisodesDiv = document.getElementById('watched-episodes-section');
    // Watching ve OnHold: izlenen bolum input'u gorunur, mevcut deger KORUNUR.
    // Watching = aktif izleme, OnHold = ara verme; ikisinde de ilerleme saklanir.
    if (watchStatus === 'Watching' || watchStatus === 'OnHold') {
        watchedEpisodesDiv.style.display = 'block';
    } else {
        watchedEpisodesDiv.style.display = 'none';
        if (watchStatus === 'Watched') {
            // Total bos ise aired_episodes'a dus (final sayisi henuz bilinmeyen
            // devam eden seriler). aired input'u olmayabilir (defansif kontrol).
            const total = document.querySelector('input[name="total_episodes"]').value;
            const airedEl = document.querySelector('input[name="aired_episodes"]');
            const aired = airedEl ? airedEl.value : '';
            document.querySelector('input[name="watched_episodes"]').value =
                total || aired || '0';
        } else if (watchStatus === 'PlanToWatch') {
            document.querySelector('input[name="watched_episodes"]').value = '0';
        }
    }
}

// --- Tur secimi ---
function addSelectedGenre(select) {
    const genre = select.value;
    if (genre && !selectedGenres.includes(genre)) {
        selectedGenres.push(genre);
        updateGenreTags();
    }
    select.value = '';
}

function addNewGenre() {
    const newGenreInput = document.getElementById('new-genre');
    const genre = newGenreInput.value.trim();

    if (genre && !selectedGenres.includes(genre)) {
        // CSRF token formdaki gizli input'tan al, fetch body'sine ekle.
        // add_genre.php server tarafinda dogruluyor.
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        const csrfToken = csrfInput ? csrfInput.value : '';
        fetch('add_genre.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'genre=' + encodeURIComponent(genre) + '&csrf_token=' + encodeURIComponent(csrfToken)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('genre-select');
                const option = new Option(genre, genre);
                select.add(option);

                selectedGenres.push(genre);
                updateGenreTags();

                newGenreInput.value = '';
            } else {
                alert(LANG.genre_add_failed);
            }
        });
    }
}

function removeGenre(genre) {
    selectedGenres = selectedGenres.filter(g => g !== genre);
    updateGenreTags();
}

function updateGenreTags() {
    const container = document.getElementById('genre-tags');
    const input = document.getElementById('genres-input');

    container.innerHTML = selectedGenres.map(genre => `
        <div class="genre-tag">
            ${genre}
            <button type="button" onclick="removeGenre('${genre}')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');

    input.value = selectedGenres.join(',');
}

// --- Etiket girisi (oneri sistemi) ---
// Tek input; mevcut etiket kutuphanesinden eslesmeler bir acilir listede
// gosterilir; eslesme yoksa "yeni olustur" secenegi cikar. Secilen etiketler
// kaldirilabilir rozet olarak gorunur. Gizli #tags-input sunucuya virgulle
// ayrilmis isim listesi olarak gonderilir.
function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
}

function renderTagSuggestions() {
    const query = tagInput.value.trim();
    if (query === '') {
        tagSuggestions.style.display = 'none';
        return;
    }
    const lower = query.toLowerCase();
    const matches = allTags.filter(t =>
        t.toLowerCase().includes(lower) && !selectedTags.includes(t)
    );

    let html = '';
    matches.slice(0, 10).forEach(t => {
        html += `<div class="tag-suggestion-item" data-name="${escapeHtml(t)}"
                      style="padding: 6px 10px; cursor: pointer;">${escapeHtml(t)}</div>`;
    });

    // "Yeni olustur" secenegi yalniz tam eslesme (buyuk/kucuk harf duyarsiz)
    // kutuphanede veya secimde yoksa gosterilir.
    const exact = allTags.find(t => t.toLowerCase() === lower);
    const alreadySelected = selectedTags.some(t => t.toLowerCase() === lower);
    if (!exact && !alreadySelected) {
        html += `<div class="tag-suggestion-item tag-suggestion-new" data-name="${escapeHtml(query)}"
                      style="padding: 6px 10px; cursor: pointer; background: #f0f8ff; font-style: italic;">
                      ${LANG.create_new_tag_prefix} "${escapeHtml(query)}"</div>`;
    }

    if (html === '') {
        tagSuggestions.style.display = 'none';
        return;
    }

    tagSuggestions.innerHTML = html;
    tagSuggestions.style.display = 'block';
}

function addTag(name) {
    name = name.trim();
    if (name === '') return;
    // Istemci tarafi buyuk/kucuk harf duyarsiz tekrar kontrolu
    if (selectedTags.some(t => t.toLowerCase() === name.toLowerCase())) {
        return;
    }
    selectedTags.push(name);
    // Yeni bir etiketse, sonraki onerilerde cikmasi icin bellekteki
    // kutuphaneye de ekle.
    if (!allTags.some(t => t.toLowerCase() === name.toLowerCase())) {
        allTags.push(name);
    }
    tagInput.value = '';
    tagSuggestions.style.display = 'none';
    updateSelectedTags();
}

function removeTag(name) {
    selectedTags = selectedTags.filter(t => t !== name);
    updateSelectedTags();
}

function updateSelectedTags() {
    const container = document.getElementById('selected-tags');
    const hidden = document.getElementById('tags-input');
    container.innerHTML = selectedTags.map(t => `
        <div class="genre-tag">
            ${escapeHtml(t)}
            <button type="button" data-tag-name="${escapeHtml(t)}">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
    // Kaldir butonlarini bagla (kullanici verisi tirnakli inline onclick'ten kacin)
    container.querySelectorAll('button[data-tag-name]').forEach(btn => {
        btn.addEventListener('click', () => removeTag(btn.dataset.tagName));
    });
    hidden.value = selectedTags.join(',');
}

// --- AnimeSchedule "Otomatik Doldur" ---
// fetch_animeschedule.php'yi anime_schedule_link input'undaki URL ile cagirir.
// Response.fields = "form_field_name -> value" cifti (yalniz API'nin
// doldurabildigi alanlar). Her alan icin eslesen DOM elemanini bulup
// SADECE bos ise yazar - mevcut manuel giris asla ezilmez.
// broadcast_timezone ozel: form "Asia/Tokyo" (varsayilan) ile baslar, bu yuzden
// "bos" = "hala varsayilanda" sayilir; baska deger = kullanici degistirmis, dokunma.
function fetchAnimeScheduleData() {
    const urlInput = document.getElementById('anime_schedule_link');
    const statusDiv = document.getElementById('animeschedule-status');
    const btn = document.getElementById('animeschedule-fetch-btn');

    const url = (urlInput.value || '').trim();
    if (url === '') {
        statusDiv.style.color = '#c0392b';
        statusDiv.textContent = LANG.enter_animeschedule_url;
        return;
    }

    const csrfInput = document.querySelector('input[name="csrf_token"]');
    const csrfToken = csrfInput ? csrfInput.value : '';

    btn.disabled = true;
    statusDiv.style.color = '#555';
    statusDiv.textContent = LANG.fetching;

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('url', url);

    fetch('fetch_animeschedule.php', {
        method: 'POST',
        body: formData,
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            statusDiv.style.color = '#c0392b';
            statusDiv.textContent = data.error || LANG.unknown_error;
            return;
        }

        const fields = data.fields || {};
        const filled = [];
        const skipped = [];

        for (const fieldName in fields) {
            if (!Object.prototype.hasOwnProperty.call(fields, fieldName)) continue;
            const value = fields[fieldName];
            const el = document.querySelector('[name="' + fieldName + '"]');
            if (!el) {
                skipped.push(fieldName + ' ' + LANG.field_not_found_suffix);
                continue;
            }

            let isEmpty;
            if (fieldName === 'broadcast_timezone') {
                isEmpty = (el.value === '' || el.value === 'Asia/Tokyo');
            } else {
                isEmpty = (el.value === '' || el.value === null);
            }

            if (!isEmpty) {
                skipped.push(fieldName);
                continue;
            }

            el.value = value;
            filled.push(fieldName);

            // status, broadcast/aired/end_date bolumlerinin gorunurlugunu
            // toggleBroadcastDetails() ile yonetir; yeni dolan broadcast_day/time
            // gorunsun diye tetikle.
            if (fieldName === 'status' && typeof toggleBroadcastDetails === 'function') {
                toggleBroadcastDetails();
            }
        }

        if (filled.length === 0) {
            statusDiv.style.color = '#888';
            statusDiv.textContent = LANG.no_empty_fields;
        } else {
            statusDiv.style.color = '#27ae60';
            statusDiv.textContent = LANG.fields_filled_prefix + ' ' + filled.length + ': ' + filled.join(', ') + '.';
        }
    })
    .catch(err => {
        statusDiv.style.color = '#c0392b';
        statusDiv.textContent = LANG.request_failed_prefix + ' ' + err.message;
    })
    .finally(() => {
        btn.disabled = false;
    });
}

// --- Etiket girisi olay baglantilari ---
if (tagInput && tagSuggestions) {
    tagInput.addEventListener('input', renderTagSuggestions);
    tagInput.addEventListener('focus', renderTagSuggestions);

    tagSuggestions.addEventListener('click', e => {
        const item = e.target.closest('.tag-suggestion-item');
        if (item) {
            addTag(item.dataset.name);
        }
    });

    tagInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const v = tagInput.value.trim();
            if (v !== '') {
                addTag(v);
            }
        } else if (e.key === 'Escape') {
            tagSuggestions.style.display = 'none';
        }
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('.tag-input-wrapper')) {
            tagSuggestions.style.display = 'none';
        }
    });
}

// --- Baslangic render'i ---
// Secili tur ve etiketleri rozet olarak goster. add_anime icin ANIME_FORM.genres
// / .tags bos oldugundan bu cagrilar bos render uretir (zararsiz); edit_anime
// icin mevcut tur/etiketleri yukler.
(function initAnimeForm() {
    function render() {
        if (document.getElementById('genre-tags')) updateGenreTags();
        if (document.getElementById('selected-tags')) updateSelectedTags();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', render);
    } else {
        render();
    }
})();
