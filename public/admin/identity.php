<?php
declare(strict_types=1);
require __DIR__.'/includes/cms-boot.php';
use App\Cms;
use App\Db;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_csrf();
    try {
        $slot = (string)($_POST['slot'] ?? '');
        if (!in_array($slot, ['logo','favicon'], true)) {
            throw new DomainException('Campo inválido');
        }
        $url = Cms::upload($_FILES['image'] ?? [], $slot);
        $q = Db::pdo()->prepare('INSERT INTO cms_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        $q->execute([$slot,$url]);
        cms_notice('Identidade visual atualizada.');
    } catch (\Throwable $e) {
        error_log((string)$e);
        cms_notice($e instanceof DomainException ? $e->getMessage() : 'Erro ao salvar imagem', 'danger');
    }
}
adm_start('Identidade visual', 'identity');?>
<div class="mb-4"><span class="page-kicker">PERSONALIZAÇÃO</span><h1 class="page-title">Identidade visual</h1><p class="page-subtitle">Ajuste a identidade visual exibida no site público.</p></div><?php cms_feedback();?><div class="content-card"><div class="content-heading"><h2>Identidade visual</h2></div><div class="p-4"><p class="text-secondary">A logo será exibida no cabeçalho e no rodapé. O favicon aparecerá na aba do navegador.</p><div class="row g-4"><?php foreach (['logo' => 'Logotipo','favicon' => 'Favicon'] as $slot => $label):?><div class="col-12 col-lg-6"><form method="post" enctype="multipart/form-data" class="border rounded p-3 h-100"><input type="hidden" name="csrf" value="<?=adm_h($csrf)?>"><input type="hidden" name="slot" value="<?=adm_h($slot)?>"><h3 class="h6"><?=adm_h($label)?></h3><?php $url = Cms::setting($slot);
    if ($url):?><img src="<?=adm_h($url)?>" alt="<?=adm_h($label)?>" style="max-height:100px;max-width:220px;object-fit:contain" class="mb-3 d-block"><?php endif;?><input type="file" name="image" class="form-control mb-3" accept="image/jpeg,image/png,image/webp" required><p class="small text-secondary">JPG, PNG ou WebP, até 5 MB. Para favicon, recomendamos imagem quadrada.</p><button class="btn btn-bia" type="submit">Atualizar <?=adm_h($label)?></button></form></div><?php endforeach;?></div></div></div><?php adm_end();
