<?php
declare(strict_types=1);
require __DIR__.'/includes/cms-boot.php';
use App\Cms;
use App\Db;

$defs = Cms::definitions();
$page = (string)($_GET['page'] ?? 'inicio');
if (!array_key_exists($page, $defs)) {
    $page = 'inicio';
}
$def = $defs[$page];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_csrf();
    try {
        if (($_POST['action'] ?? '') === 'save') {
            foreach ($def['fields'] as $key => [$label,$default]) {
                $value = trim((string)($_POST['field_'.$key] ?? ''));
                if (mb_strlen($value) > 3000) {
                    throw new DomainException('Texto excede 3000 caracteres');
                }
                Cms::saveField($page, $key, $value);
            }
            cms_notice('Textos atualizados com sucesso.');
        } elseif (($_POST['action'] ?? '') === 'image') {
            $slot = (string)($_POST['slot'] ?? '');
            if (!array_key_exists($slot, $def['images'])) {
                throw new DomainException('Área de imagem inválida');
            }
            $url = Cms::upload($_FILES['image'] ?? [], 'page-'.$page.'-'.$slot);
            $q = Db::pdo()->prepare('INSERT INTO cms_gallery(filename,caption,alt_text,is_visible,sort_order) VALUES(?,?,?,0,0)');
            $q->execute([$url,$def['label'].' · '.$def['images'][$slot],$def['images'][$slot].' do Restaurante da Bia']);
            Cms::saveField($page, 'image_'.$slot, $url);
            cms_notice('Imagem enviada, salva na biblioteca e vinculada à página.');
        } elseif (($_POST['action'] ?? '') === 'library') {
            $slot = (string)($_POST['slot'] ?? '');
            $id = filter_var($_POST['image_id'] ?? null, FILTER_VALIDATE_INT);
            if (!array_key_exists($slot, $def['images']) || !$id) {
                throw new DomainException('Seleção inválida');
            }
            $q = Db::pdo()->prepare('SELECT filename FROM cms_gallery WHERE id=?');
            $q->execute([$id]);
            $url = $q->fetchColumn();
            if (!$url) {
                throw new DomainException('Imagem não encontrada na biblioteca');
            }
            Cms::saveField($page, 'image_'.$slot, Cms::asset((string)$url));
            cms_notice('Imagem da biblioteca aplicada à página.');
        } else {
            throw new DomainException('Ação inválida');
        }
    } catch (\Throwable $e) {
        error_log((string)$e);
        cms_notice($e instanceof DomainException ? $e->getMessage() : 'Não foi possível salvar', 'danger');
    }
}
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
) {
    $success = ($GLOBALS['flashType'] ?? 'success') === 'success';
    $slot = (string) ($_POST['slot'] ?? '');

    header('Content-Type: application/json; charset=utf-8');
    http_response_code($success ? 200 : 422);
    echo json_encode([
        'ok' => $success,
        'message' => $GLOBALS['flash'] ?? '',
        'url' => $success && array_key_exists($slot, $def['images'])
            ? Cms::image($page, $slot)
            : '',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$library = Cms::gallery();
adm_start('Páginas', 'pages');
?>
<header class="cms-page-heading">
  <div>
    <span class="page-kicker">CONTEÚDO DO SITE</span>
    <h1 class="page-title">Páginas do site</h1>
    <p class="page-subtitle">Edite textos e fotos em um só lugar. As mudanças são publicadas ao salvar.</p>
  </div>
  <a class="btn btn-outline-secondary" href="<?=adm_h([
    'inicio' => '/', 'cardapio' => '/cardapio/', 'o-restaurante' => '/o-restaurante/',
    'galeria' => '/galeria/', 'contato' => '/contato/',
  ][$page])?>" target="_blank" rel="noopener noreferrer">
    <i class="bi bi-box-arrow-up-right me-2"></i> Visualizar página
  </a>
</header>
<?php cms_feedback(); ?>
<div class="cms-editor-layout">
  <aside class="content-card cms-page-selector" aria-label="Selecionar página">
    <div class="cms-sidebar-heading">PÁGINAS <span><?=count($defs)?></span></div>
    <nav class="cms-pages-nav">
      <?php foreach ($defs as $slug => $item): ?>
        <a class="<?=$slug === $page ? 'selected' : ''?>" href="?page=<?=urlencode($slug)?>">
          <i class="bi <?=adm_h([
            'inicio' => 'bi-house', 'cardapio' => 'bi-journal-text',
            'o-restaurante' => 'bi-shop', 'galeria' => 'bi-images',
            'contato' => 'bi-envelope',
          ][$slug])?>" aria-hidden="true"></i>
          <span><?=adm_h($item['label'])?></span>
          <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <div class="cms-editor-main">
    <section class="content-card cms-editor-panel">
      <div class="content-heading cms-panel-header">
        <div>
          <span class="page-kicker">TEXTOS DA PÁGINA</span>
          <h2><?=adm_h($def['label'])?></h2>
          <p><?=count($def['fields'])?> campos disponíveis para edição</p>
        </div>
      </div>
      <form class="cms-text-form" method="post">
        <input type="hidden" name="csrf" value="<?=adm_h($csrf)?>">
        <input type="hidden" name="action" value="save">
        <div class="cms-text-search">
          <i class="bi bi-search" aria-hidden="true"></i>
          <input type="search" id="cms-field-filter" placeholder="Buscar campo ou texto..." aria-label="Filtrar campos">
          <span id="cms-field-total"><?=count($def['fields'])?> campos</span>
        </div>
        <div class="cms-text-fields">
          <?php foreach ($def['fields'] as $key => [$label, $default]): ?>
            <?php $current = Cms::field($page, $key, $default); ?>
            <div class="cms-text-field" data-field-search="<?=adm_h(mb_strtolower($label.' '.$default))?>">
              <div class="cms-field-title">
                <label for="field_<?=adm_h($key)?>"><?=adm_h($label)?></label>
                <span class="cms-field-key"><?=adm_h($key)?></span>
              </div>
              <?php if (mb_strlen($default) > 110): ?>
                <textarea class="form-control" rows="3" maxlength="3000" id="field_<?=adm_h($key)?>" name="field_<?=adm_h($key)?>"><?=adm_h($current)?></textarea>
              <?php else: ?>
                <input class="form-control" type="text" maxlength="3000" id="field_<?=adm_h($key)?>" name="field_<?=adm_h($key)?>" value="<?=adm_h($current)?>">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <p id="cms-field-empty" class="cms-field-empty" hidden>Nenhum campo corresponde à sua busca.</p>
        <div class="cms-text-footer">
          <span id="cms-text-feedback" role="status" aria-live="polite">Nenhuma alteração pendente</span>
          <button type="submit" class="btn btn-bia" id="cms-save-texts" disabled>
            <i class="bi bi-check2-circle me-1"></i> Salvar textos
          </button>
        </div>
      </form>
    </section>
    <section class="content-card cms-editor-panel cms-images-panel">
      <div class="content-heading cms-panel-header">
        <div>
          <span class="page-kicker">IMAGENS DA PÁGINA</span>
          <h2>Fotografias</h2>
          <p>Utilize imagens da biblioteca ou envie novas fotografias.</p>
        </div>
        <span class="cms-photo-count"><?=count($def['images'])?></span>
      </div>
      <?php if (!$def['images']): ?>
        <p class="cms-images-empty">Esta página não usa imagens próprias. Gerencie as fotos em <a href="/admin/gallery.php">Galeria</a>.</p>
      <?php else: ?>
        <div class="cms-image-grid cms-image-grid-refined">
          <?php foreach ($def['images'] as $slot => $label): ?>
            <div class="cms-image-tile">
              <h3><?=adm_h($label)?></h3>
              <form method="post" enctype="multipart/form-data" class="js-image-upload">
                <input type="hidden" name="csrf" value="<?=adm_h($csrf)?>">
                <input type="hidden" name="action" value="image">
                <input type="hidden" name="slot" value="<?=adm_h($slot)?>">
                <?php $current = Cms::image($page, $slot); ?>
                <div class="cms-image-preview" data-preview>
                  <?php if ($current): ?>
                    <img src="<?=adm_h($current)?>" alt="Imagem atual de <?=adm_h($label)?>">
                  <?php else: ?>
                    <i class="bi bi-image" aria-hidden="true"></i>
                    <span>Fotografia padrão</span>
                  <?php endif; ?>
                </div>
                <label class="cms-file-action" for="upload-<?=adm_h($slot)?>">
                  <i class="bi bi-upload" aria-hidden="true"></i> Enviar nova foto
                </label>
                <input id="upload-<?=adm_h($slot)?>" class="cms-file-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
                <button class="btn btn-outline-secondary btn-sm cms-upload-submit" type="submit">Aplicar foto enviada</button>
                <div class="cms-upload-feedback" role="status" aria-live="polite"></div>
              </form>
              <button type="button" class="btn btn-outline-secondary btn-sm cms-library-open" data-library-slot="<?=adm_h($slot)?>">
                <i class="bi bi-images me-1" aria-hidden="true"></i> Escolher da biblioteca
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
<dialog id="cms-library-dialog" class="cms-library-dialog" aria-labelledby="cms-library-heading"><div class="cms-library-head"><div><h2 id="cms-library-heading">Biblioteca de imagens</h2><p>Escolha uma imagem existente, inclusive as ocultas da galeria pública.</p></div><button type="button" class="cms-library-close" aria-label="Fechar biblioteca"><i class="bi bi-x-lg"></i></button></div><div class="cms-library-toolbar"><input id="cms-library-search" type="search" placeholder="Buscar imagem..." aria-label="Buscar imagens"></div><div class="cms-library-grid"><?php foreach ($library as $photo):?><button type="button" class="cms-library-photo" data-image-id="<?=adm_h($photo['id'])?>" data-image-url="<?=adm_h($photo['filename'])?>" data-search="<?=adm_h(mb_strtolower($photo['caption'].' '.$photo['alt_text']))?>"><img src="<?=adm_h($photo['filename'])?>" alt="<?=adm_h($photo['alt_text'])?>" loading="lazy"><span><?=adm_h($photo['caption'] ?: $photo['alt_text'])?></span><small><?=$photo['is_visible'] ? 'Galeria pública' : 'Somente biblioteca'?></small></button><?php endforeach;?></div><p class="cms-library-empty" <?=$library ? 'hidden' : ''?>>Nenhuma imagem cadastrada. Envie uma fotografia para começar.</p><div class="cms-library-footer"><span id="cms-library-feedback" role="status" aria-live="polite"></span><button type="button" class="btn btn-outline-secondary cms-library-close">Cancelar</button></div></dialog>
<script src="/assets/js/cms-editor.js?v=3" defer></script><?php adm_end();
