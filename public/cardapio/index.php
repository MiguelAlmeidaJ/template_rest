<?php require dirname(__DIR__).'/includes/site.php';
bia_header('Cardápio', 'Cardápio');?><section class="page-hero"><div class="shell"><span class="eyebrow">Sabores da nossa cozinha</span><h1 class="title">Tem sempre algo gostoso no cardápio.</h1><p class="lead">Comida mineira, carnes variadas, massas, saladas e sobremesas. Nossa seleção muda ao longo dos dias — confirme as opções de hoje pelo WhatsApp.</p><a class="btn btn-green" href="#daily-menu-title">Ver cardápio do dia ↗</a></div></section><?php $dailyItems = [];
$menuUpdated = '';
try {
    $dailyItems = \App\Lunch::menu();
    $menuUpdated = \App\Lunch::menuDate();
} catch (\Throwable $e) {
    error_log((string)$e);
} ?>
<section class="section shell daily-menu" aria-labelledby="daily-menu-title"><div class="daily-menu-heading"><span class="eyebrow">COMIDA FEITA COM CARINHO</span><h2 id="daily-menu-title" class="title">Cardápio do dia</h2><?php if ($menuUpdated):?><p>Última atualização: <?=bia_esc($menuUpdated)?></p><?php endif;?><p class="daily-menu-notice">Itens com * são vendidos por peso e não entram nas quentinhas.</p></div><?php if (!$dailyItems):?><p class="notice">O cardápio está sendo preparado. Consulte nossa equipe para verificar as opções.</p><?php else:?><div class="daily-menu-grid"><?php foreach (['Acompanhamentos','Carnes','Saladas','Tortas e assados'] as $category):?><article class="daily-menu-group"><h3><?=bia_esc($category)?></h3><ul><?php foreach ($dailyItems as $dish):?><?php if ($dish['category'] !== $category) {
    continue;
}?><li><?=bia_esc($dish['name'])?><?=$dish['by_weight'] ? '<sup title="Vendido por peso">*</sup>' : ''?></li><?php endforeach;?></ul></article><?php endforeach;?></div><?php endif;?><div class="daily-menu-order"><a class="btn" href="/pedido/">Monte sua quentinha ↗</a></div></section><section class="shell section"><div class="cta-panel"><h2 class="title">Quer saber o que tem hoje?</h2><p class="lead">Chame a equipe e confira as opções disponíveis para o seu almoço.</p><a class="btn" href="/pedido/">Faça seu pedido ↗</a></div></section><?php bia_footer();
