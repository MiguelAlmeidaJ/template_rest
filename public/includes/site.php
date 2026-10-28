<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
function bia_info(string $key, string $fallback = ''): string
{
    $value = \App\Cms::setting('restaurant_'.$key);
    return $value !== '' ? $value : $fallback;
}
function bia_whatsapp(): string
{
    $number = preg_replace('/\D/', '', bia_info('whatsapp', '5532987112340'));
    return 'https://wa.me/'.$number;
}
function bia_esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function bia_header(string $title, string $active): void
{
    $slug = ['Início' => 'inicio','Cardápio' => 'cardapio','O Restaurante' => 'o-restaurante','Galeria' => 'galeria','Contato' => 'contato'][$active] ?? 'inicio';
    $GLOBALS['bia_page_slug'] = $slug;
    ob_start();
    $menu = ['Início' => '/','Cardápio' => '/cardapio/','O Restaurante' => '/o-restaurante/','Galeria' => '/galeria/','Contato' => '/contato/'];
    $favicon = \App\Cms::setting('favicon');
    $logo = \App\Cms::setting('logo');
    ?><!doctype html><html lang="pt-BR"><head><?php if ($favicon):?><link rel="icon" href="<?=bia_esc($favicon)?>"><?php endif;?><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#153e35"><meta name="description" content="Restaurante da Bia em Além Paraíba, MG. Comida mineira, variedade no almoço e ambiente acolhedor. Conheça nosso cardápio e entre em contato."><title><?=bia_esc($title)?> | Restaurante da Bia</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet"><link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><link rel="stylesheet" href="/assets/css/bia.css?v=7"></head><body><a href="#conteudo" class="skip">Ir para conteúdo</a><div class="topline"><span><?=bia_esc(bia_info('name', 'Restaurante da Bia'))?> · <?=bia_esc(bia_info('city', 'Além Paraíba, MG'))?></span><a href="/contato/">Endereço e contato ↗</a></div><header class="site-header"><div class="shell header-inner"><a class="brand" href="/" aria-label="Restaurante da Bia, página inicial"><?php if ($logo):?><img src="<?=bia_esc($logo)?>" alt="Restaurante da Bia" class="custom-logo"><?php else:?><?php $footerLogo = \App\Cms::setting('logo');
        if ($footerLogo):?><img src="<?=bia_esc($footerLogo)?>" alt="Restaurante da Bia" class="custom-logo"><?php else:?><span class="brand-mark">B.</span><span>Restaurante <strong>da Bia</strong><small>COMIDA COM HISTÓRIA</small></span><?php endif;?><?php endif;?></a><button type="button" id="menu-toggle" class="menu-toggle" aria-label="Abrir menu" aria-expanded="false">☰</button><nav id="main-nav" class="nav" aria-label="Navegação principal"><?php foreach ($menu as $label => $href):?><a href="<?=$href?>" <?=$label === $active ? 'aria-current="page"' : ''?>><?=bia_esc($label)?></a><?php endforeach;?><a href="/cliente/" class="customer-nav-link"><i class="bi bi-person"></i><span>Minha conta</span></a><a class="btn btn-nav" href="/pedido/">Faça seu pedido <i class="bi bi-arrow-right" aria-hidden="true"></i></a></nav></div></header><main id="conteudo">
<?php }
function bia_footer(): void
{?>
</main>
<footer class="footer footer-light">
  <div class="shell footer-cols">
    <div class="footer-about">
      <a class="footer-logo-link" href="/" aria-label="Restaurante da Bia — início">
        <?php $footerLogo=\App\Cms::setting('logo'); ?>
        <?php if($footerLogo): ?>
          <img class="footer-logo" src="<?=bia_esc($footerLogo)?>" alt="Restaurante da Bia" loading="lazy">
        <?php else: ?>
          <span class="footer-brand-fallback">Restaurante <strong>da Bia</strong></span>
        <?php endif; ?>
      </a>
      <p>Uma mesa cheia de sabores e boas histórias em Além Paraíba.</p>
    </div>
    <nav class="footer-links" aria-label="Navegação do rodapé">
      <h3>Explore</h3>
      <a href="/cardapio/">Cardápio</a>
      <a href="/o-restaurante/">O Restaurante</a>
      <a href="/galeria/">Galeria</a>
      <a href="/contato/">Contato</a>
    </nav>
    <div class="footer-visit">
      <h3>Venha nos visitar</h3>
      <p><?=bia_esc(bia_info('address','Rua Dr. Sobral Pinto, 425'))?><br><?=bia_esc(bia_info('district','Vila Laroca'))?> · <?=bia_esc(bia_info('city','Além Paraíba, MG'))?></p>
      <p><i class="bi bi-clock" aria-hidden="true"></i> <?=bia_esc(bia_info('hours','Todos os dias · 10h30 às 15h'))?></p>
      <a href="<?=bia_esc(bia_whatsapp())?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp" aria-hidden="true"></i> <?=bia_esc(bia_info('phone','(32) 98711-2340'))?> <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </div>
  </div>
  <div class="shell footer-base">
    <span>© <?=date('Y')?> Restaurante da Bia. Todos os direitos reservados.</span>
    <span class="footer-credits">Desenvolvido por <a href="https://instagram.com/miguelalmeida.dev" target="_blank" rel="noopener noreferrer">Miguel Almeida</a> &amp; <a href="https://anoar.com.br" target="_blank" rel="noopener noreferrer">Anoar</a></span>
  </div>
</footer><a class="floating-order" href="/pedido/" target="_blank" rel="noopener noreferrer" aria-label="Fazer pedido">Fazer pedido ↗</a><script src="/assets/js/bia.js" defer></script></body></html>
<?php
$html = ob_get_clean();
    $slug = $GLOBALS['bia_page_slug'] ?? 'inicio';
    $defs = \App\Cms::definitions();
    foreach (($defs[$slug]['fields'] ?? []) as $key => [$label,$original]) {
        $saved = \App\Cms::field($slug, $key, $original);
        if ($saved !== $original) {
            $html = str_replace($original, bia_esc($saved), $html);
        }
    }
    echo $html;
}
function bia_photo(string $name, string $alt, string $class = '', bool $lazy = true): void
{?>
<?php $edited = \App\Cms::image($GLOBALS['bia_page_slug'] ?? 'inicio', $name);?><img class="<?=bia_esc($class)?>" src="<?=bia_esc($edited ?: '/assets/images/bia/'.$name.'.jpg')?>" <?=$edited ? '' : 'data-photo="'.bia_esc($name).'"'?> alt="<?=bia_esc($alt)?>" <?=$lazy ? 'loading="lazy"' : 'fetchpriority="high"'?> decoding="async">
<?php }
