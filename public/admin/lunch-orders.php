<?php
declare(strict_types=1);
require __DIR__ . '/includes/cms-boot.php';
use App\Db;

$statuses = [
    'pending' => 'Novo pedido',
    'accepted' => 'Novo pedido',
    'preparing' => 'Em preparação',
    'out_for_delivery' => 'Saindo para entrega',
    'delivered' => 'Entregue',
    'cancelled' => 'Cancelado',
];
$filter = (string) ($_GET['status'] ?? '');
if ($filter !== '' && !array_key_exists($filter, $statuses)) {
    $filter = '';
}

$query = 'SELECT o.id,o.customer_name,o.customer_phone,o.neighborhood,o.quantity,
                 o.total_cents,o.payment_method,o.payment_status,o.status,o.created_at
          FROM lunch_orders o';
$params = [];
if ($filter !== '') {
    $query .= $filter === 'pending' ? " WHERE o.status IN ('pending','accepted')" : ' WHERE o.status = ?';
    if ($filter !== 'pending') {
        $params[] = $filter;
    }
}
$query .= ' ORDER BY o.id DESC LIMIT 100';
$stmt = Db::pdo()->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

adm_start('Pedidos recebidos', 'lunch-orders');
?>
<header class="mb-4">
    <span class="page-kicker">OPERAÇÃO</span>
    <h1 class="page-title">Pedidos recebidos</h1>
    <p class="page-subtitle">Visualize os pedidos e acompanhe o atendimento em tempo real.</p>
</header>
<nav class="orders-filterbar" aria-label="Filtrar pedidos">
    <?php foreach (['' => 'Todos','pending' => 'Novos','preparing' => 'Em preparação','out_for_delivery' => 'Saindo para entrega','delivered' => 'Entregues','cancelled' => 'Cancelados'] as $value => $label): ?>
    <a class="orders-filter <?=$value === $filter ? 'active' : ''?>" href="?status=<?=urlencode($value)?>"><?=adm_h($label)?></a>
    <?php endforeach; ?>
</nav>
<section class="content-card">
    <div class="content-heading">
        <div><h2>Últimos pedidos</h2><small class="text-secondary"><?=count($orders)?> pedidos nesta visualização</small></div>
        <span class="orders-live-label"><span></span> Atualização automática</span>
    </div>
    <div class="orders-list" id="admin-orders-list" data-filter="<?=adm_h($filter)?>">
      <?php if (!$orders): ?><div class="orders-list-empty">Nenhum pedido encontrado neste filtro.</div><?php endif; ?>
      <?php foreach ($orders as $order): ?>
      <a class="orders-list-item" href="/admin/lunch-order.php?id=<?=(int)$order['id']?>">
        <span class="orders-list-number">#<?=(int)$order['id']?></span>
        <span class="orders-list-customer"><strong><?=adm_h($order['customer_name'])?></strong><small><?=adm_h($order['neighborhood'])?> · <?=adm_h(date('d/m H:i', strtotime($order['created_at'])))?></small></span>
        <span class="orders-list-count"><?=(int)$order['quantity']?> <?=((int)$order['quantity'] === 1 ? 'quentinha' : 'quentinhas')?></span>
        <span class="orders-list-price">R$ <?=number_format((int)$order['total_cents'] / 100, 2, ',', '.')?></span>
        <span class="orders-state orders-state-<?=adm_h($order['status'])?>"><?=adm_h($statuses[$order['status']] ?? $order['status'])?></span>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
      </a>
      <?php endforeach; ?>
    </div>
</section>
<script src="/assets/js/admin-orders-live.js?v=1" defer></script>
<?php adm_end(); ?>
