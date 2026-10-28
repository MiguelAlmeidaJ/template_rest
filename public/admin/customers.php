<?php
declare(strict_types=1);

require __DIR__ . '/includes/cms-boot.php';

use App\CustomerDirectory;

$search = CustomerDirectory::searchTerm($_GET['q'] ?? '');
$filter = CustomerDirectory::filter($_GET['filter'] ?? 'all');
$requestedPage = (int) ($_GET['page'] ?? 1);

$data = CustomerDirectory::paginate($search, $filter, $requestedPage);
$customers = $data['customers'];
$total = $data['total'];
$registered = $data['registered'];
$page = $data['page'];
$pages = $data['pages'];

function customers_page_url(int $page, string $search, string $filter): string
{
    return '?' . http_build_query(['q' => $search, 'filter' => $filter, 'page' => $page]);
}

adm_start('Clientes', 'customers');
?>

<header class="mb-4">
    <span class="page-kicker">RELACIONAMENTO</span>
    <h1 class="page-title">
        Clientes
        <small class="customers-total"><?= number_format($registered, 0, ',', '.') ?></small>
    </h1>
    <p class="page-subtitle">
        Consulte contas cadastradas, endereços e histórico de pedidos.
        Os dados são restritos ao administrativo.
    </p>
</header>

<section class="content-card">
    <div class="content-heading customers-heading">
        <h2>Lista de clientes</h2>
        <span class="text-secondary small">
            <?= number_format($total, 0, ',', '.') ?> resultado(s)
        </span>
    </div>

    <form class="customer-admin-filters" method="get" role="search">
        <label class="customer-admin-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input
                type="search"
                name="q"
                value="<?= adm_h($search) ?>"
                maxlength="100"
                placeholder="Buscar por e-mail, nome ou telefone"
                aria-label="Buscar clientes"
            >
        </label>

        <select name="filter" aria-label="Filtrar clientes">
            <?php foreach (CustomerDirectory::FILTERS as $value => $label): ?>
                <option value="<?= adm_h($value) ?>" <?= $filter === $value ? 'selected' : '' ?>>
                    <?= adm_h($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="btn btn-bia" type="submit">Filtrar</button>
        <a class="btn btn-outline-secondary" href="/admin/customers.php">Limpar</a>
    </form>

    <div class="table-responsive">
        <table class="table align-middle customers-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Contato</th>
                    <th>Pedidos</th>
                    <th>Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td>
                            <strong><?= adm_h($customer['recipient'] ?: 'Cliente #' . $customer['id']) ?></strong>
                            <small><?= adm_h($customer['email']) ?></small>
                        </td>
                        <td>
                            <?= adm_h($customer['phone'] ?: 'Não informado') ?>
                            <small><?= adm_h($customer['neighborhood'] ?: 'Endereço não cadastrado') ?></small>
                        </td>
                        <td>
                            <strong><?= adm_h($customer['orders_count']) ?> pedidos</strong>
                            <small>
                                <?= adm_h($customer['last_order'] ? 'Último: ' . $customer['last_order'] : 'Nenhum pedido') ?>
                            </small>
                        </td>
                        <td><?= adm_h(date('d/m/Y', strtotime($customer['created_at']))) ?></td>
                        <td>
                            <a
                                class="btn btn-outline-secondary btn-sm"
                                href="/admin/customer.php?id=<?= (int) $customer['id'] ?>"
                            >
                                Ver detalhes
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!$customers): ?>
            <div class="empty-state">
                <i class="bi bi-person-search" aria-hidden="true"></i>
                Nenhum cliente encontrado.
            </div>
        <?php endif; ?>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="customers-pagination" aria-label="Paginação de clientes">
            <?php if ($page > 1): ?>
                <a href="<?= adm_h(customers_page_url($page - 1, $search, $filter)) ?>">Anterior</a>
            <?php endif; ?>

            <span>Página <?= $page ?> de <?= $pages ?></span>

            <?php if ($page < $pages): ?>
                <a href="<?= adm_h(customers_page_url($page + 1, $search, $filter)) ?>">Próxima</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>

<?php adm_end(); ?>
