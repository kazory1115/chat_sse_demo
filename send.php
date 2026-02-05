<?php
// send.php

// 簡單防呆：只接受 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$user    = isset($_POST['user']) ? trim($_POST['user']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($user === '' || $message === '') {
    http_response_code(400);
    echo 'Bad Request';
    exit;
}

$file = __DIR__ . '/messages.json';

// 讀取既有訊息
if (!file_exists($file)) {
    file_put_contents($file, json_encode([]));
}

$fp = fopen($file, 'c+');
if (!$fp) {
    http_response_code(500);
    echo 'Cannot open messages file';
    exit;
}

// 檔案鎖，避免同時寫入
flock($fp, LOCK_EX);

// 把指標移到最前面讀取內容
rewind($fp);
$content = stream_get_contents($fp);
$messages = json_decode($content, true);
if (!is_array($messages)) {
    $messages = [];
}

// 產生新的 ID
$lastId = 0;
if (!empty($messages)) {
    $last = end($messages);
    $lastId = isset($last['id']) ? (int)$last['id'] : 0;
}

$newMsg = [
    'id'         => $lastId + 1,
    'user'       => $user,
    'message'    => $message,
    'created_at' => date('Y-m-d H:i:s'),
];

// 加到陣列裡
$messages[] = $newMsg;

// 截掉太舊的訊息，避免檔案無限長（例如只保留最後 200 則）
$maxMessages = 200;
if (count($messages) > $maxMessages) {
    $messages = array_slice($messages, -$maxMessages);
}

// 把檔案清空後重寫
ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

// 解鎖 & 關閉
flock($fp, LOCK_UN);
fclose($fp);

// 回傳成功
header('Content-Type: application/json; charset=utf-8');
sleep(5);
echo json_encode(['status' => 'ok']);
