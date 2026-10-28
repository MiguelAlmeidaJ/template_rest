<?php
declare(strict_types=1);
require __DIR__.'/includes/cms-boot.php';
use App\Db;
$db=Db::pdo();
$search=trim((string)($_GET['q']??''));
if(mb_strlen($search)>100)$search=mb_substr($search,0,100);
$filter=(string)($_GET['filter']??'all');if(!in_array($filter,['all','with_orders','no_orders'],true))$filter='all';
$where=[];$params=[];
if($search!==''){$where[]='(c.email LIKE ? OR a.recipient LIKE ? OR a.phone LIKE ?)';$term='%'.$search.'%';$params=[$term,$term,$term];}
if($filter==='with_orders')$where[]='COALESCE(o.orders_count,0)>0';
elseif($filter==='no_orders')$where[]='COALESCE(o.orders_count,0)=0';
$sql=' FROM customer_accounts c LEFT JOIN customer_addresses a ON a.customer_id=c.id LEFT JOIN (SELECT customer_id,COUNT(*) orders_count,MAX(created_at) last_order,COALESCE(SUM(CASE WHEN status<>"cancelled" THEN total_cents ELSE 0 END),0) total_spent FROM lunch_orders WHERE customer_id IS NOT NULL GROUP BY customer_id) o ON o.customer_id=c.id';
if($where)$sql.=' WHERE '.implode(' AND ',$where);
$q=$db->prepare('SELECT COUNT(*)'.$sql);$q->execute($params);$total=(int)$q->fetchColumn();
$page=max(1,(int)($_GET['page']??1));$pages=max(1,(int)ceil($total/25));$page=min($page,$pages);$offset=($page-1)*25;
$q=$db->prepare('SELECT c.id,c.email,c.created_at,a.recipient,a.phone,a.neighborhood,a.address,a.reference_point,COALESCE(o.orders_count,0) orders_count,o.last_order,COALESCE(o.total_spent,0) total_spent'.$sql.' ORDER BY c.created_at DESC,c.id DESC LIMIT 25 OFFSET '.$offset);$q->execute($params);$customers=$q->fetchAll();
$overview=$db->query('SELECT COUNT(*) total FROM customer_accounts')->fetchColumn();
adm_start('Clientes','customers');?>
<div class="mb-4"><span class="page-kicker">RELACIONAMENTO</span><h1 class="page-title">Clientes <span style="font-size: 50%;">(<?=number_format((int)$overview,0,',','.')?>)</span></h1><p class="page-subtitle">Consulte contas cadastradas, endereços e histórico de pedidos. Os dados são de acesso restrito ao administrativo.</p></div>
<section class="content-card"><div class="content-heading customers-heading"><h2>Lista de clientes</h2><span class="text-secondary small"><?=number_format($total,0,',','.')?> resultado(s)</span></div>
<form class="customer-admin-filters" method="get" role="search"><label class="customer-admin-search"><i class="bi bi-search"></i><input type="search" name="q" value="<?=adm_h($search)?>" maxlength="100" placeholder="Buscar por e-mail, nome ou telefone" aria-label="Buscar clientes"></label><select name="filter" aria-label="Filtrar clientes"><option value="all" <?=$filter==='all'?'selected':''?>>Todos os clientes</option><option value="with_orders" <?=$filter==='with_orders'?'selected':''?>>Com pedidos</option><option value="no_orders" <?=$filter==='no_orders'?'selected':''?>>Sem pedidos</option></select><button class="btn btn-bia" type="submit">Filtrar</button><a class="btn btn-outline-secondary" href="/admin/customers.php">Limpar</a></form>
<div class="table-responsive"><table class="table align-middle customers-table"><thead><tr><th>Cliente</th><th>Contato</th><th>Pedidos</th><th>Cadastro</th><th></th></tr></thead><tbody><?php foreach($customers as $c):?><tr><td><strong><?=adm_h($c['recipient']?:'Cliente #'.$c['id'])?></strong><small><?=adm_h($c['email'])?></small></td><td><?=adm_h($c['phone']?:'Não informado')?><small><?=adm_h($c['neighborhood']?:'Endereço não cadastrado')?></small></td><td><strong><?=adm_h($c['orders_count'])?> pedidos</strong><small><?=adm_h($c['last_order']?'Último: '.$c['last_order']:'Nenhum pedido')?></small></td><td><?=adm_h(date('d/m/Y',strtotime($c['created_at'])))?></td><td><a class="btn btn-outline-secondary btn-sm" href="/admin/customer.php?id=<?=adm_h($c['id'])?>">Ver detalhes</a></td></tr><?php endforeach;?></tbody></table><?php if(!$customers):?><div class="empty-state"><i class="bi bi-person-search"></i>Nenhum cliente encontrado.</div><?php endif;?></div>
<?php if($pages>1):?><nav class="customers-pagination" aria-label="Paginação de clientes"><?php if($page>1):?><a href="?<?=adm_h(http_build_query(['q'=>$search,'filter'=>$filter,'page'=>$page-1]))?>">Anterior</a><?php endif;?><span>Página <?=$page?> de <?=$pages?></span><?php if($page<$pages):?><a href="?<?=adm_h(http_build_query(['q'=>$search,'filter'=>$filter,'page'=>$page+1]))?>">Próxima</a><?php endif;?></nav><?php endif;?></section><?php adm_end();