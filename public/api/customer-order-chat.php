<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\Customer;
use App\Db;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function respond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

try {
    Customer::boot();
    $customerId = Customer::id();
    if (!$customerId) {
        respond(401, ['ok' => false, 'error' => 'Entre na sua conta.']);
    }

    $orderId = filter_var($_GET['order'] ?? null, FILTER_VALIDATE_INT);
    if (!$orderId) {
        respond(400, ['ok' => false, 'error' => 'Pedido inválido.']);
    }

    $db = Db::pdo();
    $query = $db->prepare('SELECT id, status FROM lunch_orders WHERE id = ? AND customer_id = ?');
    $query->execute([$orderId, $customerId]);
    $order = $query->fetch();
    if (!$order) {
        respond(404, ['ok' => false, 'error' => 'Pedido não encontrado.']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data) || !hash_equals(Customer::csrf(), (string) ($data['csrf'] ?? ''))) {
            respond(403, ['ok' => false, 'error' => 'Sessão expirada. Atualize a página.']);
        }
        $body = trim((string) ($data['body'] ?? ''));
        if ($body === '' || mb_strlen($body) > 2000) {
            respond(422, ['ok' => false, 'error' => 'Escreva uma mensagem de até 2.000 caracteres.']);
        }
        $query = $db->prepare("INSERT INTO lunch_messages(order_id,customer_id,sender,body) VALUES(?,?,'customer',?)");
        $query->execute([$orderId, $customerId, $body]);
        \App\AdminNotification::create('chat', $orderId, 'Nova mensagem do cliente', 'Pedido #'.$orderId.' recebeu uma mensagem', '/admin/lunch-order.php?id='.$orderId);
    } elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(405, ['ok' => false, 'error' => 'Método não permitido.']);
    }

    $query = $db->prepare('SELECT id,sender,body,created_at FROM lunch_messages WHERE order_id=? AND customer_id=? ORDER BY id ASC LIMIT 200');
    $query->execute([$orderId, $customerId]);

    respond(200, ['ok' => true, 'status' => $order['status'], 'messages' => $query->fetchAll()]);
} catch (\JsonException $exception) {
    respond(400, ['ok' => false, 'error' => 'Dados inválidos.']);
} catch (\Throwable $exception) {
    error_log((string) $exception);
    respond(500, ['ok' => false, 'error' => 'Não foi possível atualizar a conversa.']);
}
