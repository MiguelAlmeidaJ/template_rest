<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/site.php';
use App\Customer;
use App\Db;

Customer::boot();
$error = '';
$notice = '';
$id = Customer::id();
$next = ($_GET['next'] ?? '') === 'pedido' ? 'pedido' : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Customer::check();
        $action = $_POST['action'] ?? '';
        if ($action === 'register') {
            Customer::register((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''));
            $id = Customer::id();
        } elseif ($action === 'login') {
            Customer::login((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''));
            $id = Customer::id();
        } elseif ($action === 'logout') {
            $_SESSION = [];
            session_regenerate_id(true);
            header('Location: /cliente/');
            exit;
        } elseif (!$id) {
            throw new DomainException('Faça login para continuar');
        } elseif ($action === 'address') {
            $fields = ['recipient' => 150,'phone' => 24,'address' => 350,'neighborhood' => 120,'reference_point' => 350];
            $values = [];
            foreach ($fields as $field => $length) {
                $value = trim((string)($_POST[$field] ?? ''));
                if (mb_strlen($value) > $length) {
                    throw new DomainException('Campo muito longo: '.$field);
                }$values[] = $value;
            }
            $q = Db::pdo()->prepare('INSERT INTO customer_addresses(customer_id,recipient,phone,address,neighborhood,reference_point) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE recipient=VALUES(recipient),phone=VALUES(phone),address=VALUES(address),neighborhood=VALUES(neighborhood),reference_point=VALUES(reference_point)');
            $q->execute([$id,...$values]);
            $notice = 'Endereço salvo com sucesso.';
        } elseif ($action === 'profile') {
            $name = trim((string)($_POST['full_name'] ?? ''));
            $phone = preg_replace('/\\D/', '', (string)($_POST['phone'] ?? ''));
            $cpf = preg_replace('/\\D/', '', (string)($_POST['cpf'] ?? ''));
            if (mb_strlen($name) < 2 || mb_strlen($name) > 150 || ($phone !== '' && (strlen($phone) < 10 || strlen($phone) > 11)) || ($cpf !== '' && strlen($cpf) !== 11)) {
                throw new DomainException('Confira nome, telefone e CPF.');
            }
            if ($cpf !== '') {
                if (preg_match('/^(\\d)\\1{10}$/', $cpf)) {
                    throw new DomainException('CPF inválido.');
                }
                for ($digit = 9;$digit < 11;$digit++) {
                    $sum = 0;
                    for ($i = 0;$i < $digit;$i++) {
                        $sum += (int)$cpf[$i] * ($digit + 1 - $i);
                    }
                    $check = ($sum * 10) % 11;
                    if (($check === 10 ? 0 : $check) !== (int)$cpf[$digit]) {
                        throw new DomainException('CPF inválido.');
                    }
                }
            }
            $stmt = Db::pdo()->prepare('UPDATE customer_accounts SET full_name=?,phone=?,cpf=? WHERE id=?');
            $stmt->execute([$name,$phone,$cpf ?: null,$id]);
            $notice = 'Informações atualizadas.';
        } elseif ($action === 'message') {
            $order = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
            $body = trim((string)($_POST['body'] ?? ''));
            if (!$order || mb_strlen($body) < 1 || mb_strlen($body) > 2000) {
                throw new DomainException('Escreva uma mensagem de até 2.000 caracteres');
            }
            $q = Db::pdo()->prepare('SELECT id FROM lunch_orders WHERE id=? AND customer_id=?');
            $q->execute([$order,$id]);
            if (!$q->fetch()) {
                throw new DomainException('Pedido não encontrado');
            }
            $q = Db::pdo()->prepare("INSERT INTO lunch_messages(order_id,customer_id,sender,body) VALUES(?,?,'customer',?)");
            $q->execute([$order,$id,$body]);
            \App\AdminNotification::create('chat', $order, 'Nova mensagem do cliente', 'Pedido #'.$order.' recebeu uma mensagem', '/admin/lunch-chat.php?order='.$order);
            $notice = 'Mensagem enviada ao restaurante.';
        } else {
            throw new DomainException('Ação inválida');
        }
        if (in_array($action, ['login','register'], true) && $next === 'pedido') {
            header('Location: /pedido/');
            exit;
        }
    } catch (DomainException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log((string)$e);
        $error = 'Não foi possível concluir a operação.';
    }
}
$id = Customer::id();
$address = $id ? Customer::address($id) : [];
$extraAddresses = $id ? \App\CustomerPreferences::addresses($id) : [];
$savedFavorites = $id ? \App\CustomerPreferences::favorites($id) : [];
$orders = $id ? Customer::orders($id) : [];
$statusLabels = ['pending' => 'Novo pedido','accepted' => 'Novo pedido','preparing' => 'Em preparação','out_for_delivery' => 'Saindo para entrega','delivered' => 'Pedido entregue','cancelled' => 'Cancelado'];
$selected = filter_var($_GET['order'] ?? null, FILTER_VALIDATE_INT);
$selectedOrder = null;
$messages = [];
if ($id && $selected) {
    foreach ($orders as $o) {
        if ((int)$o['id'] === $selected) {
            $selectedOrder = $o;
        }
    }if ($selectedOrder) {
        $q = Db::pdo()->prepare('SELECT sender,body,created_at FROM lunch_messages WHERE order_id=? AND customer_id=? ORDER BY id ASC LIMIT 150');
        $q->execute([$selected,$id]);
        $messages = $q->fetchAll();
    }
}
bia_header('Minha conta', '');?><div class="shell section customer-page"><span class="eyebrow">ÁREA DO CLIENTE</span><h1 class="title">Minha conta</h1>
<?php if ($error):?><p class="customer-alert" role="alert"><?=bia_esc($error)?></p><?php endif;?><?php if ($notice):?><p class="customer-success" role="status"><?=bia_esc($notice)?></p><?php endif;?>
<?php if (!$id):?>
<div class="customer-access">
  <div class="customer-access-intro">
    <span class="customer-access-kicker"><i class="bi bi-person-heart" aria-hidden="true"></i> Sua experiência com a Bia</span>
    <h2>Seu almoço, ainda mais prático.</h2>
    <p>Entre para acompanhar suas quentinhas, conversar com nossa equipe e guardar seu endereço para a próxima vez.</p>
    <div class="customer-access-perks">
      <span><i class="bi bi-bag-check" aria-hidden="true"></i> Acompanhe seus pedidos</span>
      <span><i class="bi bi-geo-alt" aria-hidden="true"></i> Endereço sempre à mão</span>
      <span><i class="bi bi-chat-dots" aria-hidden="true"></i> Converse com a equipe</span>
    </div>
  </div>
  <section class="customer-access-card" aria-labelledby="customer-access-title">
    <div class="customer-access-tabs" role="tablist" aria-label="Acesso de clientes">
      <button type="button" id="customer-login-tab" role="tab" aria-controls="customer-login-panel" aria-selected="true" class="active">Entrar</button>
      <button type="button" id="customer-register-tab" role="tab" aria-controls="customer-register-panel" aria-selected="false" tabindex="-1">Criar conta</button>
    </div>
    <div class="customer-access-body">
      <div id="customer-login-panel" role="tabpanel" aria-labelledby="customer-login-tab">
        <h2 id="customer-access-title">Que bom ter você por aqui.</h2>
        <p>Entre com seu e-mail e senha para acessar seus pedidos.</p>
        <form method="post" class="customer-access-form">
          <input type="hidden" name="csrf" value="<?=bia_esc(Customer::csrf())?>">
          <input type="hidden" name="action" value="login">
          <label for="customer-login-email">E-mail</label>
          <input id="customer-login-email" type="email" name="email" placeholder="seuemail@exemplo.com" required autocomplete="email">
          <label for="customer-login-password">Senha</label>
          <input id="customer-login-password" type="password" name="password" placeholder="Sua senha" required autocomplete="current-password">
          <button class="btn customer-access-submit" type="submit">Entrar na minha conta <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </form>
      </div>
      <div id="customer-register-panel" role="tabpanel" aria-labelledby="customer-register-tab" hidden>
        <h2>Vamos criar sua conta?</h2>
        <p>É rápido. Você só precisa de um e-mail e uma senha.</p>
        <form method="post" class="customer-access-form">
          <input type="hidden" name="csrf" value="<?=bia_esc(Customer::csrf())?>">
          <input type="hidden" name="action" value="register">
          <label for="customer-register-email">E-mail</label>
          <input id="customer-register-email" type="email" name="email" placeholder="seuemail@exemplo.com" required autocomplete="email">
          <label for="customer-register-password">Crie uma senha</label>
          <input id="customer-register-password" type="password" name="password" minlength="10" maxlength="128" placeholder="Mínimo de 10 caracteres" required autocomplete="new-password">
          <button class="btn customer-access-submit" type="submit">Criar minha conta <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </form>
      </div>
    </div>
  </section>
</div>
<script src="/assets/js/customer-access.js?v=1" defer></script>
<?php else: ?>
<?php
    $tab = (string) ($_GET['tab'] ?? 'pedidos');
    if (!in_array($tab, ['pedidos','padrao','enderecos','perfil'], true)) {
        $tab = 'pedidos';
    }
    $profileQuery = Db::pdo()->prepare('SELECT email, full_name, phone, cpf FROM customer_accounts WHERE id=?');
    $profileQuery->execute([$id]);
    $profile = $profileQuery->fetch() ?: [];
    ?>
<div class="customer-dashboard">
  <aside class="customer-dashboard-nav" aria-label="Menu da minha conta">
    <div class="customer-dashboard-identity"><span class="customer-dashboard-avatar"><i class="bi bi-person" aria-hidden="true"></i></span><div><strong><?=bia_esc($profile['full_name'] ?: 'Minha conta')?></strong><small><?=bia_esc($profile['email'] ?? '')?></small></div></div>
    <nav>
      <a class="<?=$tab === 'pedidos' ? 'active' : ''?>" href="/cliente/?tab=pedidos"><i class="bi bi-bag-check"></i> Pedidos</a>
      <a class="<?=$tab === 'padrao' ? 'active' : ''?>" href="/cliente/?tab=padrao"><i class="bi bi-bookmark-heart"></i> Cardápio padrão</a>
      <a class="<?=$tab === 'enderecos' ? 'active' : ''?>" href="/cliente/?tab=enderecos"><i class="bi bi-geo-alt"></i> Endereços</a>
      <a class="<?=$tab === 'perfil' ? 'active' : ''?>" href="/cliente/?tab=perfil"><i class="bi bi-person-gear"></i> Informações do usuário</a>
    </nav>
    <form method="post"><input type="hidden" name="csrf" value="<?=bia_esc(Customer::csrf())?>"><input type="hidden" name="action" value="logout"><button class="customer-dashboard-logout" type="submit"><i class="bi bi-box-arrow-right"></i> Sair da conta</button></form>
  </aside>
  <div class="customer-dashboard-main">
  <?php if ($tab === 'pedidos'): ?>
    <div class="customer-dashboard-heading"><div><span class="eyebrow">MEUS PEDIDOS</span><h2>Seus almoços</h2><p>Veja a situação de cada pedido e converse com o restaurante.</p></div><a class="btn" href="/pedido/">Fazer pedido <i class="bi bi-arrow-right"></i></a></div>
    <?php if (!$orders): ?><div class="customer-dashboard-empty"><i class="bi bi-bag"></i><h3>Você ainda não fez pedidos.</h3><p>Escolha os pratos do dia e monte sua primeira quentinha.</p><a href="/pedido/">Conhecer o cardápio</a></div><?php endif; ?>
    <div class="customer-dashboard-orders">
      <?php foreach ($orders as $order): ?>
        <article class="customer-dashboard-order <?=$selectedOrder && (int)$selectedOrder['id'] === (int)$order['id'] ? 'selected' : ''?>">
          <div><strong>Pedido #<?=bia_esc($order['id'])?></strong><small><?=bia_esc(date('d/m/Y H:i', strtotime($order['created_at'])))?> · <?=bia_esc($order['quantity'])?> quentinha(s)</small></div>
          <span class="customer-order-status"><?=bia_esc($statusLabels[$order['status']] ?? $order['status'])?></span>
          <strong class="customer-order-total">R$ <?=number_format((int)$order['total_cents'] / 100, 2, ',', '.')?></strong>
          <a href="/cliente/?tab=pedidos&amp;order=<?= (int)$order['id']?>#conversa">Acompanhar <i class="bi bi-arrow-right"></i></a>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if ($selectedOrder): ?>
      <section class="customer-panel" id="conversa"><h3>Pedido #<?=bia_esc($selectedOrder['id'])?> · <?=bia_esc($statusLabels[$selectedOrder['status']] ?? $selectedOrder['status'])?></h3><p>Converse com a equipe sobre este pedido.</p>
        <div class="customer-chat"><?php if (!$messages): ?><p>Ainda não há mensagens.</p><?php endif; ?><?php foreach ($messages as $message): ?><article class="<?=$message['sender'] === 'customer' ? 'from-customer' : 'from-restaurant'?>"><strong><?=$message['sender'] === 'customer' ? 'Você' : 'Restaurante da Bia'?></strong><p><?=nl2br(bia_esc($message['body']))?></p><small><?=bia_esc($message['created_at'])?></small></article><?php endforeach; ?></div>
        <form method="post"><input type="hidden" name="csrf" value="<?=bia_esc(Customer::csrf())?>"><input type="hidden" name="action" value="message"><input type="hidden" name="order_id" value="<?= (int)$selectedOrder['id']?>"><label>Mensagem<textarea name="body" rows="3" maxlength="2000" required></textarea></label><button type="submit" class="btn">Enviar mensagem</button></form>
      </section>
    <?php endif; ?>
  <?php elseif ($tab === 'padrao'): ?>
    <div class="customer-dashboard-heading"><div><span class="eyebrow">SEUS FAVORITOS</span><h2>Cardápio padrão</h2><p>Salve montagens de quentinhas para facilitar os próximos pedidos.</p></div><a class="btn" href="/pedido/">Montar quentinha</a></div>
    <?php if (!$savedFavorites): ?><div class="customer-dashboard-empty"><i class="bi bi-bookmark-heart"></i><h3>Nenhuma montagem salva.</h3><p>Você pode salvar seu pedido padrão durante a montagem da quentinha.</p></div><?php endif; ?>
    <div class="customer-preference-list"><?php foreach ($savedFavorites as $favorite): ?><div class="customer-preference-row"><div><strong><?=bia_esc($favorite['name'])?></strong><small>Sua montagem salva</small></div><div class="customer-preference-actions"><a href="/pedido/?favorite=<?= (int)$favorite['id']?>">Pedir novamente</a><button type="button" data-favorite-delete="<?= (int)$favorite['id']?>">Excluir</button></div></div><?php endforeach; ?></div>
  <?php elseif ($tab === 'enderecos'): ?>
    <div class="customer-dashboard-heading"><div><span class="eyebrow">ONDE ENTREGAR</span><h2>Meus endereços</h2><p>Cadastre e gerencie até 10 endereços para selecionar na hora de pedir.</p></div><button type="button" class="btn" id="customer-new-address"><i class="bi bi-plus-lg"></i> Novo endereço</button></div>
    <?php if (!$extraAddresses): ?><div class="customer-dashboard-empty"><i class="bi bi-geo-alt"></i><h3>Nenhum endereço cadastrado.</h3><p>Cadastre sua casa, trabalho ou outro local de entrega.</p></div><?php endif; ?>
    <div class="customer-preference-list"><?php foreach ($extraAddresses as $entry): ?><div class="customer-preference-row"><div><strong><?=bia_esc($entry['label'])?> <?=$entry['is_default'] ? '· Principal' : ''?></strong><small><?=bia_esc($entry['address'])?>, <?=bia_esc($entry['number'] ?: 's/n')?><?= $entry['complement'] ? ', '.bia_esc($entry['complement']) : ''?> · <?=bia_esc($entry['neighborhood'])?></small><small>Referência: <?=bia_esc($entry['reference_point'] ?: 'Não informada')?></small></div><div class="customer-preference-actions"><button type="button" data-address-edit="<?= (int)$entry['id']?>">Editar</button><button type="button" data-address-delete="<?= (int)$entry['id']?>">Excluir</button></div></div><?php endforeach; ?></div>
  <?php else: ?>
    <div class="customer-dashboard-heading"><div><span class="eyebrow">DADOS PESSOAIS</span><h2>Informações do usuário</h2><p>Mantenha seus dados atualizados para agilizar a entrega.</p></div></div>
    <form method="post" class="customer-profile-form">
      <input type="hidden" name="csrf" value="<?=bia_esc(Customer::csrf())?>"><input type="hidden" name="action" value="profile">
      <label>Nome completo<input name="full_name" value="<?=bia_esc($profile['full_name'] ?? '')?>" maxlength="150" autocomplete="name" required></label>
      <label>E-mail<input type="email" value="<?=bia_esc($profile['email'] ?? '')?>" disabled><small>Para alterar o e-mail, entre em contato com o restaurante.</small></label>
      <label>Telefone / WhatsApp<input name="phone" type="tel" inputmode="tel" value="<?=bia_esc($profile['phone'] ?? '')?>" maxlength="20" autocomplete="tel"></label>
      <label>CPF (opcional)<input name="cpf" inputmode="numeric" value="<?=bia_esc($profile['cpf'] ?? '')?>" maxlength="14" placeholder="000.000.000-00"><small>Informe apenas se desejar manter esse dado cadastrado.</small></label>
      <button type="submit" class="btn">Salvar informações</button>
    </form>
  <?php endif; ?>
  </div>
</div>
<?php endif;?></div><?php if ($id): ?>
<dialog id="customer-address-dialog" class="customer-address-dialog" aria-labelledby="customer-address-title">
<h2 id="customer-address-title">Cadastrar endereço</h2>
<form id="customer-address-form">
  <input type="hidden" name="id" value="">
  <label>Identificação<input name="label" placeholder="Casa, Trabalho..." maxlength="70" required></label>
  <label>Quem vai receber?<input name="recipient" maxlength="150" required></label>
  <label>WhatsApp<input name="phone" type="tel" maxlength="24" required></label>
  <label>Rua / endereço<input name="address" maxlength="350" required></label><label>Número<input name="number" maxlength="20" placeholder="Ex.: 425"></label><label>Complemento<input name="complement" maxlength="120" placeholder="Apto, bloco, casa..."></label>
  <label>Bairro<input name="neighborhood" maxlength="120" required></label>
  <label>Ponto de referência<input name="reference_point" maxlength="350"></label>
  <label class="customer-address-checkbox"><input name="is_default" type="checkbox"> Usar como endereço principal</label>
  <p id="customer-address-feedback" role="status" aria-live="polite"></p>
  <div class="customer-address-dialog-buttons">
    <button type="button" id="customer-address-cancel" class="btn btn-outline-secondary">Cancelar</button>
    <button class="btn" type="submit">Salvar endereço</button>
  </div>
</form>
</dialog>
<script type="application/json" id="customer-preferences-data"><?=json_encode(['addresses' => $extraAddresses,'csrf' => Customer::csrf()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)?></script>
<script src="/assets/js/customer-preferences.js?v=1" defer></script>
<?php endif; ?>
<?php bia_footer();
