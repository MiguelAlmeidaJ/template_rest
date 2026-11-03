<?php

declare(strict_types=1);

require __DIR__ . '/includes/cms-boot.php';

use App\Db;
use App\Pos;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function reply(int $code, array $payload): never
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['type'] ?? '') === 'chat') {
        $orderId = filter_var($_GET['order_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$orderId) throw new DomainException('Pedido inválido.');
        $customer = Db::pdo()->prepare('SELECT customer_id FROM lunch_orders WHERE id=?');
        $customer->execute([$orderId]);
        if (!$customer->fetchColumn()) throw new DomainException('Este pedido não possui chat cadastrado.');
        $messages = Db::pdo()->prepare('SELECT id,sender,body,created_at FROM lunch_messages WHERE order_id=? ORDER BY id ASC LIMIT 200');
        $messages->execute([$orderId]);
        \App\AdminNotification::markReferenceRead('chat', $orderId);
        reply(200, ['ok' => true, 'messages' => $messages->fetchAll()]);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        reply(200, ['ok' => true, 'orders' => Pos::board(), 'finance' => Pos::financial()]);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        reply(405, ['ok' => false, 'error' => 'Método não permitido.']);
    }
    // cms_csrf handles the authenticated staff session.
    cms_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'chat_send') {
        $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
        $body = trim((string) ($_POST['body'] ?? ''));
        if (!$orderId || $body === '' || mb_strlen($body) > 2000) {
            throw new DomainException('Digite uma mensagem válida de até 2.000 caracteres.');
        }
        $query = Db::pdo()->prepare('SELECT customer_id FROM lunch_orders WHERE id=?');
        $query->execute([$orderId]);
        $customerId = $query->fetchColumn();
        if (!$customerId) throw new DomainException('Pedido sem conta de cliente vinculada.');
        $insert = Db::pdo()->prepare(
            "INSERT INTO lunch_messages(order_id,customer_id,sender,body) VALUES(?,?,'restaurant',?)"
        );
        $insert->execute([$orderId, $customerId, $body]);
        reply(201, ['ok' => true, 'message' => 'Mensagem enviada.']);
    }
    if ($action === 'sale') {
        $id = Pos::sale((int) $staff['id'], $_POST);
        reply(201, ['ok' => true, 'message' => 'Venda presencial #'.$id.' registrada.']);
    }
    if ($action === 'next') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            throw new DomainException('Pedido inválido.');
        }
        Pos::move($id, (string) ($_POST['next'] ?? ''));
        reply(200, ['ok' => true, 'message' => 'Etapa atualizada.']);
    }
    if ($action === 'paid') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            throw new DomainException('Pedido inválido.');
        }
        $q = Db::pdo()->prepare(
            "UPDATE lunch_orders SET payment_status='paid',paid_at=NOW()
             WHERE id=? AND status<>'cancelled' AND payment_status='pending'"
        );
        $q->execute([$id]);
        if (!$q->rowCount()) {
            throw new DomainException('Pagamento já confirmado ou pedido indisponível.');
        }
        reply(200, ['ok' => true, 'message' => 'Pagamento confirmado.']);
    }
    throw new DomainException('Operação não permitida.');
} catch (DomainException $e) {
    reply(422, ['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log((string) $e);
    reply(500, ['ok' => false, 'error' => 'Não foi possível processar a solicitação.']);
}
