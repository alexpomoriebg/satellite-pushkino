<?php
/**
 * Прокси чат-виджета → бот на VPS. Same-origin (443), скрывает адрес/секрет бота от клиента.
 * Виджет шлёт POST {sid, message}. Мы добавляем секрет+бренд, ходим на бот, отдаём {reply}.
 * BRAND: 'steklotrade' на steklotrade.com; 'stekloltd' на stekloltd.ru (менять при заливке на другой сайт).
 * Rate-limit по IP (экономия токенов бота — API по ключу). PHP 5.6-safe.
 */
define('BRAND', 'steklotrade');
define('BOT_URL', 'https://bot.steklotrade.com:8444/chat');
define('RL_MAX', 30);    // сообщений
define('RL_WIN', 600);   // за 10 минут на IP
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{"error":"method"}'; exit; }

$cfg = @include(dirname(__FILE__) . '/chat-config.php');
$SECRET = (is_array($cfg) && !empty($cfg['chat_secret'])) ? $cfg['chat_secret'] : '';

// rate-limit: файлы в вебруте (на Timeweb /tmp у каждого бэкенда свой — ненадёжно; дом заперт open_basedir)
$dir = dirname(__FILE__) . '/.chatrl';
if (!is_dir($dir)) { @mkdir($dir, 0700); }
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0';
$rf = $dir . '/' . md5($ip);
$now = time(); $t = array();
if (is_file($rf)) { foreach (explode(',', (string)@file_get_contents($rf)) as $x) { $x = (int)$x; if ($x > $now - RL_WIN) { $t[] = $x; } } }
if (count($t) >= RL_MAX) { http_response_code(429); echo json_encode(array('reply' => 'Слишком много сообщений подряд — немного подождите или позвоните нам: +7 (495) 585-47-20.'), JSON_UNESCAPED_UNICODE); exit; }
$t[] = $now; @file_put_contents($rf, implode(',', $t), LOCK_EX);

$in = json_decode(file_get_contents('php://input'), true);
$msg = (is_array($in) && isset($in['message']) && is_string($in['message'])) ? substr(trim($in['message']), 0, 2000) : '';
$sid = (is_array($in) && isset($in['sid']) && is_string($in['sid'])) ? substr(preg_replace('/[^a-zA-Z0-9_-]/', '', $in['sid']), 0, 64) : '';
if ($msg === '') { http_response_code(400); echo '{"error":"empty"}'; exit; }
if ($SECRET === '') { http_response_code(500); echo json_encode(array('reply' => 'Чат временно недоступен. Напишите на info@steklotrade.com.'), JSON_UNESCAPED_UNICODE); exit; }

$payload = json_encode(array('secret' => $SECRET, 'brand' => BRAND, 'sid' => $sid, 'message' => $msg), JSON_UNESCAPED_UNICODE);
$ch = curl_init(BOT_URL);
curl_setopt_array($ch, array(
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
));
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($resp === false || $code !== 200) {
    http_response_code(502);
    echo json_encode(array('reply' => 'Извините, чат сейчас недоступен. Напишите на info@steklotrade.com или позвоните +7 (495) 585-47-20 — ответим быстро.'), JSON_UNESCAPED_UNICODE);
    exit;
}
echo $resp; // {"reply":"..."} — как есть от бота
