<?php
// stream.php - 簡化版：每次請求送一次新訊息，讓 EventSource 自動重連

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// 避免 BOM 或其他輸出
while (ob_get_level() > 0) {
    ob_end_flush();
}

// 讀取最後一次收到的訊息 ID（瀏覽器會帶 Last-Event-ID）
$lastId = 0;
if (isset($_SERVER['HTTP_LAST_EVENT_ID'])) {
    $lastId = (int) $_SERVER['HTTP_LAST_EVENT_ID'];
}

$file = __DIR__ . '/messages.json';

if (!file_exists($file)) {
    echo ": no messages yet\n\n";
    flush();
    exit;
}

$content = file_get_contents($file);
$messages = json_decode($content, true);
if (!is_array($messages)) {
    $messages = [];
}

$sent = 0;

foreach ($messages as $msg) {
    if (!isset($msg['id'])) {
        continue;
    }

    if ($msg['id'] > $lastId) {
        $sent++;
        // SSE 格式
        echo "id: {$msg['id']}\n";
        echo "data: " . json_encode($msg, JSON_UNESCAPED_UNICODE) . "\n\n";
    }
}

// 如果沒有新訊息，也送一個註解當心跳，避免某些 proxy 覺得這是空回應
if ($sent === 0) {
    echo ": heartbeat " . time() . "\n\n";
}

flush();
