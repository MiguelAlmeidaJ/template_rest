<?php
declare(strict_types=1);
require __DIR__ . '/includes/cms-boot.php';
use App\Db;

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /admin/lunch-orders.php');
    exit;
}
$db = Db::pdo();
$statuses = [
    'pending' => 'Novo pedido','accepted' => 'Novo pedido','preparing' => 'Em preparação',
    'out_for_delivery' => 'Saindo para entrega','delivered' => 'Pedido entregue','cancelled' => 'Cancelado',
];
$error = '';
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_csrf();
    try {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'status') {
            $status = (string) ($_POST['status'] ?? '');
            if (!in_array($status, ['pending','preparing','out_for_delivery','delivered','cancelled'], true)) {
                throw new DomainException('Status inválido.');
            }
            $stmt = $db->prepare('UPDATE lunch_orders SET status=? WHERE id=?');
            $stmt->execute([$status,$id]);
            $notice = 'Etapa do pedido atualizada.';
        } elseif ($action === 'paid') {
            $stmt = $db->prepare("UPDATE lunch_orders SET payment_status='paid' WHERE id=? AND status<>'cancelled'");
            $stmt->execute([$id]);
            $notice = 'Pagamento confirmado.';
        } else {
            throw new DomainException('Ação inválida.');
        }
    } catch (DomainException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log((string)$exception);
        $error = 'Não foi possível atualizar o pedido.';
    }
}
$stmt = $db->prepare('SELECT * FROM lunch_orders WHERE id=?');
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) {
    http_response_code(404);
    exit('Pedido não encontrado.');
}
$stmt = $db->prepare('SELECT b.id,b.box_number,b.box_name,c.item_name FROM lunch_order_boxes b LEFT JOIN lunch_order_choices c ON c.box_id=b.id WHERE b.order_id=? ORDER BY b.box_number,c.id');
$stmt->execute([$id]);
// Opening the order counts as reading its incoming-order notification.
\App\AdminNotification::markReferenceRead('order', $id);
$boxes = [];
foreach ($stmt->fetchAll() as $row) {
    $boxNumber = (int)$row['box_number'];
    $boxes[$boxNumber]['name'] = $row['box_name'];
    if ($row['item_name'] !== null) {
        $boxes[$boxNumber]['items'][] = $row['item_name'];
    }
}
adm_start('Pedido #'.$id, 'lunch-orders');
?>
<div class="order-detail-top">
  <a href="/admin/lunch-orders.php" class="order-back"><i class="bi bi-arrow-left"></i> Voltar aos pedidos</a>
  <div class="order-detail-title"><div><span class="page-kicker">DETALHES DO PEDIDO</span><h1 class="page-title">Pedido #<?=(int)$id?></h1><p class="page-subtitle">Registrado em <?=adm_h(date('d/m/Y às H:i', strtotime($order['created_at'])))?></p></div><span class="orders-state orders-state-<?=adm_h($order['status'])?>"><?=adm_h($statuses[$order['status']] ?? $order['status'])?></span></div>
</div>
<?php if ($notice): ?><div class="alert alert-success"><?=adm_h($notice)?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?=adm_h($error)?></div><?php endif; ?>
<div class="order-detail-layout">
  <div class="order-detail-primary">
    <section class="content-card order-detail-card">
      <h2><i class="bi bi-basket"></i> Montagem das quentinhas</h2>
      <?php foreach ($boxes as $number => $box): ?>
      <article class="order-detail-box"><div class="order-detail-box-title"><strong>Quentinha <?=(int)$number?></strong><span><?=adm_h($box['name'])?></span></div><p><?=adm_h(implode(' · ', $box['items'] ?? []))?></p></article>
      <?php endforeach; ?>
    </section>
    <section class="content-card order-detail-card">
      <h2><i class="bi bi-geo-alt"></i> Informações para entrega</h2>
      <div class="order-detail-facts">
        <div><small>Cliente</small><strong><?=adm_h($order['customer_name'])?></strong></div>
        <div><small>WhatsApp</small><strong><?=adm_h($order['customer_phone'])?></strong></div>
        <div class="wide"><small>Endereço</small><strong><?=adm_h($order['address'])?> · <?=adm_h($order['neighborhood'])?></strong></div>
        <div class="wide"><small>Ponto de referência</small><strong><?=adm_h($order['reference_point'])?></strong></div>
      </div>
    </section>
    <section class="content-card order-detail-card" id="order-chat-section">
      <div class="order-detail-chat-heading"><h2><i class="bi bi-chat-dots"></i> Conversa com o cliente</h2><span id="order-chat-live" class="orders-live-label">Atualização automática</span></div>
      <?php if ($order['customer_id']): ?>
        <div id="order-chat-messages" class="order-detail-messages" data-order-id="<?=(int)$id?>"></div>
        <form id="order-chat-form" class="order-chat-form">
          <input type="hidden" name="csrf" value="<?=adm_h($csrf)?>">
          <input type="hidden" name="order_id" value="<?=(int)$id?>">
          <label for="order-chat-body">Escrever resposta</label>
          <textarea id="order-chat-body" name="body" maxlength="2000" rows="3" required placeholder="Digite sua mensagem..."></textarea>
          <div><span id="order-chat-feedback" role="status"></span><button type="submit" class="btn btn-bia">Enviar mensagem <i class="bi bi-arrow-right"></i></button></div>
        </form>
      <?php else: ?><p class="text-secondary">Este pedido não está associado a uma conta de cliente.</p><?php endif; ?>
    </section>
  </div>
  <aside class="order-detail-aside">
    <section class="content-card order-detail-card">
      <h2>Andamento do pedido</h2>
      <form method="post" class="order-detail-status-form">
        <input type="hidden" name="csrf" value="<?=adm_h($csrf)?>">
        <input type="hidden" name="action" value="status">
        <label for="order-status">Situação atual</label>
        <select id="order-status" class="form-select" name="status">
          <?php foreach ($statuses as $value => $label): ?>
          <?php if ($value === 'accepted') {
              continue;
          } ?>
          <option value="<?=adm_h($value)?>" <?=($order['status'] === $value || ($value === 'pending' && $order['status'] === 'accepted')) ? 'selected' : ''?>><?=adm_h($label)?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-bia">Atualizar status</button>
      </form>
    </section>
    <section class="content-card order-detail-card">
      <h2>Pagamento</h2>
      <div class="order-detail-facts"><div><small>Forma</small><strong><?=$order['payment_method'] === 'pix' ? 'Pix' : 'Cartão na entrega'?></strong></div><div><small>Situação</small><strong><?=$order['payment_status'] === 'paid' ? 'Pago' : 'Pendente'?></strong></div><div><small>Quantidade</small><strong><?=(int)$order['quantity']?></strong></div><div><small>Valor total</small><strong class="order-total">R$ <?=number_format((int)$order['total_cents'] / 100, 2, ',', '.')?></strong></div></div>
      <?php if ($order['payment_status'] !== 'paid' && $order['status'] !== 'cancelled'): ?>
      <form method="post" class="mt-3" onsubmit="return confirm('O pagamento foi realmente recebido?')"><input type="hidden" name="csrf" value="<?=adm_h($csrf)?>"><input type="hidden" name="action" value="paid"><button type="submit" class="btn btn-outline-secondary w-100">Confirmar recebimento</button></form>
      <?php endif; ?>
    </section>
  </aside>
</div>
<?php if ($order['customer_id']): ?><script src="/assets/js/admin-order-chat.js?v=1" defer></script><?php endif; ?>
<?php adm_end(); ?>
