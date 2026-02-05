<?php
// time.php
header('Content-Type: application/json; charset=UTF-8');

sleep(5);

echo json_encode([
    'timestamp' => time(),
    'iso' => date('c'),
], JSON_UNESCAPED_UNICODE);
