<?php
declare(strict_types=1);
require __DIR__.'/includes/cms-boot.php';
use App\Cms;
$defs=Cms::definitions();$page=(string)($_GET['page']??'inicio');
if(!array_key_exists($page,$defs))$page='inicio';
$def=$defs[$page];
if($_SERVER['REQUEST_METHOD']==='POST'){
 cms_csrf();
 try{
  if(($_POST['action']??'')==='save'){
   foreach($def['fields'] as $key=>[$label,$default]){
    $value=trim((string)($_POST['field_'.$key]??''));
    if(mb_strlen($value)>3000)throw new DomainException('Texto excede 3000 caracteres');
    Cms::saveField($page,$key,$value);
   }
   cms_notice('Textos salvos.');
  }elseif(($_POST['action']??'')==='image'){
   $slot=(string)($_POST['slot']??'');
   if(!array_key_exists($slot,$def['images']))throw new DomainException('Área de imagem inválida');
   $url=Cms::upload($_FILES['image']??[],'page-'.$page.'-'.$slot);
   Cms::saveField($page,'image_'.$slot,$url);
   cms_notice('Imagem enviada e vinculada à página.');
  }else throw new DomainException('Ação inválida');
 }catch(\Throwable $e){error_log((string)$e);cms_notice($e instanceof DomainException?$e->getMessage():'Não foi possível salvar','danger');}
}
adm_start('Páginas','pages');?>
<div class="mb-4"><span class="page-kicker">CONTEÚDO DO SITE</span><h1 class="page-title">Páginas</h1><p class="page-subtitle">Altere os títulos, descrições e fotografias de cada página pública.</p></div>
<?php cms_feedback();?>
<div class="row g-4"><div class="col-lg-4"><section class="content-card p-3"><h2 class="h6 fw-bold mb-3">Selecionar página</h2><div class="list-group"><?php foreach($defs as $slug=>$item):?><a class="list-group-item list-group-item-action <?=$slug===$page?'active':''?>" href="?page=<?=urlencode($slug)?>"><?=adm_h($item['label'])?></a><?php endforeach;?></div></section></div><div class="col-lg-8"><section class="content-card mb-4"><div class="content-heading"><h2>Editar: <?=adm_h($def['label'])?></h2></div><div class="p-4"><form method="post"><input type="hidden" name="csrf" value="<?=adm_h($csrf)?>"><input type="hidden" name="action" value="save"><?php foreach($def['fields'] as $key=>[$label,$default]):?><label class="form-label fw-semibold" for="field_<?=adm_h($key)?>"><?=adm_h($label)?></label><textarea class="form-control mb-3" rows="<?=strlen($default)>130?4:2?>" id="field_<?=adm_h($key)?>" name="field_<?=adm_h($key)?>" maxlength="3000"><?=adm_h(Cms::field($page,$key,$default))?></textarea><?php endforeach;?><button class="btn btn-bia" type="submit">Salvar textos</button></form></div></section><section class="content-card"><div class="content-heading"><h2>Imagens desta página</h2></div><div class="p-4"><?php if(!$def['images']):?><p class="text-secondary">As imagens desta página são administradas pela <a href="/admin/gallery.php">Galeria</a>.</p><?php endif;?><?php foreach($def['images'] as $slot=>$label):?><form method="post" enctype="multipart/form-data" class="mb-4 border-bottom pb-3"><input type="hidden" name="csrf" value="<?=adm_h($csrf)?>"><input type="hidden" name="action" value="image"><input type="hidden" name="slot" value="<?=adm_h($slot)?>"><div class="fw-semibold mb-2"><?=adm_h($label)?></div><?php $current=Cms::image($page,$slot);if($current):?><img src="<?=adm_h($current)?>" alt="" style="width:180px;height:110px;object-fit:cover;border-radius:8px" class="mb-2"><?php endif;?><input class="form-control mb-2" type="file" name="image" accept="image/jpeg,image/png,image/webp" required><button class="btn btn-outline-secondary btn-sm" type="submit">Enviar imagem</button></form><?php endforeach;?></div></section></div></div><?php adm_end();