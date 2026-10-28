<?php

declare(strict_types=1);
require __DIR__.'/includes/cms-boot.php';
use App\Db;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
$counts = ['messages' => 0,'orders' => 0,'reservations' => 0];
try {
    $db = Db::pdo();
    $counts['orders'] = \App\AdminNotification::unreadCount();
    $counts['reservations'] = 0;
    $counts['messages'] = (int)$db->query("SELECT COUNT(*) FROM lunch_messages m WHERE m.sender='customer' AND NOT EXISTS (SELECT 1 FROM lunch_messages r WHERE r.order_id=m.order_id AND r.sender='restaurant' AND r.id>m.id)")->fetchColumn();
    echo json_encode(['ok' => true,'counts' => $counts], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log((string)$e);
    http_response_code(503);
    echo json_encode(['ok' => false]);
}
