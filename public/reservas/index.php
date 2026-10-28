<?php
http_response_code(404);
require dirname(__DIR__) . '/includes/site.php';
bia_header('Página não encontrada', '');
?>
<section class="shell section"><h1 class="title">Página não encontrada</h1><p>Não oferecemos reservas de mesa. Conheça nosso cardápio ou faça seu pedido.</p><a class="btn" href="/cardapio/">Ver cardápio</a></section>
<?php bia_footer();
