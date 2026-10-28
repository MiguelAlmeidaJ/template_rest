<?php
declare(strict_types=1);
require __DIR__.'/includes/cms-boot.php';
adm_start('Configurações', 'settings');?>
<div class="mb-4"><span class="page-kicker">PREFERÊNCIAS DO RESTAURANTE</span><h1 class="page-title">Configurações</h1><p class="page-subtitle">Gerencie a identidade da marca e as opções financeiras em um só lugar.</p></div>
<div class="settings-links-grid"><a class="settings-link" href="/admin/restaurant-info.php"><span class="settings-link-icon"><i class="bi bi-shop"></i></span><span><strong>Informações do restaurante</strong><small>Endereço, horário, telefone e redes sociais</small></span><i class="bi bi-chevron-right"></i></a>
<a class="settings-link" href="/admin/identity.php"><span class="settings-link-icon"><i class="bi bi-palette"></i></span><span><strong>Identidade visual</strong><small>Logotipo, favicon e apresentação da marca</small></span><i class="bi bi-chevron-right"></i></a>
<a class="settings-link" href="/admin/payment-methods.php"><span class="settings-link-icon"><i class="bi bi-wallet2"></i></span><span><strong>Formas de pagamento</strong><small>Pix e cartão no momento da entrega</small></span><i class="bi bi-chevron-right"></i></a>
<a class="settings-link" href="/admin/payments.php"><span class="settings-link-icon"><i class="bi bi-credit-card-2-front"></i></span><span><strong>Pagamentos</strong><small>Configurações e acompanhamento financeiro</small></span><i class="bi bi-chevron-right"></i></a>
</div><?php adm_end();
