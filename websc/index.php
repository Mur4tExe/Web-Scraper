<?php
// index.php - Neo-Brutalist Web Scraper
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#000000">
    <title>WEB KAZIYICI · URL Scraper</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 650px;
        }

        /* Logo */
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 10px;
            background: #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #000000;
        }

        .logo-icon svg {
            width: 28px;
            height: 28px;
            stroke: #ffffff;
        }

        .logo-text {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #000000;
        }

        .logo-text span {
            background: #000000;
            color: #ffffff;
            padding: 2px 8px;
        }

        /* Ana Kart */
        .main-card {
            background: #ffffff;
            border: 3px solid #000000;
            padding: 40px 35px;
            box-shadow: 10px 10px 0 #000000;
            transition: all 0.2s ease;
        }

        .main-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: 12px 12px 0 #000000;
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
            color: #000000;
        }

        .card-subtitle {
            font-size: 12px;
            color: #666;
            margin-bottom: 25px;
            font-weight: 500;
        }

        /* Input Grubu */
        .input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .url-input {
            flex: 1;
            padding: 14px 16px;
            border: 3px solid #000000;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 14px;
            font-weight: 600;
            outline: none;
            transition: all 0.2s ease;
            background: #ffffff;
            color: #000000;
        }

        .url-input:focus {
            background: #f9f9f9;
            transform: translate(-2px, -2px);
            box-shadow: 4px 4px 0 #000000;
        }

        .url-input::placeholder {
            color: #999;
            font-weight: 400;
        }

        .scrape-btn {
            padding: 14px 24px;
            background: #000000;
            color: #ffffff;
            border: 3px solid #000000;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            letter-spacing: 1px;
        }

        .scrape-btn:hover {
            background: #222222;
            transform: translate(-2px, -2px);
            box-shadow: 4px 4px 0 #000000;
        }

        .scrape-btn:active {
            transform: translate(0, 0);
            box-shadow: none;
        }

        .scrape-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Yükleniyor */
        .loading {
            display: none;
            margin-top: 10px;
            height: 4px;
            background: #eee;
            overflow: hidden;
        }

        .loading.active {
            display: block;
        }

        .loading-bar {
            height: 100%;
            width: 40%;
            background: #000000;
            animation: loadSlide 1s ease-in-out infinite;
        }

        /* Hata */
        .error-box {
            display: none;
            margin-top: 15px;
            padding: 12px 16px;
            border: 2px solid #ff0000;
            background: #fff5f5;
            font-size: 12px;
            font-weight: 600;
            color: #ff0000;
            align-items: center;
            gap: 10px;
        }

        .error-box.active {
            display: flex;
        }

        .error-box svg {
            width: 18px;
            height: 18px;
            stroke: #ff0000;
            flex-shrink: 0;
        }

        /* Sonuç Kartı */
        .result-card {
            display: none;
            background: #ffffff;
            border: 3px solid #000000;
            margin-top: 20px;
            box-shadow: 10px 10px 0 #000000;
        }

        .result-card.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 2px solid #000000;
            flex-wrap: wrap;
            gap: 10px;
        }

        .result-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .result-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 4px 10px;
            border: 2px solid #000000;
            background: #000000;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Tablar */
        .tabs {
            display: flex;
            border-bottom: 2px solid #000000;
            overflow-x: auto;
            gap: 0;
        }

        .tabs::-webkit-scrollbar {
            height: 0;
        }

        .tab {
            padding: 10px 18px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            border: none;
            background: transparent;
            color: #666;
            font-family: 'Space Grotesk', sans-serif;
            transition: all 0.2s ease;
            white-space: nowrap;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }

        .tab:hover {
            color: #000000;
        }

        .tab.active {
            color: #000000;
            border-bottom-color: #000000;
        }

        /* İçerik Paneli */
        .panel {
            display: none;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }

        .panel.active {
            display: block;
        }

        /* İstatistik Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-item {
            border: 2px solid #000000;
            padding: 14px 10px;
            text-align: center;
            transition: all 0.2s ease;
        }

        .stat-item:hover {
            transform: translate(-2px, -2px);
            box-shadow: 4px 4px 0 #000000;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 800;
            color: #000000;
        }

        .stat-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #666;
            margin-top: 4px;
        }

        /* Link Listesi */
        .link-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .link-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border: 2px solid #000000;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .link-item:hover {
            transform: translate(-2px, -2px);
            box-shadow: 4px 4px 0 #000000;
        }

        .link-badge {
            font-size: 9px;
            font-weight: 800;
            padding: 3px 8px;
            border: 2px solid #000000;
            text-transform: uppercase;
            letter-spacing: 1px;
            flex-shrink: 0;
        }

        .link-badge.in {
            background: #000000;
            color: #ffffff;
        }

        .link-badge.out {
            background: #ffffff;
            color: #000000;
        }

        .link-text {
            flex: 1;
            font-size: 12px;
            font-weight: 600;
            color: #000000;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .link-arrow {
            width: 14px;
            height: 14px;
            stroke: #666;
            flex-shrink: 0;
        }

        /* Görsel Grid */
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 8px;
        }

        .image-card {
            border: 2px solid #000000;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .image-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: 4px 4px 0 #000000;
        }

        .image-card img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            display: block;
        }

        .image-alt {
            padding: 6px 8px;
            font-size: 9px;
            font-weight: 600;
            color: #666;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Başlık Listesi */
        .heading-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .heading-item {
            display: flex;
            gap: 10px;
            padding: 8px 12px;
            border: 2px solid #000000;
            font-size: 13px;
            font-weight: 600;
            align-items: center;
        }

        .heading-tag {
            font-size: 10px;
            font-weight: 800;
            padding: 3px 8px;
            background: #000000;
            color: #ffffff;
            border: 2px solid #000000;
            flex-shrink: 0;
        }

        /* Meta Tablosu */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 10px 12px;
            border-bottom: 2px solid #000000;
            font-size: 12px;
            font-weight: 600;
            vertical-align: top;
        }

        .meta-table td:first-child {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 35%;
            background: #f9f9f9;
        }

        /* Ham Kod */
        .raw-code {
            padding: 14px;
            border: 2px solid #000000;
            font-family: monospace;
            font-size: 11px;
            color: #000000;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.5;
            background: #fafafa;
        }

        /* Buton Grubu */
        .btn-group {
            display: flex;
            gap: 8px;
            padding: 16px 20px;
            border-top: 2px solid #000000;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 16px;
            border: 2px solid #000000;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #ffffff;
            color: #000000;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn:hover {
            transform: translate(-2px, -2px);
            box-shadow: 4px 4px 0 #000000;
        }

        .btn:active {
            transform: none;
            box-shadow: none;
        }

        .btn-dark {
            background: #000000;
            color: #ffffff;
        }

        .btn-dark:hover {
            background: #222222;
        }

        .btn svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #999;
        }

        .footer span {
            color: #000000;
        }

        /* Animasyonlar */
        @keyframes loadSlide {
            0% { transform: translateX(-120%); }
            100% { transform: translateX(350%); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Scrollbar */
        .panel::-webkit-scrollbar {
            width: 6px;
        }

        .panel::-webkit-scrollbar-track {
            background: #f5f5f5;
        }

        .panel::-webkit-scrollbar-thumb {
            background: #000000;
        }

        /* Responsive */
        @media (max-width: 500px) {
            .input-group {
                flex-direction: column;
            }
            .main-card {
                padding: 25px 20px;
            }
            .panel {
                padding: 14px;
            }
            .btn-group {
                padding: 12px 14px;
            }
            .btn {
                font-size: 10px;
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo -->
        <div class="logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v8M8 12h8"/>
                </svg>
            </div>
            <div class="logo-text">WEB <span>KAZIYICI</span></div>
        </div>

        <!-- Ana Kart -->
        <div class="main-card">
            <div class="card-title">URL GİRİN</div>
            <div class="card-subtitle">Hedef siteyi kazıyın · Link · Görsel · Meta · Başlık</div>
            
            <div class="input-group">
                <input 
                    class="url-input" 
                    id="urlInput" 
                    type="text" 
                    placeholder="https://example.com" 
                    autocomplete="off"
                    autofocus
                >
                <button class="scrape-btn" id="scrapeBtn">KAZI</button>
            </div>
            
            <div class="loading" id="loading">
                <div class="loading-bar"></div>
            </div>
            
            <div class="error-box" id="errorBox">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span id="errorMsg"></span>
            </div>
        </div>

        <!-- Sonuç Kartı -->
        <div class="result-card" id="resultCard">
            <div class="result-header">
                <span class="result-title">KAZI SONUCU</span>
                <span class="result-badge" id="resultBadge">HTTP 200</span>
            </div>
            
            <div class="tabs">
                <button class="tab active" data-tab="overview">ÖZET</button>
                <button class="tab" data-tab="links">LİNKLER</button>
                <button class="tab" data-tab="images">GÖRSELLER</button>
                <button class="tab" data-tab="meta">META</button>
                <button class="tab" data-tab="headings">BAŞLIKLAR</button>
                <button class="tab" data-tab="text">METİN</button>
            </div>
            
            <div class="panel active" id="panelOverview"></div>
            <div class="panel" id="panelLinks"></div>
            <div class="panel" id="panelImages"></div>
            <div class="panel" id="panelMeta"></div>
            <div class="panel" id="panelHeadings"></div>
            <div class="panel" id="panelText"></div>
            
            <div class="btn-group">
                <button class="btn btn-dark" id="zipBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    ZIP
                </button>
                <button class="btn" id="jsonBtn">
                    JSON
                </button>
                <button class="btn" id="csvBtn">
                    CSV
                </button>
                <button class="btn" id="copyBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2"/>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                    </svg>
                    KOPYALA
                </button>
                <button class="btn" id="clearBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    TEMİZLE
                </button>
            </div>
        </div>
        
        <div class="footer">
            WEB <span>KAZIYICI</span> · PHP cURL · © 2024
        </div>
    </div>

    <script>
        let scrapedData = null;

        // Tab değiştirme
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById('panel' + tab.dataset.tab.charAt(0).toUpperCase() + tab.dataset.tab.slice(1)).classList.add('active');
            });
        });

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function showError(msg) {
            const box = document.getElementById('errorBox');
            document.getElementById('errorMsg').textContent = msg;
            box.classList.add('active');
            setTimeout(() => box.classList.remove('active'), 6000);
        }

        function hideError() {
            document.getElementById('errorBox').classList.remove('active');
        }

        // Kazıma işlemi
        async function doScrape() {
            let url = document.getElementById('urlInput').value.trim();
            if (!url) return showError('LÜTFEN BİR URL GİRİN');
            if (!url.startsWith('http')) url = 'https://' + url;

            hideError();
            document.getElementById('resultCard').classList.remove('active');
            document.getElementById('loading').classList.add('active');
            
            const btn = document.getElementById('scrapeBtn');
            btn.disabled = true;
            btn.textContent = 'KAZINIYOR...';

            try {
                const response = await fetch('scrape.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url })
                });

                const data = await response.json();

                if (data.error) {
                    showError(data.error);
                    return;
                }

                scrapedData = data;
                renderResults(data);
                document.getElementById('resultBadge').textContent = 'HTTP ' + data.httpCode;
                document.getElementById('resultCard').classList.add('active');
                document.getElementById('resultCard').scrollIntoView({ behavior: 'smooth' });

            } catch (err) {
                showError('BAĞLANTI HATASI · scrape.php mevcut mu?');
            } finally {
                document.getElementById('loading').classList.remove('active');
                btn.disabled = false;
                btn.textContent = 'KAZI';
            }
        }

        function renderResults(data) {
            const baseHost = (() => { try { return new URL(data.url).hostname; } catch { return ''; } })();

            // Özet
            document.getElementById('panelOverview').innerHTML = `
                <div class="stats-grid">
                    <div class="stat-item"><div class="stat-number">${data.stats.links}</div><div class="stat-label">LİNK</div></div>
                    <div class="stat-item"><div class="stat-number">${data.stats.images}</div><div class="stat-label">GÖRSEL</div></div>
                    <div class="stat-item"><div class="stat-number">${data.stats.headings}</div><div class="stat-label">BAŞLIK</div></div>
                    <div class="stat-item"><div class="stat-number">${data.stats.paragraphs}</div><div class="stat-label">PARAGRAF</div></div>
                    <div class="stat-item"><div class="stat-number">${Math.round(data.stats.htmlSize / 1024)}KB</div><div class="stat-label">BOYUT</div></div>
                </div>
                ${data.title ? `<p style="font-weight:700;font-size:16px;margin-bottom:6px;">${escapeHtml(data.title)}</p>` : ''}
                ${data.meta.description ? `<p style="color:#666;font-size:12px;">${escapeHtml(data.meta.description)}</p>` : ''}
            `;

            // Linkler
            document.getElementById('panelLinks').innerHTML = data.links.length ? `
                <div class="link-list">
                    ${data.links.map(l => `
                        <a class="link-item" href="${escapeHtml(l.href)}" target="_blank" rel="noopener">
                            <span class="link-badge ${l.internal ? 'in' : 'out'}">${l.internal ? 'İÇ' : 'DIŞ'}</span>
                            <span class="link-text">${escapeHtml(l.text)}</span>
                            <svg class="link-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 17L17 7M17 7H7M17 7v10"/>
                            </svg>
                        </a>
                    `).join('')}
                </div>
            ` : '<p style="color:#999;font-size:12px;font-weight:600;">LİNK BULUNAMADI</p>';

            // Görseller
            document.getElementById('panelImages').innerHTML = data.images.length ? `
                <div class="image-grid">
                    ${data.images.map(img => `
                        <a class="image-card" href="${escapeHtml(img.src)}" target="_blank">
                            <img src="${escapeHtml(img.src)}" alt="${escapeHtml(img.alt)}" loading="lazy" onerror="this.parentElement.style.display='none'">
                            <div class="image-alt">${escapeHtml((img.alt || '').substring(0, 30))}</div>
                        </a>
                    `).join('')}
                </div>
            ` : '<p style="color:#999;font-size:12px;font-weight:600;">GÖRSEL BULUNAMADI</p>';

            // Meta
            document.getElementById('panelMeta').innerHTML = Object.keys(data.meta).length ? `
                <table class="meta-table">
                    ${Object.entries(data.meta).map(([k, v]) => `<tr><td>${escapeHtml(k)}</td><td>${escapeHtml(v)}</td></tr>`).join('')}
                </table>
            ` : '<p style="color:#999;font-size:12px;font-weight:600;">META BULUNAMADI</p>';

            // Başlıklar
            document.getElementById('panelHeadings').innerHTML = data.headings.length ? `
                <div class="heading-list">
                    ${data.headings.map(h => `
                        <div class="heading-item">
                            <span class="heading-tag">${h.tag}</span>
                            <span>${escapeHtml(h.text)}</span>
                        </div>
                    `).join('')}
                </div>
            ` : '<p style="color:#999;font-size:12px;font-weight:600;">BAŞLIK BULUNAMADI</p>';

            // Metin
            document.getElementById('panelText').innerHTML = data.text ? `
                <div class="raw-code">${escapeHtml(data.text)}</div>
            ` : '<p style="color:#999;font-size:12px;font-weight:600;">METİN BULUNAMADI</p>';
        }

        // ZIP indirme
        async function downloadZip() {
            if (!scrapedData) return showError('ÖNCE KAZIMA YAPIN');
            const btn = document.getElementById('zipBtn');
            btn.disabled = true;
            btn.textContent = 'HAZIRLANIYOR...';
            
            try {
                const res = await fetch('download.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ data: scrapedData })
                });
                const blob = await res.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'scrape_' + (scrapedData.title || 'export').substring(0, 25) + '.zip';
                a.click();
                URL.revokeObjectURL(url);
            } catch (e) {
                showError('ZIP HATASI');
            } finally {
                btn.disabled = false;
                btn.textContent = 'ZIP';
            }
        }

        function downloadJSON() {
            if (!scrapedData) return showError('ÖNCE KAZIMA YAPIN');
            const blob = new Blob([JSON.stringify(scrapedData, null, 2)], { type: 'application/json' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'scrape_' + (scrapedData.title || 'export').substring(0, 25) + '.json';
            a.click();
        }

        function downloadCSV() {
            if (!scrapedData?.links) return showError('ÖNCE KAZIMA YAPIN');
            const host = (() => { try { return new URL(scrapedData.url).hostname; } catch { return ''; } })();
            let csv = "Metin,URL,Tür\n";
            scrapedData.links.forEach(l => {
                csv += `"${(l.text||'').replace(/"/g,'""')}","${l.href.replace(/"/g,'""')}","${l.href.includes(host)?'İç':'Dış'}"\n`;
            });
            const blob = new Blob(["\uFEFF"+csv], { type: 'text/csv' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'links.csv';
            a.click();
        }

        function copyData() {
            if (!scrapedData) return showError('ÖNCE KAZIMA YAPIN');
            const text = `URL: ${scrapedData.url}\nBaşlık: ${scrapedData.title}\nLink: ${scrapedData.stats.links}\nGörsel: ${scrapedData.stats.images}\n\n${scrapedData.text?.substring(0,1500) || ''}`;
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('copyBtn');
                const orig = btn.innerHTML;
                btn.innerHTML = '✓ KOPYALANDI';
                btn.style.background = '#000';
                btn.style.color = '#fff';
                setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; btn.style.color = ''; }, 1500);
            });
        }

        function clearAll() {
            scrapedData = null;
            document.getElementById('resultCard').classList.remove('active');
            document.getElementById('urlInput').value = '';
            document.getElementById('urlInput').focus();
            hideError();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Event listener'lar
        document.getElementById('scrapeBtn').addEventListener('click', doScrape);
        document.getElementById('urlInput').addEventListener('keypress', e => { if (e.key === 'Enter') doScrape(); });
        document.getElementById('zipBtn').addEventListener('click', downloadZip);
        document.getElementById('jsonBtn').addEventListener('click', downloadJSON);
        document.getElementById('csvBtn').addEventListener('click', downloadCSV);
        document.getElementById('copyBtn').addEventListener('click', copyData);
        document.getElementById('clearBtn').addEventListener('click', clearAll);
    </script>
</body>
</html>