<?php
declare(strict_types=1);
require __DIR__.'/includes/cms-boot.php';
use App\Db;

$db = Db::pdo();
$threads = $db->query("SELECT o.id,o.customer_name,MAX(m.created_at) last_message,COUNT(m.id) total_messages,SUM(CASE WHEN m.sender='customer' AND NOT EXISTS(SELECT 1 FROM lunch_messages r WHERE r.order_id=m.order_id AND r.sender='restaurant' AND r.id>m.id) THEN 1 ELSE 0 END) unanswered FROM lunch_orders o JOIN lunch_messages m ON m.order_id=o.id GROUP BY o.id,o.customer_name ORDER BY last_message DESC LIMIT 100")->fetchAll();
adm_start('Conversas', 'chats');?><div class="mb-4"><span class="page-kicker">ATENDIMENTO</span><h1 class="page-title">Conversas com clientes</h1><p class="page-subtitle">Mensagens vinculadas a pedidos. Entre na conversa para responder.</p></div><div class="content-card p-4"><div class="admin-alert-list"><?php if (!$threads):?><p class="text-secondary">Nenhuma conversa iniciada.</p><?php endif;?><?php foreach ($threads as $thread):?><a class="admin-alert-item" href="/admin/lunch-chat.php?order=<?=adm_h($thread['id'])?>"><div><strong>Pedido #<?=adm_h($thread['id'])?> · <?=adm_h($thread['customer_name'])?></strong><small><?=adm_h($thread['total_messages'])?> mensagens · Última mensagem: <?=adm_h($thread['last_message'])?></small></div><span class="<?=$thread['unanswered'] ? 'badge text-bg-warning' : 'badge text-bg-light'?>"><?=adm_h($thread['unanswered'])?> aguardando resposta</span></a><?php endforeach;?></div></div><script src="/assets/js/admin-chats-live.js?v=1" defer></script><?php adm_end();
