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
<?php else:?><div class="customer-toolbar"><p>Acompanhe seus pedidos e mantenha seu endereço atualizado.</p><form method="post"><input type="hidden" name="csrf" value="<?=bia_esc(Customer::csrf())?>"><input type="hidden" name="action" value="logout"><button type="submit" class="customer-link">Sair da conta</button></form></div><div class="customer-layout"><div><section class="customer-panel"><h2>Meus pedidos</h2><?php if (!$orders):?><p>Você ainda não tem pedidos vinculados à sua conta.</p><?php endif;?><?php foreach ($orders as $order):?><div class="customer-order"><div><strong>Pedido #<?=bia_esc((string)$order['id'])?></strong><span><?=bia_esc($statusLabels[$order['status']] ?? $order['status'])?></span><small><?=bia_esc($order['quantity'])?> quentinha(s) · R$ <?=number_format($order['total_cents'] / 100, 2, ',', '.')?></small></div><a href="?order=<?=bia_esc($order['id'])?>">Acompanhar e conversar ↗</a></div><?php endforeach;?><a class="btn" href="/pedido/">Fazer novo pedido</a></section>
<?php if ($selectedOrder):?><section class="customer-panel" id="mensagens"><h2>Conversa sobre o pedido #<?=bia_esc((string)$selected)?></h2><p>Envie mensagens diretamente ao restaurante. As respostas aparecem aqui ao atualizar a página.</p><div class="customer-chat"><?php if (!$messages):?><p>Ainda não há mensagens neste pedido.</p><?php endif;?><?php foreach ($messages as $message):?><article class="<?=$message['sender'] === 'customer' ? 'from-customer' : 'from-restaurant'?>"><strong><?=$message['sender'] === 'customer' ? 'Você' : 'Restaurante da Bia'?></strong><p><?=nl2br(bia_esc($message['body']))?></p><small><?=bia_esc($message['created_at'])?></small></article><?php endforeach;?></div><form method="post"><input type="hidden" name="csrf" value="<?=bia_esc(Customer::csrf())?>"><input type="hidden" name="action" value="message"><input type="hidden" name="order_id" value="<?=bia_esc((string)$selected)?>"><label>Mensagem<textarea name="body" rows="3" maxlength="2000" required></textarea></label><button class="btn">Enviar mensagem</button></form></section><?php endif;?></div>
<section class="customer-panel"><h2>Endereço padrão de entrega</h2><form method="post"><input type="hidden" name="csrf" value="<?=bia_esc(Customer::csrf())?>"><input type="hidden" name="action" value="address"><?php foreach (['recipient' => 'Nome de quem recebe','phone' => 'WhatsApp','address' => 'Rua, número e complemento','neighborhood' => 'Bairro','reference_point' => 'Ponto de referência'] as $key => $label):?><label><?=bia_esc($label)?><input name="<?=$key?>" value="<?=bia_esc((string)($address[$key] ?? ''))?>" maxlength="<?=$key === 'address' ? 350 : ($key === 'reference_point' ? 350 : 150)?>"></label><?php endforeach;?><button class="btn">Salvar endereço</button></form><p class="customer-hint">Seus dados salvos poderão preencher automaticamente o próximo pedido.</p></section></div><?php endif;?></div><?php bia_footer();
