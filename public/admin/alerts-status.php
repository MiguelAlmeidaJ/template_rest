<?php

declare(strict_types=1);
require __DIR__.'/includes/cms-boot.php';
use App\Db;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
$counts = ['messages' => 0, 'orders' => 0];
try {
    $db = Db::pdo();
    $counts['orders'] = \App\AdminNotification::unreadCountExcludingChat();
    $counts['messages'] = \App\AdminNotification::unreadCountByType('chat');
    echo json_encode(['ok' => true,'counts' => $counts], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log((string)$e);
    http_response_code(503);
    echo json_encode(['ok' => false]);
}
