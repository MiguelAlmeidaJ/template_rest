<?php
declare(strict_types=1);

require __DIR__ . '/includes/cms-boot.php';

use App\Pos;
use App\Staff;

// Financial history is available only to restaurant management.
Staff::requireManagement();

$day = (string) ($_GET['day'] ?? date('Y-m-d'));
try {
    $data = Pos::financial($day);
} catch (DomainException $e) {
    $day = date('Y-m-d');
    $data = Pos::financial($day);
}

$amount = static fn (mixed $cents): string => 'R$ '.number_format(((int) $cents) / 100, 2, ',', '.');

adm_start('Relatório financeiro · PDV', 'pdv');
?>
<header class="mb-4">
  <a href="/admin/pdv.php" class="text-decoration-none" style="color:#2d6835"><i class="bi bi-arrow-left"></i> Voltar ao PDV</a>
  <span class="page-kicker d-block mt-4">RELATÓRIO FINANCEIRO</span>
  <h1 class="page-title">Movimentação do restaurante</h1>
  <p class="page-subtitle">Vendas presenciais registradas e pedidos online com pagamento confirmado.</p>
</header>

<section class="content-card p-4 mb-4">
  <form method="get" class="d-flex align-items-end flex-wrap gap-3">
    <label class="form-label" for="report-day">Data do relatório
      <input id="report-day" class="form-control mt-2" type="date" name="day" value="<?=adm_h($day)?>" required>
    </label>
    <button type="submit" class="btn btn-bia mb-1">Consultar</button>
  </form>
</section>

<div class="row g-3 mb-4">
<?php foreach ([
  ['Vendas presenciais', $amount($data['local']['total_cents']), (int) $data['local']['sales_count'].' vendas'],
  ['Pedidos online pagos', $amount($data['online']['received_cents']), (int) $data['online']['orders_count'].' pedidos'],
  ['Total recebido', $amount($data['grand_total_cents']), 'Recebimentos do dia'],
] as [$label,$value,$extra]): ?>
  <div class="col-12 col-md-4">
    <div class="metric-card h-100">
      <div class="metric-label"><?=adm_h($label)?></div>
      <div class="metric-value"><?=adm_h($value)?></div>
      <small class="text-secondary"><?=adm_h($extra)?></small>
    </div>
  </div>
<?php endforeach; ?>
</div>

<section class="content-card mb-4">
  <div class="content-heading"><h2>Vendas presenciais por forma de pagamento</h2></div>
  <div class="row g-3 p-4">
    <?php foreach ([
        ['Dinheiro', $data['local']['cash_cents'], 'bi-cash'],
        ['Pix', $data['local']['pix_cents'], 'bi-qr-code'],
        ['Cartão', $data['local']['card_cents'], 'bi-credit-card'],
    ] as [$label,$cents,$icon]): ?>
      <div class="col-12 col-sm-4"><div class="border rounded-3 p-3 h-100">
        <p class="mb-2 text-secondary small"><i class="bi <?=adm_h($icon)?> me-2"></i><?=adm_h($label)?></p>
        <strong><?=adm_h($amount($cents))?></strong>
      </div></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="content-card">
  <div class="content-heading"><div><h2>Vendas realizadas no local</h2><small class="text-secondary">Últimos 60 registros da data selecionada</small></div></div>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>Venda</th><th>Descrição</th><th>Unidades</th><th>Pagamento</th><th>Total</th><th>Horário</th></tr></thead>
      <tbody>
      <?php foreach ($data['sales'] as $sale): ?>
        <tr>
          <td>#<?=(int)$sale['id']?></td>
          <td>
            <strong><?=adm_h($sale['description'])?></strong>
            <?php if (!empty($sale['items'])): ?>
              <div class="mt-1 text-secondary small">
                <?php foreach ($sale['items'] as $item): ?>
                  <div><?= (int) $item['quantity'] ?> × <?=adm_h($item['description'])?>
                    <span class="text-nowrap">(<?=adm_h($amount($item['unit_price_cents']))?> un. · <?=adm_h($amount($item['total_cents']))?>)</span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <small class="d-block text-secondary">Registro anterior, sem itens separados</small>
            <?php endif; ?>
          </td>
          <td><?=(int)$sale['quantity']?></td>
          <td><?=adm_h(['cash' => 'Dinheiro','pix' => 'Pix','card' => 'Cartão'][$sale['payment_method']] ?? $sale['payment_method'])?></td>
          <td><?=adm_h($amount($sale['total_cents']))?></td>
          <td><?=adm_h(date('H:i', strtotime($sale['created_at'])))?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$data['sales']): ?><tr><td colspan="6" class="text-center py-4 text-secondary">Nenhuma venda presencial registrada nessa data.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php adm_end(); ?>
