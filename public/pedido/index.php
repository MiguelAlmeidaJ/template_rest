<?php require dirname(__DIR__).'/includes/site.php';
use App\Lunch;

$error = '';
$settings = [];
$items = [];
$customerAddress = [];
$customerAddresses = [];
$customerFavorites = [];
$customerId = \App\Customer::id();
try {
    if ($customerId) {
        $customerAddresses = \App\CustomerPreferences::addresses($customerId);
        $customerFavorites = \App\CustomerPreferences::favorites($customerId);
        $customerAddress = $customerAddresses[0] ?? \App\Customer::address($customerId);
    }
} catch (Throwable $e) {
    error_log((string)$e);
}
try {
    $settings = Lunch::settings();
    $items = Lunch::items();
} catch (Throwable $e) {
    error_log((string)$e);
    $error = 'O cardápio de pedidos ainda não está configurado.';
}
bia_header('Faça seu pedido', '');?>
<div class="shell section lunch-page"><div class="lunch-intro"><span class="eyebrow">QUENTINHAS DA BIA</span><h1>Monte seu almoço do seu jeito.</h1><p>Personalize cada quentinha e identifique para quem ela será preparada.</p></div><div class="lunch-layout"><section class="lunch-panel"><div class="lunch-account-note" id="lunch-account-banner">
<?php if ($customerId): ?>
<i class="bi bi-person-check" aria-hidden="true"></i> Você está conectado à sua conta.
<a href="/cliente/">Acompanhar meus pedidos</a>
<?php else: ?>
<i class="bi bi-person-circle" aria-hidden="true"></i> Faça seu pedido como visitante ou
<button type="button" class="lunch-inline-link" id="lunch-open-account">entre / crie uma conta</button>
para salvar suas informações.
<?php endif; ?>
</div><h2>Seu pedido</h2>
<div class="lunch-favorites-toolbar" id="lunch-favorites-toolbar" <?=$customerId ? '' : 'hidden'?>>
  <label for="lunch-favorite-select">Montagens salvas</label>
  <select id="lunch-favorite-select"><option value="">Selecione um pedido padrão...</option></select>
  <button type="button" id="lunch-save-favorite" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bookmark-plus"></i> Salvar montagem atual</button>
</div><?php if ($error):?><p class="lunch-message"><?=bia_esc($error)?></p><?php elseif (!(int)$settings['orders_enabled']):?><p class="lunch-message">Pedidos online temporariamente indisponíveis.</p><?php elseif (!$items):?><p class="lunch-message">Estamos preparando o cardápio do dia. Volte em breve!</p><?php else:?>
<p class="lead"><?php if (($settings['limit_mode'] ?? 'total') === 'category'):?>Cada categoria tem seu próprio limite de escolha, indicado abaixo.<?php else:?>Selecione até <?= (int)$settings['max_choices']?> opções por quentinha.<?php endif;?></p><form id="lunch-form"><div class="lunch-count"><span>Quantidade de quentinhas</span><div class="lunch-counter"><button type="button" id="lunch-minus" aria-label="Diminuir">−</button><output id="lunch-quantity">1</output><button type="button" id="lunch-plus" aria-label="Aumentar">+</button></div></div><div id="lunch-groups" class="lunch-groups" hidden><div class="lunch-groups-title"><strong>Como deseja montar as quentinhas?</strong><p>Organize as unidades iguais em grupos. Cada grupo terá uma montagem única.</p></div><div id="lunch-group-list"></div><button type="button" id="lunch-add-group" class="lunch-add-group"><i class="bi bi-plus-circle"></i> Criar montagem diferente</button></div><div id="lunch-tabs" class="lunch-tabs" hidden><div id="lunch-tabs-nav" class="lunch-tabs-nav" role="tablist" aria-label="Montagens de quentinhas"></div></div><div id="lunch-boxes" class="lunch-boxes-panels"></div><h3>Informações para entrega</h3>
<div class="lunch-address-toolbar" id="lunch-address-toolbar" <?=$customerId ? '' : 'hidden'?>>
  <label for="lunch-address-select">Usar endereço cadastrado</label>
  <select id="lunch-address-select"><option value="">Preencher manualmente</option></select>
  <button type="button" class="btn btn-outline-secondary btn-sm" id="lunch-save-address">Salvar este endereço</button>
</div>
<div class="lunch-geolocation"><button type="button" id="lunch-find-address" class="lunch-inline-link"><i class="bi bi-crosshair"></i> Usar minha localização para preencher o endereço</button><span id="lunch-location-feedback" role="status"></span></div><div class="lunch-form-grid"><label>Nome completo<input name="customer_name" value="<?=bia_esc((string)($customerAddress['recipient'] ?? ''))?>" required maxlength="150" autocomplete="name"></label><label>WhatsApp<input name="customer_phone" id="customer-phone" inputmode="numeric" maxlength="15" placeholder="(32) 99999-9999" pattern="\([0-9]{2}\) [0-9]{4,5}-[0-9]{4}" title="Informe um telefone com DDD" value="<?=bia_esc((string)($customerAddress['phone'] ?? ''))?>" required type="tel" autocomplete="tel"></label><label class="wide">Endereço completo, número e complemento<input name="address" value="<?=bia_esc((string)($customerAddress['address'] ?? ''))?>" required minlength="8" maxlength="350" autocomplete="street-address"></label><label>Bairro<input name="neighborhood" value="<?=bia_esc((string)($customerAddress['neighborhood'] ?? ''))?>" required maxlength="120"></label><label>Ponto de referência<input name="reference_point" value="<?=bia_esc((string)($customerAddress['reference_point'] ?? ''))?>" required maxlength="350" placeholder="Ex.: próximo à praça"></label></div><h3>Pagamento na entrega</h3><div class="lunch-payments"><?php if ((int)$settings['pix_enabled']):?><label><input type="radio" name="payment_method" value="pix" required> Pix na entrega</label><?php endif;?><?php if ((int)$settings['card_enabled']):?><label><input type="radio" name="payment_method" value="card" required> Cartão na maquininha</label><?php endif;?></div><p class="lead">O pagamento será confirmado manualmente pelo restaurante após o recebimento.</p><button class="btn" type="submit" id="lunch-submit">Confirmar pedido <i class="bi bi-arrow-right"></i></button><p id="lunch-feedback" role="status" aria-live="polite"></p></form><?php endif;?></section><aside class="lunch-summary"><span class="eyebrow">RESUMO</span><h2>Seu almoço</h2><p id="lunch-count-summary">1 quentinha</p><strong id="lunch-total">R$ <?=number_format((int)($settings['price_cents'] ?? 0) / 100, 2, ',', '.')?></strong><p class="lead">Preço por quentinha: R$ <?=number_format((int)($settings['price_cents'] ?? 0) / 100, 2, ',', '.')?>.</p><p class="lead">Entrega sujeita à confirmação do restaurante. Taxa de entrega não incluída neste valor.</p></aside></div></div>
<?php if ($items && (int)$settings['orders_enabled']):?><script type="application/json" id="lunch-config"><?=json_encode(['items' => $items,'settings' => ['price_cents' => (int)$settings['price_cents'],'max_choices' => (int)$settings['max_choices'],'limit_mode' => $settings['limit_mode'] ?? 'total','category_limits' => json_decode((string)($settings['category_limits'] ?? '{}'), true) ?: []], 'account' => ['authenticated' => (bool)$customerId, 'csrf' => \App\Customer::csrf(), 'addresses' => $customerAddresses, 'favorites' => $customerFavorites]], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)?></script><script src="/assets/js/lunch.js?v=7" defer></script><?php endif;?><dialog id="lunch-notice-dialog" class="lunch-notice-dialog" aria-labelledby="lunch-notice-title" aria-describedby="lunch-notice-text">
  <div class="lunch-notice-icon" aria-hidden="true"><i class="bi bi-info-circle"></i></div>
  <button type="button" class="lunch-notice-close" id="lunch-notice-close" aria-label="Fechar"><i class="bi bi-x-lg"></i></button>
  <h2 id="lunch-notice-title">Atenção</h2>
  <p id="lunch-notice-text"></p>
  <div id="lunch-notice-input-wrap" hidden>
    <label for="lunch-notice-input">Identificação</label>
    <input type="text" id="lunch-notice-input" maxlength="100" autocomplete="off">
  </div>
  <div class="lunch-notice-actions">
    <button type="button" id="lunch-notice-cancel" class="lunch-notice-secondary">Cancelar</button>
    <button type="button" id="lunch-notice-confirm" class="lunch-notice-primary">Entendi</button>
  </div>
</dialog>
<dialog id="lunch-account-dialog" class="lunch-account-dialog" aria-labelledby="lunch-account-title">
  <div class="lunch-account-head"><div><h2 id="lunch-account-title">Sua conta na Bia</h2><p>Salve seu endereço e acompanhe seus pedidos sem perder a montagem atual.</p></div><button type="button" id="lunch-close-account" aria-label="Fechar"><i class="bi bi-x-lg"></i></button></div>
  <div class="lunch-account-switch"><button type="button" data-account-mode="register" class="active">Criar conta</button><button type="button" data-account-mode="login">Já tenho conta</button></div>
  <form id="lunch-account-form">
    <label for="lunch-account-email">E-mail</label><input id="lunch-account-email" name="email" type="email" autocomplete="email" required>
    <label for="lunch-account-password">Senha</label><input id="lunch-account-password" name="password" type="password" minlength="10" maxlength="128" required autocomplete="new-password">
    <p class="lunch-account-help">Ao criar uma conta, escolha uma senha de pelo menos 10 caracteres.</p>
    <p id="lunch-account-feedback" role="status" aria-live="polite"></p>
    <button type="submit" class="btn" id="lunch-account-submit">Criar minha conta</button>
  </form>
</dialog>
<?php bia_footer();
