<?php

declare(strict_types=1);
require __DIR__.'/includes/cms-boot.php';

use App\Db;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function orderFeedReply(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

try {
    $db = Db::pdo();
    $type = (string)($_GET['type'] ?? 'orders');
    if ($type === 'orders') {
        $filter = (string)($_GET['status'] ?? '');
        $allowed = ['','pending','preparing','out_for_delivery','delivered','cancelled'];
        if (!in_array($filter, $allowed, true)) {
            $filter = '';
        }
        $sql = 'SELECT id,customer_name,neighborhood,quantity,total_cents,status,created_at FROM lunch_orders';
        $params = [];
        if ($filter === 'pending') {
            $sql .= " WHERE status IN ('pending','accepted')";
        } elseif ($filter !== '') {
            $sql .= ' WHERE status=?';
            $params[] = $filter;
        }
        $sql .= ' ORDER BY id DESC LIMIT 100';
        $q = $db->prepare($sql);
        $q->execute($params);
        orderFeedReply(200, ['ok' => true,'orders' => $q->fetchAll()]);
    }
    if ($type !== 'chat') {
        orderFeedReply(400, ['ok' => false,'error' => 'Consulta inválida.']);
    }
    $id = filter_var($_GET['order'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        orderFeedReply(400, ['ok' => false,'error' => 'Pedido inválido.']);
    }
    $q = $db->prepare('SELECT id FROM lunch_orders WHERE id=?');
    $q->execute([$id]);
    if (!$q->fetch()) {
        orderFeedReply(404, ['ok' => false,'error' => 'Pedido não encontrado.']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        cms_csrf();
        $body = trim((string)($_POST['body'] ?? ''));
        if ($body === '' || mb_strlen($body) > 2000) {
            orderFeedReply(422, ['ok' => false,'error' => 'Escreva uma mensagem de até 2.000 caracteres.']);
        }
        $q = $db->prepare('SELECT customer_id FROM lunch_orders WHERE id=?');
        $q->execute([$id]);
        $customerId = $q->fetchColumn();
        if (!$customerId) {
            orderFeedReply(422, ['ok' => false,'error' => 'Pedido sem conta vinculada.']);
        }
        $q = $db->prepare("INSERT INTO lunch_messages(order_id,customer_id,sender,body) VALUES(?,?,'restaurant',?)");
        $q->execute([$id,$customerId,$body]);
    } elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        orderFeedReply(405, ['ok' => false,'error' => 'Método não permitido.']);
    }
    $q = $db->prepare('SELECT id,sender,body,created_at FROM lunch_messages WHERE order_id=? ORDER BY id ASC LIMIT 200');
    $q->execute([$id]);
    orderFeedReply(200, ['ok' => true,'messages' => $q->fetchAll()]);
} catch (Throwable $e) {
    error_log((string)$e);
    orderFeedReply(500, ['ok' => false,'error' => 'Falha ao consultar atualizações.']);
}
