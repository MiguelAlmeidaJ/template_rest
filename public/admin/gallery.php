<?php
declare(strict_types=1);
require __DIR__.'/includes/cms-boot.php';
use App\Cms;
use App\Db;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_csrf();
    try {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'upload') {
            $url = Cms::upload($_FILES['image'] ?? [], 'gallery');
            $caption = mb_substr(trim((string)($_POST['caption'] ?? '')), 0, 250);
            $alt = mb_substr(trim((string)($_POST['alt_text'] ?? '')), 0, 250);
            if ($alt === '') {
                throw new DomainException('Informe um texto alternativo para a imagem');
            }
            $q = Db::pdo()->prepare('INSERT INTO cms_gallery(filename,caption,alt_text,is_visible,sort_order) VALUES(?,?,?,?,?)');
            $q->execute([$url,$caption,$alt,isset($_POST['is_visible']) ? 1 : 0,0]);
            cms_notice('Imagem adicionada à galeria.');
        } elseif ($action === 'update') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id) {
                throw new DomainException('Imagem inválida');
            }
            $caption = mb_substr(trim((string)($_POST['caption'] ?? '')), 0, 250);
            $alt = mb_substr(trim((string)($_POST['alt_text'] ?? '')), 0, 250);
            if ($alt === '') {
                throw new DomainException('Texto alternativo obrigatório');
            }
            $q = Db::pdo()->prepare('UPDATE cms_gallery SET caption=?,alt_text=?,is_visible=?,sort_order=? WHERE id=?');
            $q->execute([$caption,$alt,isset($_POST['is_visible']) ? 1 : 0,max(-10000, min(10000, (int)($_POST['sort_order'] ?? 0))),$id]);
            cms_notice('Imagem atualizada.');
        } else {
            throw new DomainException('Ação inválida');
        }
    } catch (\Throwable $e) {
        error_log((string)$e);
        cms_notice($e instanceof DomainException ? $e->getMessage() : 'Erro ao processar imagem', 'danger');
    }
}
$photos = Cms::gallery();
adm_start('Galeria', 'gallery');?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4"><div><span class="page-kicker">BIBLIOTECA DE MÍDIA</span><h1 class="page-title">Galeria</h1><p class="page-subtitle">Envie imagens, organize a ordem e escolha o que aparece no site.</p></div><a href="/galeria/" target="_blank" class="btn btn-outline-secondary">Ver galeria pública ↗</a></div><?php cms_feedback();?><section class="content-card mb-4"><div class="content-heading"><h2>Adicionar fotografia</h2></div><form class="p-4" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=adm_h($csrf)?>"><input type="hidden" name="action" value="upload"><div class="row g-3"><div class="col-12 col-lg-6"><label class="form-label">Arquivo de imagem</label><input class="form-control" type="file" name="image" accept="image/png,image/jpeg,image/webp" required></div><div class="col-12 col-lg-6"><label class="form-label">Legenda</label><input class="form-control" name="caption" maxlength="250"></div><div class="col-12 col-lg-6"><label class="form-label">Texto alternativo (acessibilidade)</label><input class="form-control" name="alt_text" maxlength="250" required></div><div class="col-12 col-lg-6 d-flex align-items-center"><label><input type="checkbox" name="is_visible" checked> Publicar na galeria</label></div></div><button class="btn btn-bia mt-3">Adicionar imagem</button></form></section><div class="cms-media-grid"><?php foreach ($photos as $photo):?><form class="content-card overflow-hidden" method="post"><img src="<?=adm_h($photo['filename'])?>" alt="<?=adm_h($photo['alt_text'])?>" class="cms-thumb"><div class="p-3"><input type="hidden" name="csrf" value="<?=adm_h($csrf)?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?=adm_h($photo['id'])?>"><label class="form-label">Legenda</label><input class="form-control mb-2" name="caption" value="<?=adm_h($photo['caption'])?>" maxlength="250"><label class="form-label">Texto alternativo</label><input class="form-control mb-2" name="alt_text" value="<?=adm_h($photo['alt_text'])?>" required><label class="form-label">Posição</label><input class="form-control mb-2" type="number" name="sort_order" value="<?=adm_h($photo['sort_order'])?>"><label class="d-block mb-3"><input type="checkbox" name="is_visible" <?=$photo['is_visible'] ? 'checked' : ''?>> Visível no site</label><button class="btn btn-bia btn-sm" type="submit">Salvar imagem</button></div></form><?php endforeach;?></div><?php if (!$photos):?><div class="content-card empty-state">Nenhuma imagem enviada. Adicione a primeira fotografia acima.</div><?php endif;?><?php adm_end();
