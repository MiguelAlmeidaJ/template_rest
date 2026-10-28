<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
use App\Api;
use App\Modules;

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
try {
    if ($path === '/' && $method === 'GET') {
        require __DIR__ . '/includes/site.php';
        bia_header('Início', 'Início'); ?>
        <section class="hero"><?php bia_photo('hero', 'Comida caseira servida em um ambiente acolhedor', '', false); ?>
            <div class="shell">
                <div class="hero-copy"><span class="eyebrow">O sabor de se sentir em casa</span>
                    <h1 class="display">Tempero de casa.<br><em>Sabor de verdade.</em></h1>
                    <p>Comida mineira, variedade todos os dias e aquele carinho que transforma o almoço em um momento especial.
                    </p>
                    <div class="actions"><a class="btn" href="/cardapio/">Conheça nosso cardápio ↗</a><a class="btn btn-outline"
                            href="/pedido/" target="_blank" rel="noopener noreferrer">Faça seu pedido</a></div>
                </div>
            </div>
        </section>
        <section class="hours-wrap shell" aria-label="Horário de atendimento">
            <div class="hours-card">
                <div class="hours-icon" aria-hidden="true">◷</div>
                <div class="hours-copy"><span class="hours-eyebrow">Venha almoçar com a gente</span>
                    <h2>Nosso horário de atendimento</h2>
                    <p>Todos os dias <strong>das 10h30 às 15h</strong></p>
                </div><a class="hours-link" href="/contato/">Como chegar <span aria-hidden="true">↗</span></a>
            </div>
        </section>
        <section class="section shell">
            <div class="section-header">
                <div><span class="eyebrow">Escolha o seu favorito</span>
                    <h2 class="title">Uma mesa cheia de sabores.</h2>
                </div>
                <p class="lead">Da tradição mineira às opções mais leves, aqui sempre tem algo gostoso esperando por você.</p>
            </div>
            <div class="cards"><a href="/cardapio/"
                    class="category-card"><?php bia_photo('meat', 'Seleção de carnes preparadas com carinho'); ?>
                    <div><span>DO NOSSO JEITO</span>
                        <h3>Comida mineira</h3><span>Conhecer o cardápio ↗</span>
                    </div>
                </a><a href="/cardapio/" class="category-card"><?php bia_photo('salad', 'Saladas e legumes variados'); ?>
                    <div><span>FRESCOR TODO DIA</span>
                        <h3>Saladas & acompanhamentos</h3><span>Conhecer o cardápio ↗</span>
                    </div>
                </a><a href="/cardapio/" class="category-card"><?php bia_photo('pasta', 'Pratos de massas'); ?>
                    <div><span>PARA TODOS OS GOSTOS</span>
                        <h3>Massas & especiais</h3><span>Conhecer o cardápio ↗</span>
                    </div>
                </a></div>
        </section>
        <section class="split"><?php bia_photo('interior', 'Interior de restaurante com mesas preparadas', 'split-photo'); ?>
            <div class="split-copy"><span class="eyebrow">Muito além do almoço</span>
                <h2 class="title">Um lugar para se reunir e saborear.</h2>
                <p class="lead">Ambiente amplo, climatizado e acolhedor, com espaço para 144 pessoas. Sinta-se à vontade: a casa
                    é sua.</p><a class="btn" href="/o-restaurante/">Conheça o restaurante ↗</a>
            </div>
        </section>
        <div class="shell home-amenities" aria-label="Comodidades do restaurante">
            <ul class="home-amenities-list">
                <li><span class="home-amenity-icon"><i class="bi bi-wind" aria-hidden="true"></i></span><span>Ambiente
                        climatizado</span></li>
                <li><span class="home-amenity-icon"><i class="bi bi-p-square" aria-hidden="true"></i></span><span>Estacionamento
                        grátis</span></li>
                <li><span class="home-amenity-icon"><i class="bi bi-universal-access"
                            aria-hidden="true"></i></span><span>Acessibilidade</span></li>
                <li><span class="home-amenity-icon"><i class="bi bi-wifi" aria-hidden="true"></i></span><span>Wi-Fi
                        grátis</span></li>
            </ul>
        </div>
        <section class="shell section home-order-section" aria-labelledby="home-order-title">
            <div class="home-order-feature">
                <div class="home-order-copy">
                    <span class="eyebrow">SEU ALMOÇO, DO SEU JEITO</span>
                    <h2 id="home-order-title">Comida de verdade.<br>Do nosso fogão para você.</h2>
                    <p>Escolha os pratos do dia, monte sua quentinha e aproveite o sabor da Bia onde estiver.</p>
                    <div class="home-order-actions">
                        <a class="btn home-order-button" href="/pedido/">Montar minha quentinha <i class="bi bi-arrow-up-right"
                                aria-hidden="true"></i></a>
                        <a class="home-order-menu-link" href="/cardapio/">Ver cardápio do dia <i class="bi bi-arrow-right"
                                aria-hidden="true"></i></a>
                    </div>
                </div>
                <div class="home-order-media">
                    <?php bia_photo('meat', 'Prato de comida caseira preparado pelo Restaurante da Bia', 'home-order-photo'); ?>
                </div>
            </div>
        </section>
        <?php bia_footer();

        exit;
    }
    if ($path === '/api/lunch/orders' && $method === 'POST') {
        Api::send(\App\Lunch::create(Api::input()), 201);
    }
    if ($path === '/api/health' && $method === 'GET') {
        Api::send(['status' => 'ok']);
    }
    if ($path === '/api/payments/options' && $method === 'GET') {
        Api::send(Modules::paymentOptions());
    }
    if ($path === '/api/products' && $method === 'GET') {
        Api::send(Modules::products());
    }
    if ($path === '/api/delivery/quote' && $method === 'POST') {
        Api::send(Modules::delivery(Api::input()));
    }
    if ($path === '/api/orders' && $method === 'POST') {
        Api::send(Modules::order(Api::input()), 201);
    }
    if ($path === '/api/payments/checkout' && $method === 'POST') {
        Api::send(Modules::checkout(Api::input()));
    }
    Api::send(['error' => 'Rota não encontrada'], 404);
} catch (DomainException $e) {
    Api::send(['error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    error_log((string) $e);
    Api::send(['error' => 'Erro interno'], 500);
}
