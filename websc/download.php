<?php
// Hataları logla ama gösterme
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/errors.log');

// Her zaman JSON header
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

// Rate limiting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$key = 'dl_' . md5($ip);
$tkey = 'dt_' . md5($ip);

if (!isset($_SESSION[$tkey]) || (time() - $_SESSION[$tkey]) > 60) {
    $_SESSION[$key] = 0;
    $_SESSION[$tkey] = time();
}

$_SESSION[$key]++;

if ($_SESSION[$key] > 5) {
    http_response_code(429);
    echo json_encode(['error' => 'ÇOK FAZLA İNDİRME (5/dk). BEKLEYİN.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Veriyi al
$body = json_decode(file_get_contents('php://input'), true);
$data = $body['data'] ?? null;

if (!$data || !isset($data['url'])) {
    http_response_code(400);
    echo json_encode(['error' => 'GEÇERSİZ VERİ'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Güvenli dosya adı - İNGİLİZCE karakterler
$title = $data['title'] ?? 'kazima';
// Türkçe karakterleri İngilizce karşılıklarına çevir
$tr = ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'];
$en = ['i', 'g', 'u', 's', 'o', 'c', 'I', 'G', 'U', 'S', 'O', 'C'];
$title = str_replace($tr, $en, $title);
// Sadece güvenli karakterler
$title = preg_replace('/[^a-zA-Z0-9_\-]/u', '_', $title);
$title = mb_substr($title, 0, 40);
$title = basename($title);
if (empty(trim($title, '_'))) $title = 'kazima';

// Geçici klasör
$tmp = sys_get_temp_dir() . '/scraper_' . uniqid();
if (!mkdir($tmp, 0755, true) || !mkdir($tmp . '/images', 0755, true)) {
    http_response_code(500);
    echo json_encode(['error' => 'KLASÖR OLUŞTURULAMADI'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper
function dec($t) { return html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }

// İmza
$signature = "\n\n---\n📢 Telegram Kanalı: t.me/QueryBots\n👨‍💻 Geliştirici: t.me/CrosOrj\n🔗 Web Kaziyici Pro";

// 1. page.html
file_put_contents($tmp . '/page.html', ($data['html'] ?? '') . "\n<!-- Kaziyici by t.me/CrosOrj -->");

// 2. rapor.json
$report = [
    'tarih' => date('Y-m-d H:i:s'),
    'url' => $data['url'] ?? '',
    'baslik' => dec($data['title'] ?? ''),
    'meta' => $data['meta'] ?? [],
    'basliklar' => $data['headings'] ?? [],
    'istatistikler' => $data['stats'] ?? [],
    'linkler' => array_slice($data['links'] ?? [], 0, 200),
    'paragraflar' => $data['paragraphs'] ?? [],
    'gelistirici' => 't.me/CrosOrj',
    'kanal' => 't.me/QueryBots',
];
file_put_contents($tmp . '/rapor.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

// 3. linkler.csv
$host = parse_url($data['url'] ?? '', PHP_URL_HOST);
$csv = "\xEF\xBB\xBFMetin,URL,Tür\n";
foreach (($data['links'] ?? []) as $l) {
    $lh = parse_url($l['href'] ?? '', PHP_URL_HOST);
    $type = ($host && $lh && strpos($lh, $host) !== false) ? 'İÇ' : 'DIŞ';
    $csv .= '"' . str_replace('"', '""', dec($l['text'] ?? '')) . '",'
          . '"' . str_replace('"', '""', $l['href'] ?? '') . '",'
          . '"' . $type . '"' . "\n";
}
$csv .= "\n\"t.me/CrosOrj\",\"https://t.me/CrosOrj\",\"Geliştirici\"\n";
$csv .= "\"t.me/QueryBots\",\"https://t.me/QueryBots\",\"Kanal\"\n";
file_put_contents($tmp . '/linkler.csv', $csv);

// 4. gorseller.csv
$csvImg = "\xEF\xBB\xBFURL,Alt,Boyut\n";
foreach (($data['images'] ?? []) as $img) {
    $csvImg .= '"' . str_replace('"', '""', $img['src'] ?? '') . '",'
             . '"' . str_replace('"', '""', dec($img['alt'] ?? '')) . '",'
             . '"' . ($img['width'] ? $img['width'] . 'x' . $img['height'] : '?') . '"' . "\n";
}
$csvImg .= "\n\"https://t.me/CrosOrj\",\"Geliştirici\",\"t.me/CrosOrj\"\n";
$csvImg .= "\"https://t.me/QueryBots\",\"Telegram Kanalı\",\"t.me/QueryBots\"\n";
file_put_contents($tmp . '/gorseller.csv', $csvImg);

// 5. metin.txt
file_put_contents($tmp . '/metin.txt', ($data['text'] ?? '') . $signature);

// 6. basliklar.txt
$htxt = '';
foreach (($data['headings'] ?? []) as $h) {
    $htxt .= '[' . $h['tag'] . '] ' . dec($h['text']) . "\n\n";
}
$htxt .= $signature;
file_put_contents($tmp . '/basliklar.txt', $htxt);

// 7. paragraflar.txt
$ptxt = '';
foreach (($data['paragraphs'] ?? []) as $p) {
    $ptxt .= dec($p) . "\n\n---\n\n";
}
$ptxt .= $signature;
file_put_contents($tmp . '/paragraflar.txt', $ptxt);

// 8. Görselleri indir
$log = [];
$downloaded = 0;
$mh = curl_multi_init();
$handles = [];

foreach (($data['images'] ?? []) as $i => $img) {
    if ($downloaded >= 25) break;
    $src = $img['src'] ?? '';
    if (!$src || !str_starts_with($src, 'http')) continue;
    
    $ext = strtolower(pathinfo(parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'])) $ext = 'jpg';
    
    $fname = 'img_' . sprintf('%03d', $i + 1) . '.' . $ext;
    $path = $tmp . '/images/' . $fname;
    
    $ch = curl_init($src);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 Chrome/120.0.0.0',
        CURLOPT_MAXFILESIZE => 2 * 1024 * 1024,
    ]);
    curl_multi_add_handle($mh, $ch);
    $handles[(int)$ch] = ['ch' => $ch, 'path' => $path, 'src' => $src, 'alt' => $img['alt'] ?? ''];
}

do { curl_multi_exec($mh, $running); curl_multi_select($mh); } while ($running > 0);

foreach ($handles as $item) {
    $ch = $item['ch'];
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $imgData = curl_multi_getcontent($ch);
    $size = strlen($imgData);
    
    if ($imgData && $code === 200 && $size > 100) {
        file_put_contents($item['path'], $imgData);
        $log[] = [
            'dosya' => basename($item['path']), 
            'src' => $item['src'], 
            'alt' => dec($item['alt']), 
            'boyut' => $size
        ];
        $downloaded++;
    }
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

if ($log) {
    file_put_contents($tmp . '/images/indirilenler.json', json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// 9. README.md
$readme = "# WEB KAZIYICI RAPORU\n\n";
$readme .= "**URL:** " . ($data['url'] ?? '') . "\n";
$readme .= "**Başlık:** " . dec($data['title'] ?? '') . "\n";
$readme .= "**Tarih:** " . date('Y-m-d H:i:s') . "\n\n";
$readme .= "## DOSYALAR\n\n";
$readme .= "| Dosya | Açıklama |\n|-------|----------|\n";
$readme .= "| page.html | Orijinal HTML |\n";
$readme .= "| rapor.json | JSON Rapor |\n";
$readme .= "| linkler.csv | Linkler (Excel) |\n";
$readme .= "| gorseller.csv | Görsel Listesi |\n";
$readme .= "| metin.txt | Temiz Metin |\n";
$readme .= "| basliklar.txt | H1-H3 Başlıkları |\n";
$readme .= "| paragraflar.txt | Paragraflar |\n";
$readme .= "| images/ | İndirilen Görseller ($downloaded adet) |\n\n";
$readme .= "## İSTATİSTİKLER\n\n";
$readme .= "- Link: " . ($data['stats']['links'] ?? 0) . "\n";
$readme .= "- Görsel: " . ($data['stats']['images'] ?? 0) . " ($downloaded indirildi)\n";
$readme .= "- Başlık: " . ($data['stats']['headings'] ?? 0) . "\n";
$readme .= "- Paragraf: " . ($data['stats']['paragraphs'] ?? 0) . "\n";
$readme .= "- Metin: " . ($data['stats']['textLen'] ?? 0) . " karakter\n";
$readme .= "- HTML Boyutu: " . round(($data['stats']['htmlSize'] ?? 0) / 1024) . " KB\n\n";
$readme .= "---\n\n";
$readme .= "## 📢 GELİŞTİRİCİ & KANAL\n\n";
$readme .= "**👨‍💻 Geliştirici:** [t.me/CrosOrj](https://t.me/CrosOrj)\n";
$readme .= "**📢 Telegram Kanalı:** [t.me/QueryBots](https://t.me/QueryBots)\n\n";
$readme .= "> Web Kaziyici Pro v1.0\n";
file_put_contents($tmp . '/README.md', $readme);

// 10. ZIP oluştur
if (!class_exists('ZipArchive')) {
    $clean = function($d) use (&$clean) {
        foreach (glob($d . '/*') as $f) is_dir($f) ? $clean($f) : unlink($f);
        rmdir($d);
    };
    $clean($tmp);
    http_response_code(500);
    echo json_encode(['error' => 'ZIP UZANTISI GEREKLİ'], JSON_UNESCAPED_UNICODE);
    exit;
}

$zipFile = sys_get_temp_dir() . '/' . $title . '_' . time() . '.zip';
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo json_encode(['error' => 'ZIP OLUŞTURULAMADI'], JSON_UNESCAPED_UNICODE);
    exit;
}

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp));
foreach ($files as $f) {
    if ($f->isDir()) continue;
    $zip->addFile($f->getRealPath(), substr($f->getRealPath(), strlen($tmp) + 1));
}
$zip->close();

// Temizlik
$clean = function($d) use (&$clean) {
    foreach (glob($d . '/*') as $f) is_dir($f) ? $clean($f) : unlink($f);
    rmdir($d);
};
$clean($tmp);

// ZIP'i gönder
if (!file_exists($zipFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'ZIP BULUNAMADI'], JSON_UNESCAPED_UNICODE);
    exit;
}

header_remove('Content-Type');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $title . '_kazima.zip"');
header('Content-Length: ' . filesize($zipFile));
header('Cache-Control: no-cache');
readfile($zipFile);
unlink($zipFile);
exit;