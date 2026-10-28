<?php
declare(strict_types=1);
require dirname(__DIR__, 2).'/src/bootstrap.php';
require __DIR__.'/includes/layout.php';
session_name('raiz_admin');
session_set_cookie_params(['httponly' => true,'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','samesite' => 'Lax']);
session_start();
$token = $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($token, (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Solicitação inválida');
    }
    if (isset($_POST['logout'])) {
        $_SESSION = [];
        session_destroy();
        header('Location: /admin/');
        exit;
    }
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $q = \App\Db::pdo()->prepare('SELECT id,password_hash FROM admins WHERE email=? LIMIT 1');
    $q->execute([$email]);
    $u = $q->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$u['id'];
        header('Location: /admin/');
        exit;
    }
    $error = 'E-mail ou senha incorretos.';
}
if (isset($_SESSION['admin_id'])) {
    $staff = \App\Staff::current();
    if (!$staff) {
        $_SESSION = [];
        session_destroy();
    } elseif (!\App\Staff::allowed('index.php', $staff['role'])) {
        http_response_code(403);
        exit('Sem permissão');
    }
}
if (!isset($_SESSION['admin_id'])) {
    adm_start('Entrar', '', true);?>
<div class="auth-art"><a class="auth-brand text-white text-decoration-none" href="/"><span class="brand-icon">B.</span> Restaurante da Bia</a><div><span class="text-uppercase" style="letter-spacing:.2em">Gestão simplificada</span><h1 class="mt-3">Tudo para cuidar do seu restaurante.</h1><p>Acompanhe pedidos, reservas e recebimentos em um único lugar.</p></div><small>Área restrita · Restaurante da Bia</small></div><div class="auth-panel"><div class="auth-box"><span class="page-kicker">ÁREA ADMINISTRATIVA</span><h2 class="mt-2 mb-2">Bem-vindo de volta.</h2><p class="auth-caption mb-4">Entre com suas credenciais para acessar o painel.</p><?php if ($error):?><div class="alert alert-danger" role="alert"><?=adm_h($error)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=adm_h($token)?>"><div class="mb-3"><label class="form-label" for="email">E-mail</label><input class="form-control" type="email" id="email" name="email" autocomplete="username" required autofocus></div><div class="mb-4"><label class="form-label" for="password">Senha</label><input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required></div><button class="btn btn-bia w-100" type="submit">Acessar painel <i class="bi bi-arrow-right ms-2"></i></button></form><a class="d-block mt-4 small text-decoration-none text-secondary" href="/">← Voltar ao site</a></div></div><?php adm_end(true);
    exit;
}
$db = \App\Db::pdo();
$stats = ['orders' => (int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),'reservations' => (int)$db->query('SELECT COUNT(*) FROM reservations')->fetchColumn(),'revenue' => (int)$db->query("SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE payment_status='paid'")->fetchColumn(),'pending' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE payment_status='pending'")->fetchColumn()];
$orders = $db->query('SELECT id,customer_name,fulfillment,total_cents,status,payment_status,created_at FROM orders ORDER BY id DESC LIMIT 20')->fetchAll();
$reservations = $db->query('SELECT r.id,r.customer_name,r.guests,r.status,s.date,s.time FROM reservations r JOIN reservation_slots s ON r.slot_id=s.id ORDER BY r.id DESC LIMIT 20')->fetchAll();
adm_start('Visão geral');?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4"><div><span class="page-kicker">PAINEL ADMINISTRATIVO</span><h1 class="page-title">Visão geral</h1><p class="page-subtitle mb-0">Acompanhe a movimentação do restaurante.</p></div><form method="post"><input type="hidden" name="csrf" value="<?=adm_h($token)?>"><button name="logout" value="1" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-right me-2"></i>Sair</button></form></div>
<div class="row g-3 mb-4">
<?php foreach ([['Pedidos registrados',$stats['orders'],'bag-check'],['Reservas recebidas',$stats['reservations'],'calendar2-check'],['Receita confirmada','R$ '.number_format($stats['revenue'] / 100, 2, ',', '.'),'cash-stack'],['Pagamentos pendentes',$stats['pending'],'clock-history']] as [$label,$value,$icon]):?><div class="col-12 col-sm-6 col-xl-3"><div class="metric-card"><div class="d-flex justify-content-between align-items-center"><span class="metric-label"><?=adm_h($label)?></span><span class="metric-icon"><i class="bi bi-<?=adm_h($icon)?>"></i></span></div><div class="metric-value"><?=adm_h($value)?></div></div></div><?php endforeach;?></div>
<div class="row g-4"><div class="col-12"><section id="pedidos" class="content-card"><div class="content-heading"><div><h2>Pedidos recentes</h2><small class="text-secondary">Últimos 20 registros</small></div><a href="/admin/payments.php" class="btn btn-bia btn-sm">Gerenciar pagamentos</a></div><?php if (!$orders):?><div class="empty-state"><i class="bi bi-bag-x"></i><strong>Nenhum pedido recebido ainda</strong><p>Os novos pedidos aparecerão aqui.</p></div><?php else:?><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Pedido</th><th>Cliente</th><th>Modalidade</th><th>Valor</th><th>Status</th><th>Pagamento</th><th>Data</th></tr></thead><tbody><?php foreach ($orders as $o):?><tr><td><strong>#<?=adm_h($o['id'])?></strong></td><td><?=adm_h($o['customer_name'])?></td><td><?=adm_h($o['fulfillment'] === 'delivery' ? 'Entrega' : 'Retirada')?></td><td>R$ <?=number_format((int)$o['total_cents'] / 100, 2, ',', '.')?></td><td><?=adm_status($o['status'])?></td><td><?=adm_status($o['payment_status'])?></td><td><?=adm_h($o['created_at'])?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section></div>
<div class="col-12"><section id="reservas" class="content-card"><div class="content-heading"><div><h2>Reservas recentes</h2><small class="text-secondary">Últimos 20 registros</small></div></div><?php if (!$reservations):?><div class="empty-state"><i class="bi bi-calendar2-x"></i><strong>Nenhuma reserva recebida ainda</strong><p>As solicitações aparecerão aqui.</p></div><?php else:?><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Reserva</th><th>Cliente</th><th>Pessoas</th><th>Data</th><th>Horário</th><th>Status</th></tr></thead><tbody><?php foreach ($reservations as $r):?><tr><td><strong>#<?=adm_h($r['id'])?></strong></td><td><?=adm_h($r['customer_name'])?></td><td><?=adm_h($r['guests'])?></td><td><?=adm_h($r['date'])?></td><td><?=adm_h($r['time'])?></td><td><?=adm_status($r['status'])?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section></div></div><p class="text-secondary small mt-4">Indicadores históricos dos registros demonstrativos. Gestão de status será adicionada em etapa posterior.</p><?php adm_end();
