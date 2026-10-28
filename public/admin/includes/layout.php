<?php
declare(strict_types=1);
function adm_h(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function adm_start(string $page, string $active = 'dashboard', bool $login = false): void
{?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?=adm_h($page)?> · Restaurante da Bia</title><?php $adminFavicon = \App\Cms::setting('favicon');
    $adminLogo = \App\Cms::setting('logo');
    if ($adminFavicon):?><link rel="icon" href="<?=adm_h($adminFavicon)?>"><?php endif;?><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyTSlDJQToswBJ4EyH+8aBaAZG7D" crossorigin="anonymous"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/css/admin.css?v=8"></head><body class="<?=$login ? 'auth-body' : 'admin-body'?>">
<?php if (!$login):?><div class="admin-shell"><aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar"><div class="offcanvas-header d-lg-none"><h5 class="offcanvas-title">Menu</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Fechar"></button></div><div class="offcanvas-body d-flex flex-column p-0"><a class="admin-brand" href="/admin/"><?php if ($adminLogo):?><img class="admin-custom-logo" src="<?=adm_h($adminLogo)?>" alt="Restaurante da Bia"><?php else:?><span class="brand-icon">B.</span><span>Restaurante <strong>da Bia</strong><small>PAINEL DE GESTÃO</small></span><?php endif;?></a><div class="sidebar-section">GERENCIAMENTO</div><nav class="side-nav">
<a class="<?=$active === 'dashboard' ? 'active' : ''?>" href="/admin/"><i class="bi bi-grid-1x2"></i> Visão Geral</a>
<a class="<?=$active === 'lunch-orders' ? 'active' : ''?>" href="/admin/lunch-orders.php"><i class="bi bi-bag-check"></i> Pedidos</a>

<a class="<?=$active === 'lunch' ? 'active' : ''?>" href="/admin/lunch.php"><i class="bi bi-journal-richtext"></i> Cardápio</a>
<?php if (\App\Staff::role() !== 'employee'):?>
<a class="<?=$active === 'gallery' ? 'active' : ''?>" href="/admin/gallery.php"><i class="bi bi-images"></i> Galeria</a>
<a class="<?=$active === 'customers' ? 'active' : ''?>" href="/admin/customers.php"><i class="bi bi-people"></i> Clientes</a>
<?php endif;?>
<?php if (\App\Staff::role() === 'admin'):?>
<a class="<?=$active === 'users' ? 'active' : ''?>" href="/admin/users.php"><i class="bi bi-person-gear"></i> Usuários</a>
<a class="<?=$active === 'pages' ? 'active' : ''?>" href="/admin/pages.php"><i class="bi bi-file-earmark-text"></i> Páginas</a>
<?php elseif (\App\Staff::role() === 'manager'):?>
<a class="<?=$active === 'employees' ? 'active' : ''?>" href="/admin/employees.php"><i class="bi bi-person-badge"></i> Funcionários</a>
<?php endif;?>
<?php if (\App\Staff::role() !== 'employee'):?>
<a class="<?=in_array($active, ['settings','identity','payments','payment-methods'], true) ? 'active' : ''?>" href="/admin/settings.php"><i class="bi bi-gear"></i> Configurações</a>
<?php endif;?></nav></div></aside><div class="admin-main"><div class="admin-topbar"><button class="btn btn-outline-secondary d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-label="Abrir navegação"><i class="bi bi-list"></i></button><span class="crumb">Restaurante da Bia <i class="bi bi-chevron-right mx-2"></i> <?=adm_h($page)?></span><div class="admin-top-actions"><a href="/admin/chats.php" class="admin-action-icon" title="Mensagens dos clientes" aria-label="Mensagens dos clientes"><i class="bi bi-chat-dots"></i><span class="admin-action-count" data-admin-chat-count hidden></span></a><a href="/admin/notifications.php" class="admin-action-icon" title="Novos pedidos" aria-label="Notificações de pedidos"><i class="bi bi-bell"></i><span class="admin-action-count" data-admin-alert-count hidden></span></a><div class="admin-account-dropdown">
<button class="admin-account-trigger" type="button" aria-label="Abrir menu da conta" aria-expanded="false" aria-controls="admin-account-menu" data-account-toggle><i class="bi bi-person-circle" aria-hidden="true"></i></button>
<div id="admin-account-menu" class="admin-account-menu" hidden>
  <div class="admin-account-info"><span>Minha conta</span><strong><?=adm_h(\App\Staff::current()['email'] ?? '')?></strong></div>
  <a href="/admin/profile.php"><i class="bi bi-key" aria-hidden="true"></i> Alterar minha senha</a>
  <form method="post" action="/admin/logout.php"><input type="hidden" name="csrf" value="<?=adm_h($_SESSION['csrf'] ?? '')?>"><button type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sair da conta</button></form>
</div>
</div></div></div><div class="admin-content"><?php endif;?>
<?php }
function adm_end(bool $login = false): void
{
    if (!$login):?></div></div></div><?php endif;?><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script><script>
document.addEventListener('DOMContentLoaded',function(){
 const menu=document.getElementById('adminSidebar');
 document.querySelectorAll('[data-bs-target="#adminSidebar"]').forEach(function(btn){
  btn.addEventListener('click',function(){if(!window.bootstrap){menu.classList.toggle('is-open');}});
 });
 const trigger=document.querySelector('[data-account-toggle]');
 const accountMenu=document.getElementById('admin-account-menu');
 if(trigger&&accountMenu){
  const close=()=>{accountMenu.hidden=true;trigger.setAttribute('aria-expanded','false')};
  trigger.addEventListener('click',()=>{const opening=accountMenu.hidden;accountMenu.hidden=!opening;trigger.setAttribute('aria-expanded',String(opening))});
  document.addEventListener('click',e=>{if(!trigger.parentElement.contains(e.target))close()});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'){close();trigger.focus()}});
 }
 document.querySelectorAll('.side-nav a').forEach(function(a){
  a.addEventListener('click',function(){menu?.classList.remove('is-open')});
 });
});
</script><?php if (!$login):?><script src="/assets/js/admin-alerts.js?v=1" defer></script><?php endif;?></body></html><?php }
function adm_status(string $status): string
{
    $labels = ['pending' => 'Pendente','accepted' => 'Aceito','preparing' => 'Em preparo','ready' => 'Pronto','dispatched' => 'Em entrega','completed' => 'Concluído','cancelled' => 'Cancelado','paid' => 'Pago','confirmed' => 'Confirmada','failed' => 'Falhou','refunded' => 'Reembolsado'];
    $colors = ['pending' => 'warning','accepted' => 'info','preparing' => 'info','ready' => 'primary','dispatched' => 'primary','completed' => 'success','paid' => 'success','confirmed' => 'success','cancelled' => 'danger','failed' => 'danger','refunded' => 'secondary'];
    return '<span class="badge rounded-pill text-bg-'.($colors[$status] ?? 'secondary').'">'.adm_h($labels[$status] ?? $status).'</span>';
}
