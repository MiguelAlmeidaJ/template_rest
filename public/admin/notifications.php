<?php
declare(strict_types=1);
require __DIR__.'/includes/cms-boot.php';
use App\AdminNotification;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_csrf();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id && ($_POST['action'] ?? '') === 'read') {
        AdminNotification::markRead($id);
        cms_notice('Notificação marcada como lida.');
    } else {
        cms_notice('Ação inválida.', 'danger');
    }
}
$notifications = AdminNotification::latest(150);
$icons = ['order' => 'bi-bag-check','chat' => 'bi-chat-dots','payment' => 'bi-credit-card','system' => 'bi-bell'];
adm_start('Notificações', 'notifications');
cms_feedback();?>
<div class="mb-4"><span class="page-kicker">CENTRAL DE ATIVIDADES</span><h1 class="page-title">Notificações</h1><p class="page-subtitle">Pedidos, mensagens e atividades do restaurante em uma única lista.</p></div>
<section class="content-card"><div class="content-heading"><h2>Todas as notificações</h2><span class="text-secondary small"><?=AdminNotification::unreadCount()?> não lidas</span></div>
<div class="notification-list"><?php if (!$notifications):?><div class="notification-empty"><i class="bi bi-bell-slash"></i><p>Nenhuma notificação no momento.</p></div><?php endif;?>
<?php foreach ($notifications as $n):?><article class="notification-item <?=(int)$n['is_read'] ? 'is-read' : 'is-unread'?>"><div class="notification-icon"><i class="bi <?=adm_h($icons[$n['type']] ?? 'bi-bell')?>"></i></div><div class="notification-body"><div class="notification-top"><h3><?=adm_h($n['title'])?></h3><time><?=adm_h($n['created_at'])?></time></div><p><?=adm_h($n['message'])?></p><div class="notification-actions"><a class="btn btn-sm btn-outline-secondary" href="<?=adm_h($n['link'])?>">Ver detalhes <i class="bi bi-arrow-up-right"></i></a><?php if (!(int)$n['is_read']):?><form method="post"><input type="hidden" name="csrf" value="<?=adm_h($csrf)?>"><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?=adm_h($n['id'])?>"><button class="btn btn-sm btn-outline-secondary notification-read" title="Marcar como lida" aria-label="Marcar notificação como lida"><i class="bi bi-check2"></i></button></form><?php endif;?></div></div></article><?php endforeach;?></div></section><script src="/assets/js/admin-notifications-live.js?v=1" defer></script><?php adm_end();
