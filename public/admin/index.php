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
        $staffRole = \App\Staff::role();
        header('Location: '.($staffRole === 'employee' ? '/admin/pdv.php' : '/admin/'), true, 303);
        exit;
    }
    $error = 'E-mail ou senha incorretos.';
}
if (isset($_SESSION['admin_id'])) {
    $staff = \App\Staff::current();
    if (!$staff) {
        $_SESSION = [];
        session_destroy();
    } elseif ($staff['role'] === 'employee') {
        header('Location: /admin/pdv.php', true, 302);
        exit;
    } elseif (!\App\Staff::allowed('index.php', $staff['role'])) {
        http_response_code(403);
        exit('Sem permissão');
    }
}
if (!isset($_SESSION['admin_id'])) {
    $loginLogo = \App\Cms::setting('logo');
    adm_start('Acesso ao painel', '', true);
    ?>
    <main class="bia-login">
        <section class="bia-login-story" aria-label="Restaurante da Bia">
            <a class="bia-login-story-brand" href="/" aria-label="Voltar ao site do Restaurante da Bia">
                <?php if ($loginLogo): ?>
                    <img src="<?=adm_h($loginLogo)?>" alt="Restaurante da Bia">
                <?php else: ?>
                    <span class="bia-login-brand-fallback">Restaurante <strong>da Bia</strong></span>
                <?php endif; ?>
            </a>
            <div class="bia-login-story-content">
                <span class="bia-login-story-label"><span class="bia-login-status-dot"></span> CENTRAL DE OPERAÇÕES</span>
                <h1>O cuidado com cada pedido começa aqui.</h1>
                <p>Pedidos, atendimento e gestão em um ambiente feito para a rotina do restaurante.</p>
                <div class="bia-login-story-line" aria-hidden="true"></div>
                <span class="bia-login-story-signature">Restaurante da Bia <span>·</span> Além Paraíba, MG</span>
            </div>
            <span class="bia-login-story-bottom">Um espaço reservado para nossa equipe.</span>
            <div class="bia-login-story-decoration" aria-hidden="true"><span></span><span></span><span></span></div>
        </section>

        <section class="bia-login-entry" aria-labelledby="bia-login-title">
            <div class="bia-login-form-wrap">
                <div class="bia-login-mobile-brand">
                    <?php if ($loginLogo): ?>
                        <img src="<?=adm_h($loginLogo)?>" alt="Restaurante da Bia">
                    <?php else: ?>
                        <strong>Restaurante da Bia</strong>
                    <?php endif; ?>
                </div>
                <div class="bia-login-form-heading">
                    <span class="bia-login-kicker">ACESSO RESTRITO</span>
                    <h2 id="bia-login-title">Boas-vindas de volta.</h2>
                    <p>Acesse sua conta para começar o atendimento.</p>
                </div>

                <?php if ($error): ?>
                    <div class="bia-login-error" role="alert">
                        <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                        <?=adm_h($error)?>
                    </div>
                <?php endif; ?>

                <form method="post" class="bia-login-form" id="bia-admin-login-form">
                    <input type="hidden" name="csrf" value="<?=adm_h($token)?>">
                    <div class="bia-login-field">
                        <label for="email">E-mail</label>
                        <div class="bia-login-input-wrap">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <input type="email" id="email" name="email"
                                value="<?=adm_h($email ?? '')?>" autocomplete="username"
                                placeholder="seuemail@restaurante.com.br" maxlength="254"
                                spellcheck="false" required autofocus>
                        </div>
                    </div>
                    <div class="bia-login-field">
                        <label for="password">Senha</label>
                        <div class="bia-login-input-wrap bia-login-password-wrap">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            <input type="password" id="password" name="password"
                                autocomplete="current-password" placeholder="Digite sua senha" required>
                            <button type="button" class="bia-login-password-toggle" id="bia-show-password"
                                aria-controls="password" aria-label="Mostrar senha" aria-pressed="false">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <button class="bia-login-submit" type="submit" id="bia-login-submit">
                        <span>Entrar no painel</span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>
                <div class="bia-login-form-bottom">
                    <a href="/"><i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar ao site</a>
                    <span><i class="bi bi-shield-lock" aria-hidden="true"></i> Acesso seguro</span>
                </div>
            </div>
            <small class="bia-login-copyright">© <?=date('Y')?> Restaurante da Bia</small>
        </section>
    </main>
    <script src="/assets/js/admin-login.js?v=1" defer></script>
    <?php
    adm_end(true);
    exit;
}
$db = \App\Db::pdo();
$stats = ['orders' => (int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),'revenue' => (int)$db->query("SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE payment_status='paid'")->fetchColumn(),'pending' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE payment_status='pending'")->fetchColumn()];
$orders = $db->query('SELECT id,customer_name,fulfillment,total_cents,status,payment_status,created_at FROM orders ORDER BY id DESC LIMIT 20')->fetchAll();
adm_start('Visão geral');?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4"><div><span class="page-kicker">PAINEL ADMINISTRATIVO</span><h1 class="page-title">Visão geral</h1><p class="page-subtitle mb-0">Acompanhe a movimentação do restaurante.</p></div><form method="post"><input type="hidden" name="csrf" value="<?=adm_h($token)?>"><button name="logout" value="1" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-right me-2"></i>Sair</button></form></div>
<div class="row g-3 mb-4">
<?php foreach ([['Pedidos registrados',$stats['orders'],'bag-check'],['Receita confirmada','R$ '.number_format($stats['revenue'] / 100, 2, ',', '.'),'cash-stack'],['Pagamentos pendentes',$stats['pending'],'clock-history']] as [$label,$value,$icon]):?><div class="col-12 col-sm-6 col-xl-3"><div class="metric-card"><div class="d-flex justify-content-between align-items-center"><span class="metric-label"><?=adm_h($label)?></span><span class="metric-icon"><i class="bi bi-<?=adm_h($icon)?>"></i></span></div><div class="metric-value"><?=adm_h($value)?></div></div></div><?php endforeach;?></div>
<div class="row g-4"><div class="col-12"><section id="pedidos" class="content-card"><div class="content-heading"><div><h2>Pedidos recentes</h2><small class="text-secondary">Últimos 20 registros</small></div><a href="/admin/payments.php" class="btn btn-bia btn-sm">Gerenciar pagamentos</a></div><?php if (!$orders):?><div class="empty-state"><i class="bi bi-bag-x"></i><strong>Nenhum pedido recebido ainda</strong><p>Os novos pedidos aparecerão aqui.</p></div><?php else:?><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Pedido</th><th>Cliente</th><th>Modalidade</th><th>Valor</th><th>Status</th><th>Pagamento</th><th>Data</th></tr></thead><tbody><?php foreach ($orders as $o):?><tr><td><strong>#<?=adm_h($o['id'])?></strong></td><td><?=adm_h($o['customer_name'])?></td><td><?=adm_h($o['fulfillment'] === 'delivery' ? 'Entrega' : 'Retirada')?></td><td>R$ <?=number_format((int)$o['total_cents'] / 100, 2, ',', '.')?></td><td><?=adm_status($o['status'])?></td><td><?=adm_status($o['payment_status'])?></td><td><?=adm_h($o['created_at'])?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section></div>
</div><p class="text-secondary small mt-4">Indicadores dos pedidos registrados.</p><?php adm_end();
