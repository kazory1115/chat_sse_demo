<?php
// clear.php - 清空聊天訊息

header('Content-Type: application/json; charset=utf-8');

$file = __DIR__ . '/messages.json';

try {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_UNESCAPED_UNICODE));
    } else {
        $fp = fopen($file, 'c+');
        if ($fp === false) {
            throw new RuntimeException('open_failed');
        }
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            throw new RuntimeException('lock_failed');
        }
        ftruncate($fp, 0);
        fwrite($fp, json_encode([], JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
