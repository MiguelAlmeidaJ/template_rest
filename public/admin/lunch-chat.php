<?php
declare(strict_types=1);
require __DIR__.'/includes/cms-boot.php';
use App\Db;

$id = filter_var($_GET['order'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /admin/lunch-orders.php');
    exit;
}
$q = Db::pdo()->prepare('SELECT id,customer_name,customer_id FROM lunch_orders WHERE id=?');
$q->execute([$id]);
$order = $q->fetch();
if (!$order) {
    http_response_code(404);
    exit('Pedido não encontrado');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_csrf();
    $body = trim((string)($_POST['body'] ?? ''));
    if (!$order['customer_id']) {
        cms_notice('Este pedido não pertence a uma conta cadastrada.', 'danger');
    } elseif ($body === '' || mb_strlen($body) > 2000) {
        cms_notice('Mensagem inválida', 'danger');
    } else {
        $q = Db::pdo()->prepare("INSERT INTO lunch_messages(order_id,customer_id,sender,body) VALUES(?,?,'restaurant',?)");
        $q->execute([$id,$order['customer_id'],$body]);
        cms_notice('Resposta enviada.');
    }
}
$q = Db::pdo()->prepare('SELECT sender,body,created_at FROM lunch_messages WHERE order_id=? ORDER BY id ASC LIMIT 150');
$q->execute([$id]);
$messages = $q->fetchAll();
adm_start('Conversa do pedido', 'lunch-orders');
cms_feedback();?><h1 class="page-title">Pedido #<?=adm_h($id)?> — <?=adm_h($order['customer_name'])?></h1><p><a href="/admin/lunch-orders.php">Voltar aos pedidos</a></p><section class="content-card p-4"><h2 class="h5">Conversa com o cliente</h2><?php if (!$order['customer_id']):?><p>Pedido realizado sem login de cliente.</p><?php endif;?><?php foreach ($messages as $message):?><div class="border rounded p-3 mb-2"><strong><?=$message['sender'] === 'customer' ? 'Cliente' : 'Restaurante'?></strong><p class="mb-1"><?=nl2br(adm_h($message['body']))?></p><small><?=adm_h($message['created_at'])?></small></div><?php endforeach;?><?php if ($order['customer_id']):?><form method="post"><input type="hidden" name="csrf" value="<?=adm_h($csrf)?>"><label class="form-label">Resposta ao cliente</label><textarea class="form-control mb-3" name="body" maxlength="2000" rows="3" required></textarea><button class="btn btn-bia">Enviar resposta</button></form><?php endif;?></section><?php adm_end();
