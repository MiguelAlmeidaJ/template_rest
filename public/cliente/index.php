<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/site.php';
use App\Customer;
use App\Db;

Customer::boot();
$error = '';
$notice = ($_GET['saved'] ?? '') === 'perfil' ? 'Suas informações foram atualizadas.' : '';
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
            // A conta de funcionário, gestor ou administrador deve abrir o painel interno.
            if (\App\Staff::signInLinkedCustomer($id)) {
                header('Location: /admin/', true, 303);
                exit;
            }
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
            header('Location: /cliente/?tab=perfil&saved=perfil', true, 303);
            exit;
        } elseif ($action === 'message') {
            // Redirect legacy form submissions to the live conversation endpoint.
            throw new DomainException('Atualize a página para usar o novo chat.');
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
if (!$id) {
    require __DIR__ . '/login-view.php';
    exit;
}
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
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Minha conta | Restaurante da Bia</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@400;600;700;800&display=swap">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/bia.css?v=18">
  <?php $customerFavicon = \App\Cms::setting('favicon');
    if ($customerFavicon): ?>
  <link rel="icon" href="<?=bia_esc($customerFavicon)?>">
  <?php endif; ?>
</head>
<body class="customer-private-body">
<a class="skip" href="#conteudo">Ir para conteúdo</a>
<div class="customer-private-topbar">
  <a href="/" class="customer-private-brand">
    <?php $customerLogo = \App\Cms::setting('logo');
    if ($customerLogo): ?>
    <img src="<?=bia_esc($customerLogo)?>" alt="Restaurante da Bia">
    <?php else: ?>Restaurante da Bia<?php endif; ?>
  </a>
  <span>Área do cliente</span>
  <a href="/" class="customer-private-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar ao site</a>
</div>
<main id="conteudo" class="shell section customer-page">
<span class="eyebrow">ÁREA DO CLIENTE</span><h1 class="title">Minha conta</h1>
<?php if ($error): ?><p class="customer-alert" role="alert"><?=bia_esc($error)?></p><?php endif; ?>
<?php if ($notice): ?><p class="customer-success" role="status"><?=bia_esc($notice)?></p><?php endif; ?>
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
      <section class="customer-panel" id="conversa">
        <h3>Pedido #<?= (int) $selectedOrder['id'] ?> · <span id="customer-order-status"><?=bia_esc($statusLabels[$selectedOrder['status']] ?? $selectedOrder['status'])?></span></h3>
        <p>Converse com a equipe sobre este pedido. As mensagens e o andamento são atualizados automaticamente.</p>
        <div class="customer-chat" data-order-id="<?= (int) $selectedOrder['id'] ?>" aria-live="polite"></div>
        <form id="customer-chat-form">
          <input type="hidden" name="csrf" value="<?=bia_esc(Customer::csrf())?>">
          <label for="customer-chat-body">Mensagem</label>
          <textarea id="customer-chat-body" name="body" rows="3" maxlength="2000" required></textarea>
          <div class="customer-chat-submit"><span id="customer-chat-feedback" role="status" aria-live="polite"></span><button type="submit" class="btn">Enviar mensagem</button></div>
        </form>
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
    <div class="customer-dashboard-heading">
      <div><span class="eyebrow">SEUS DADOS</span><h2>Meu perfil</h2><p>Deixe suas informações em dia para pedir com mais praticidade.</p></div>
    </div>
    <form method="post" class="customer-profile-form customer-profile-refined" id="customer-profile-form">
      <input type="hidden" name="csrf" value="<?=bia_esc(Customer::csrf())?>">
      <input type="hidden" name="action" value="profile">
      <div class="customer-profile-section-label"><i class="bi bi-person-vcard" aria-hidden="true"></i><span>Dados de contato</span></div>
      <label class="wide" for="profile-name">Nome completo <span class="required-mark">*</span>
        <input id="profile-name" name="full_name" value="<?=bia_esc($profile['full_name'] ?? '')?>" maxlength="150" autocomplete="name" placeholder="Como podemos chamar você?" required>
      </label>
      <label for="profile-email">E-mail cadastrado
        <input id="profile-email" type="email" value="<?=bia_esc($profile['email'] ?? '')?>" readonly>
        <small>Seu e-mail de acesso. Para alterá-lo, fale com o restaurante.</small>
      </label>
      <label for="profile-phone">WhatsApp
        <input id="profile-phone" name="phone" type="tel" inputmode="numeric" value="<?=bia_esc($profile['phone'] ?? '')?>" maxlength="15" autocomplete="tel" placeholder="(32) 99999-9999" data-br-phone>
        <small>Usado para contato sobre a entrega.</small>
      </label>
      <div class="customer-profile-section-label"><i class="bi bi-shield-check" aria-hidden="true"></i><span>Informação opcional</span></div>
      <label class="wide" for="profile-cpf">CPF
        <input id="profile-cpf" name="cpf" inputmode="numeric" value="<?=bia_esc($profile['cpf'] ?? '')?>" maxlength="14" placeholder="000.000.000-00" data-br-cpf>
        <small>Não é necessário informar o CPF para fazer pedidos.</small>
      </label>
      <div class="customer-profile-footer"><span><i class="bi bi-lock" aria-hidden="true"></i> Seus dados ficam disponíveis apenas na sua conta e para o atendimento.</span><button type="submit" class="btn"><i class="bi bi-check2" aria-hidden="true"></i> Salvar alterações</button></div>
    </form>
  <?php endif; ?>
  </div>
</div>
</div>
<dialog id="customer-address-dialog" class="customer-address-dialog customer-address-dialog-refined" aria-labelledby="customer-address-title">
  <div class="customer-address-dialog-header">
    <div><span class="eyebrow">LOCAL DE ENTREGA</span><h2 id="customer-address-title">Novo endereço</h2><p>Preencha os dados para receber seus próximos pedidos.</p></div>
    <button type="button" id="customer-address-close" aria-label="Fechar cadastro" class="customer-address-close"><i class="bi bi-x-lg"></i></button>
  </div>
  <form id="customer-address-form">
    <input type="hidden" name="id" value="">
    <div class="customer-address-dialog-body">
      <div class="customer-address-form-grid">
        <label class="wide" for="address-label">Como deseja identificar? <span class="required-mark">*</span>
          <input id="address-label" name="label" placeholder="Ex.: Casa, Trabalho, Casa da mãe" maxlength="70" required autocomplete="off">
        </label>
        <div class="customer-address-group-title"><i class="bi bi-person"></i> Quem vai receber?</div>
        <label for="address-recipient">Nome de quem recebe <span class="required-mark">*</span>
          <input id="address-recipient" name="recipient" maxlength="150" autocomplete="name" required placeholder="Nome completo">
        </label>
        <label for="address-phone">WhatsApp <span class="required-mark">*</span>
          <input id="address-phone" name="phone" type="tel" inputmode="numeric" maxlength="15" pattern="\([0-9]{2}\) [0-9]{4,5}-[0-9]{4}" title="Informe telefone com DDD" data-br-phone placeholder="(32) 99999-9999" required autocomplete="tel">
        </label>
        <div class="customer-address-group-title"><i class="bi bi-geo-alt"></i> Onde entregar?</div>
        <label class="wide" for="address-street">Rua / avenida <span class="required-mark">*</span>
          <input id="address-street" name="address" maxlength="350" minlength="3" autocomplete="address-line1" required placeholder="Ex.: Rua Dr. Sobral Pinto">
        </label>
        <label for="address-number">Número
          <input id="address-number" name="number" maxlength="20" placeholder="Ex.: 425" autocomplete="off">
        </label>
        <label for="address-complement">Complemento
          <input id="address-complement" name="complement" maxlength="120" placeholder="Casa, apto, bloco..." autocomplete="address-line2">
        </label>
        <label class="wide" for="address-neighborhood">Bairro <span class="required-mark">*</span>
          <input id="address-neighborhood" name="neighborhood" maxlength="120" autocomplete="address-level3" required placeholder="Ex.: Vila Laroca">
        </label>
        <label class="wide" for="address-reference">Ponto de referência
          <input id="address-reference" name="reference_point" maxlength="350" placeholder="Ex.: Portão verde, próximo à praça">
        </label>
        <label class="customer-address-checkbox wide"><input name="is_default" type="checkbox"> <span>Definir como meu endereço principal<small>Será selecionado automaticamente no próximo pedido.</small></span></label>
      </div>
    </div>
    <div class="customer-address-dialog-footer">
      <p id="customer-address-feedback" role="status" aria-live="polite"></p>
      <div class="customer-address-dialog-buttons">
        <button type="button" id="customer-address-cancel" class="btn btn-outline-secondary">Cancelar</button>
        <button class="btn" type="submit"><i class="bi bi-check2"></i> Salvar endereço</button>
      </div>
    </div>
  </form>
</dialog>
<script type="application/json" id="customer-preferences-data"><?=json_encode(['addresses' => $extraAddresses,'csrf' => Customer::csrf()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)?></script>
<script src="/assets/js/customer-preferences.js?v=3" defer></script><script src="/assets/js/customer-chat-live.js?v=2" defer></script>
</main>
</body>
</html>
