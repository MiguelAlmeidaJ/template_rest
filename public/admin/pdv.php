<?php
declare(strict_types=1);

require __DIR__ . '/includes/cms-boot.php';

use App\Cms;
use App\Pos;

$logo = Cms::setting('logo');
$favicon = Cms::setting('favicon');
$first = Pos::board();
$finance = Pos::financial();
$isEmployee = $staff['role'] === 'employee';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#2d6835">
  <title>Central PDV | Restaurante da Bia</title>
  <?php if ($favicon): ?><link rel="icon" href="<?=adm_h($favicon)?>"><?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/pos.css?v=3">
</head>
<body class="pos-body">
<header class="pos-header">
  <div class="pos-brand">
    <?php if ($logo): ?><img src="<?=adm_h($logo)?>" alt="Restaurante da Bia"><?php else: ?><strong>Restaurante da Bia</strong><?php endif; ?>
    <span class="pos-header-divider"></span>
    <div><span class="pos-header-eyebrow">OPERAÇÃO EM TEMPO REAL</span><strong>Central de pedidos</strong></div>
  </div>
  <div class="pos-header-actions">
    <span class="pos-live" id="pos-connection"><span class="pos-live-dot"></span> Conectado</span>
    <?php if (!$isEmployee): ?><a class="pos-return" href="/admin/pos-report.php"><i class="bi bi-bar-chart"></i> Relatórios</a><a class="pos-return" href="/admin/"><i class="bi bi-arrow-left"></i> Gestão</a><?php endif; ?>
    <form action="/admin/logout.php" method="post">
      <input type="hidden" name="csrf" value="<?=adm_h($csrf)?>">
      <button class="pos-logout" type="submit" title="Sair da conta"><i class="bi bi-box-arrow-right"></i><span>Sair</span></button>
    </form>
  </div>
</header>
<main class="pos-main">
  <div class="pos-heading">
    <div>
      <span class="pos-eyebrow">VISÃO DO ATENDIMENTO</span>
      <h1>Controle de pedidos</h1>
      <p>Acompanhe os pedidos e registre as vendas feitas no restaurante.</p>
    </div>
    <button type="button" id="pos-new-sale" class="pos-primary"><i class="bi bi-plus-circle"></i> Nova venda presencial</button>
  </div>
  <div class="pos-overview">
    <div class="pos-metric"><span>Novos pedidos</span><strong id="pos-count-new">0</strong><small>Aguardando preparação</small></div>
    <div class="pos-metric"><span>Em preparação</span><strong id="pos-count-preparing">0</strong><small>Na cozinha</small></div>
    <div class="pos-metric"><span>Saindo para entrega</span><strong id="pos-count-delivering">0</strong><small>A caminho do cliente</small></div>
    <div class="pos-metric pos-metric-finance"><span>Recebido hoje</span><strong id="pos-finance-total">R$ <?=number_format($finance['grand_total_cents'] / 100, 2, ',', '.')?></strong><small>Presenciais + pedidos pagos</small></div>
  </div>
  <div class="pos-section-heading">
    <div><h2>Fluxo de pedidos</h2><p>Os cartões mudam de coluna quando a etapa é atualizada.</p></div>
    <div class="pos-refresh"><i class="bi bi-arrow-repeat"></i><span id="pos-last-refresh">Atualização automática</span></div>
  </div>
  <div class="pos-board" id="pos-board" aria-label="Quadro de pedidos">
    <section class="pos-lane pos-lane-new"><header><span><i class="bi bi-bell"></i> Novos pedidos</span><b id="pos-lane-count-new">0</b></header><div class="pos-cards" id="pos-lane-new"></div></section>
    <section class="pos-lane pos-lane-preparing"><header><span><i class="bi bi-fire"></i> Em preparação</span><b id="pos-lane-count-preparing">0</b></header><div class="pos-cards" id="pos-lane-preparing"></div></section>
    <section class="pos-lane pos-lane-delivering"><header><span><i class="bi bi-truck"></i> Saindo para entrega</span><b id="pos-lane-count-delivering">0</b></header><div class="pos-cards" id="pos-lane-delivering"></div></section>
  </div>
  <section class="pos-finance">
    <div class="pos-section-heading">
      <div><h2>Movimentação de hoje</h2><p>Valores recebidos, sem incluir pedidos online ainda pendentes.</p></div>
      <span class="pos-today"><?=adm_h(date('d/m/Y'))?></span>
    </div>
    <div class="pos-finance-grid">
      <div><span>Vendas presenciais</span><strong id="pos-sales-local">R$ <?=number_format((int)$finance['local']['total_cents'] / 100, 2, ',', '.')?></strong><small id="pos-sales-count">0 vendas</small></div>
      <div><span>Pedidos online pagos</span><strong id="pos-sales-online">R$ <?=number_format((int)$finance['online']['received_cents'] / 100, 2, ',', '.')?></strong></div>
      <div><span>Total recebido</span><strong id="pos-sales-total">R$ <?=number_format($finance['grand_total_cents'] / 100, 2, ',', '.')?></strong></div>
    </div>
    <details class="pos-sales-history">
      <summary>Ver vendas presenciais registradas hoje <i class="bi bi-chevron-down"></i></summary>
      <div id="pos-sale-history" class="pos-sales-rows"></div>
    </details>
  </section>
</main>

<dialog id="pos-sale-dialog" class="pos-dialog" aria-labelledby="pos-sale-title">
  <div class="pos-dialog-head"><div><span class="pos-eyebrow">VENDA NO BALCÃO</span><h2 id="pos-sale-title">Registrar venda presencial</h2><p>Registre o valor efetivamente recebido no restaurante.</p></div><button type="button" class="pos-icon-close" data-close-dialog aria-label="Fechar"><i class="bi bi-x-lg"></i></button></div>
  <form id="pos-sale-form">
    <input type="hidden" name="csrf" value="<?=adm_h($csrf)?>">
    <input type="hidden" name="action" value="sale">
    <div class="pos-items-heading">
      <div><strong>Itens da venda</strong><small>Inclua refeições, bebidas e outros produtos com preços diferentes.</small></div>
      <button type="button" id="pos-add-item" class="pos-add-item"><i class="bi bi-plus-lg"></i> Adicionar item</button>
    </div>
    <div id="pos-sale-items" class="pos-sale-items"></div>
    <label for="pos-payment">Pagamento recebido<select id="pos-payment" name="payment_method" required><option value="">Selecione a forma de pagamento</option><option value="cash">Dinheiro</option><option value="pix">Pix</option><option value="card">Cartão</option></select></label>
    <div class="pos-sale-total"><span><strong>Total da venda</strong><small id="pos-sale-items-count">0 itens</small></span><strong id="pos-sale-total">R$ 0,00</strong></div>
    <p id="pos-sale-error" class="pos-form-error" role="alert"></p>
    <div class="pos-dialog-actions"><button type="button" class="pos-secondary" data-close-dialog>Cancelar</button><button class="pos-primary" id="pos-sale-submit" type="submit">Registrar venda <i class="bi bi-check2"></i></button></div>
  </form>
</dialog>

<dialog id="pos-order-dialog" class="pos-dialog pos-order-dialog" aria-labelledby="pos-order-title">
  <div class="pos-dialog-head"><div><span class="pos-eyebrow">PEDIDO ONLINE</span><h2 id="pos-order-title">Detalhes do pedido</h2><p id="pos-order-subtitle"></p></div><button type="button" class="pos-icon-close" data-close-dialog aria-label="Fechar"><i class="bi bi-x-lg"></i></button></div>
  <div class="pos-order-dialog-content" id="pos-order-content"></div>
<section class="pos-chat-panel" id="pos-chat-panel" hidden>
<header><strong><i class="bi bi-chat-dots"></i> Conversa com o cliente</strong><span id="pos-chat-indicator">Atualização automática</span></header>
<div class="pos-chat-messages" id="pos-chat-messages" role="log" aria-live="polite"></div>
<form id="pos-chat-form">
<label for="pos-chat-body">Sua resposta</label>
<textarea id="pos-chat-body" rows="3" maxlength="2000" placeholder="Digite uma mensagem..." required></textarea>
<div class="pos-chat-actions"><span id="pos-chat-feedback" role="status"></span><button type="submit" class="pos-primary" id="pos-chat-submit"><i class="bi bi-send"></i> Enviar</button></div>
</form>
</section>
<p id="pos-chat-unavailable" class="pos-chat-unavailable" hidden>Este pedido não possui conta vinculada para conversar pelo chat.</p>
  <div class="pos-dialog-actions pos-order-actions"><a id="pos-order-whatsapp" class="pos-whatsapp-link" target="_blank" rel="noopener noreferrer" hidden><i class="bi bi-whatsapp"></i> Conversar no WhatsApp</a><button type="button" class="pos-secondary" data-close-dialog>Fechar</button><button type="button" class="pos-primary" id="pos-order-next">Avançar etapa <i class="bi bi-arrow-right"></i></button></div>
</dialog>

<div class="pos-toast" id="pos-toast" role="status" aria-live="polite" hidden></div>
<script type="application/json" id="pos-initial"><?=json_encode(['orders' => $first,'finance' => $finance,'csrf' => $csrf], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)?></script>
<script src="/assets/js/pos.js?v=3" defer></script>
<script src="/assets/js/pos-chat.js?v=1" defer></script>
</body>
</html>
