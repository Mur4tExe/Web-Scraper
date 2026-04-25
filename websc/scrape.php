<?php
// Tüm hataları JSON olarak göster
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Her durumda JSON header
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
}

// Fatal error'ları yakala
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'error' => 'SUNUCU HATASI',
            'detail' => $error['message'] ?? 'Bilinmeyen hata'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

// Exception'ları yakala
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'BEKLENMEYEN HATA',
        'detail' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

// Hata loglama
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/errors.log');

// Rate Limiting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$key = 'rl_' . md5($ip);
$tkey = 'rt_' . md5($ip);

if (!isset($_SESSION[$tkey]) || (time() - $_SESSION[$tkey]) > 60) {
    $_SESSION[$key] = 0;
    $_SESSION[$tkey] = time();
}

$_SESSION[$key]++;

if ($_SESSION[$key] > 20) {
    http_response_code(429);
    echo json_encode(['error' => 'ÇOK FAZLA İSTEK (20/dk). BEKLEYİN.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Güvenlik: Private IP engeli
function is_blocked($url) {
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return true;
    
    $blocked = ['localhost', '127.0.0.1', '0.0.0.0', '[::1]', '::1', '10.0.0.0', '172.16.0.0', '192.168.0.0'];
    if (in_array(strtolower($host), $blocked)) return true;
    
    $port = parse_url($url, PHP_URL_PORT);
    if ($port && !in_array($port, [80, 443, 8080, 8443])) return true;
    
    $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
    if ($ip === $host) return true;
    
    return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}

// HTML temizleme
function clean($html) {
    if (!mb_check_encoding($html, 'UTF-8')) {
        $html = mb_convert_encoding($html, 'UTF-8', 'auto');
    }
    $html = preg_replace_callback('/&#([0-9]+);/', fn($m) => mb_chr((int)$m[1], 'UTF-8'), $html);
    $html = preg_replace_callback('/&#x([0-9a-f]+);/i', fn($m) => mb_chr(hexdec($m[1]), 'UTF-8'), $html);
    return mb_convert_encoding($html, 'UTF-8', 'UTF-8');
}

// Ana kazıma fonksiyonu
function scrape($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['error' => 'GEÇERSİZ URL', 'code' => 400];
    }

    $p = parse_url($url);
    if (!in_array($p['scheme'] ?? '', ['http', 'https'])) {
        return ['error' => 'SADECE HTTP/HTTPS', 'code' => 400];
    }

    if (is_blocked($url)) {
        return ['error' => 'ÖZEL IP YASAK', 'code' => 403];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: tr-TR,tr;q=0.9,en;q=0.8',
            'Accept-Encoding: gzip, deflate, br',
        ],
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXFILESIZE    => 5 * 1024 * 1024,
    ]);

    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) return ['error' => 'BAĞLANTI HATASI: ' . $err, 'code' => 500];
    if ($code === 403) return ['error' => 'ERİŞİM REDDEDİLDİ (403). BOT ENGELİ OLABİLİR.', 'code' => 403];
    if ($code === 404) return ['error' => 'SAYFA BULUNAMADI (404)', 'code' => 404];
    if ($code >= 400) return ['error' => "HTTP HATASI: $code", 'code' => $code];
    if (!$html) return ['error' => 'BOŞ İÇERİK', 'code' => 500];
    if (strpos($ctype, 'text/html') === false && strpos($ctype, 'application/xhtml') === false) {
        return ['error' => 'HTML DEĞİL: ' . $ctype, 'code' => 415];
    }

    $html = clean($html);

    // DOM
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    libxml_use_internal_errors(true);
    $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xp = new DOMXPath($dom);

    // Başlık
    $tn = $xp->query('//title');
    $title = $tn->length ? trim($tn->item(0)->textContent) : '';
    $title = mb_substr($title, 0, 200);

    // Meta
    $meta = [];
    $important = ['description', 'keywords', 'author', 'viewport', 'robots', 'og:title', 'og:description', 'og:image', 'twitter:card'];
    foreach ($xp->query('//meta') as $m) {
        $name = $m->getAttribute('name') ?: $m->getAttribute('property');
        $content = $m->getAttribute('content');
        if ($name && $content && in_array($name, $important)) {
            $meta[$name] = mb_substr($content, 0, 300);
        }
    }

    // Başlıklar
    $headings = [];
    foreach (['h1', 'h2', 'h3'] as $tag) {
        foreach ($xp->query("//$tag") as $n) {
            $t = trim($n->textContent);
            if ($t && mb_strlen($t) < 200) {
                $headings[] = ['tag' => strtoupper($tag), 'text' => mb_substr($t, 0, 150)];
                if (count($headings) >= 20) break 2;
            }
        }
    }

    // Linkler
    $links = [];
    $host = $p['host'];
    foreach ($xp->query('//a[@href]') as $a) {
        if (count($links) >= 100) break;
        $href = $a->getAttribute('href');
        $text = trim($a->textContent);
        if (!$href || $href === '#' || strpos($href, 'javascript:') === 0 || strpos($href, 'mailto:') === 0) continue;
        if (mb_strlen($text) > 80) $text = mb_substr($text, 0, 77) . '...';
        
        if (!str_starts_with($href, 'http')) {
            if (str_starts_with($href, '//')) $href = $p['scheme'] . ':' . $href;
            elseif (str_starts_with($href, '/')) $href = $p['scheme'] . '://' . $p['host'] . $href;
            else $href = rtrim($url, '/') . '/' . ltrim($href, '/');
        }
        
        if (!preg_match('/^https?:\/\//', $href)) continue;
        
        $links[] = [
            'text' => $text ?: '[Link]',
            'href' => $href,
            'internal' => (strpos($href, $host) !== false)
        ];
    }

    // Görseller
    $images = [];
    foreach ($xp->query('//img[@src]') as $img) {
        if (count($images) >= 50) break;
        $src = $img->getAttribute('src');
        if (!$src || str_starts_with($src, 'data:')) continue;
        if (!str_starts_with($src, 'http')) {
            if (str_starts_with($src, '//')) $src = $p['scheme'] . ':' . $src;
            elseif (str_starts_with($src, '/')) $src = $p['scheme'] . '://' . $p['host'] . $src;
            else $src = rtrim($url, '/') . '/' . ltrim($src, '/');
        }
        $images[] = [
            'src' => $src,
            'alt' => mb_substr($img->getAttribute('alt'), 0, 100) ?: 'Görsel',
            'width' => $img->getAttribute('width'),
            'height' => $img->getAttribute('height')
        ];
    }

    // Paragraflar
    $paragraphs = [];
    foreach ($xp->query('//p') as $p) {
        if (count($paragraphs) >= 15) break;
        $t = trim($p->textContent);
        if (mb_strlen($t) > 40 && mb_strlen($t) < 800) {
            $paragraphs[] = mb_substr($t, 0, 500);
        }
    }

    // Temiz metin
    foreach ($xp->query('//script | //style | //nav | //footer | //aside') as $n) {
        if ($n->parentNode) $n->parentNode->removeChild($n);
    }
    $body = $xp->query('//body');
    $text = $body->length ? trim($body->item(0)->textContent) : '';
    $text = mb_substr(preg_replace('/\s+/', ' ', $text), 0, 5000);

    return [
        'url'        => $url,
        'httpCode'   => $code,
        'title'      => $title,
        'meta'       => $meta,
        'headings'   => $headings,
        'links'      => $links,
        'images'     => $images,
        'paragraphs' => $paragraphs,
        'text'       => $text,
        'html'       => mb_substr($html, 0, 20000),
        'stats'      => [
            'links'      => count($links),
            'images'     => count($images),
            'headings'   => count($headings),
            'paragraphs' => count($paragraphs),
            'textLen'    => mb_strlen($text),
            'htmlSize'   => mb_strlen($html),
        ],
    ];
}

// --- İSTEK İŞLE ---
$url = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $url = trim($body['url'] ?? '');
} else {
    $url = trim($_GET['url'] ?? '');
}

if (!$url) {
    echo json_encode(['error' => 'URL GEREKLİ', 'code' => 400], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!str_starts_with($url, 'http')) $url = 'https://' . $url;

$result = scrape($url);

if (isset($result['error'])) {
    http_response_code($result['code'] ?? 400);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);