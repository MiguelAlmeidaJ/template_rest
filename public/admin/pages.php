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
<div class="cms-page-heading"><div><span class="page-kicker">CONTEÚDO DO SITE</span><h1 class="page-title">Gerenciar páginas</h1><p class="page-subtitle">Personalize os textos e as fotografias exibidos no site.</p></div><a class="btn btn-outline-secondary" href="<?=adm_h(['inicio'=>'/','cardapio'=>'/cardapio/','o-restaurante'=>'/o-restaurante/','galeria'=>'/galeria/','contato'=>'/contato/'][$page])?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-2"></i>Ver página</a></div>
<?php cms_feedback();?>
<div class="cms-editor-layout">
 <aside class="content-card cms-page-selector"><div class="cms-sidebar-heading">PÁGINAS DO SITE <span><?=count($defs)?></span></div><nav class="cms-pages-nav"><?php foreach($defs as $slug=>$item):?><a class="<?=$slug===$page?'selected':''?>" href="?page=<?=urlencode($slug)?>"><i class="bi <?=adm_h(['inicio'=>'bi-house','cardapio'=>'bi-journal-text','o-restaurante'=>'bi-shop','galeria'=>'bi-images','contato'=>'bi-envelope'][$slug])?>"></i><span><?=adm_h($item['label'])?></span><i class="bi bi-chevron-right"></i></a><?php endforeach;?></nav></aside>
 <div class="cms-editor-main"><section class="content-card mb-4"><div class="content-heading"><div><span class="page-kicker">EDIÇÃO DE CONTEÚDO</span><h2 class="mt-2"><?=adm_h($def['label'])?></h2></div><span class="badge text-bg-secondary">Textos</span></div><div class="cms-form"><form method="post"><input type="hidden" name="csrf" value="<?=adm_h($csrf)?>"><input type="hidden" name="action" value="save"><div class="cms-fields"><?php foreach($def['fields'] as $key=>[$label,$default]):?><div class="cms-field"><label class="form-label fw-semibold" for="field_<?=adm_h($key)?>"><?=adm_h($label)?></label><textarea class="form-control" rows="<?=mb_strlen($default)>135?4:2?>" id="field_<?=adm_h($key)?>" name="field_<?=adm_h($key)?>" maxlength="3000"><?=adm_h(Cms::field($page,$key,$default))?></textarea></div><?php endforeach;?></div><div class="cms-form-footer"><span>As alterações são exibidas no site após salvar.</span><button class="btn btn-bia" type="submit"><i class="bi bi-check2-circle me-2"></i>Salvar alterações</button></div></form></div></section>
 <section class="content-card"><div class="content-heading"><div><span class="page-kicker">MÍDIA DA PÁGINA</span><h2 class="mt-2">Imagens</h2></div><span class="badge text-bg-secondary"><?=count($def['images'])?> áreas</span></div><div class="cms-form"><?php if(!$def['images']):?><p class="text-secondary">As fotografias desta página são gerenciadas na <a href="/admin/gallery.php">Galeria</a>.</p><?php endif;?><div class="cms-image-grid"><?php foreach($def['images'] as $slot=>$label):?><form method="post" enctype="multipart/form-data" class="cms-image-tile"><input type="hidden" name="csrf" value="<?=adm_h($csrf)?>"><input type="hidden" name="action" value="image"><input type="hidden" name="slot" value="<?=adm_h($slot)?>"><h3><?=adm_h($label)?></h3><?php $current=Cms::image($page,$slot);?><div class="cms-image-preview"><?php if($current):?><img src="<?=adm_h($current)?>" alt="Imagem atual de <?=adm_h($label)?>"><?php else:?><i class="bi bi-image"></i><span>Imagem demonstrativa ou padrão</span><?php endif;?></div><label class="form-label" for="upload-<?=adm_h($slot)?>">Nova fotografia</label><input id="upload-<?=adm_h($slot)?>" class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp" required><button class="btn btn-outline-secondary btn-sm mt-3" type="submit">Atualizar imagem</button></form><?php endforeach;?></div></div></section></div>
</div><?php adm_end();