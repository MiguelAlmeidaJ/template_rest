<?php
declare(strict_types=1);

require __DIR__ . '/includes/cms-boot.php';

use App\CustomerDirectory;

$customerId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$customerId) {
    header('Location: /admin/customers.php');
    exit;
}

$customer = CustomerDirectory::find($customerId);

if (!$customer) {
    http_response_code(404);
    adm_start('Cliente não encontrado', 'customers');
    ?>
    <h1 class="page-title">Cliente não encontrado</h1>
    <a href="/admin/customers.php">Voltar aos clientes</a>
    <?php
    adm_end();
    exit;
}

$orders = CustomerDirectory::orders($customerId);

adm_start('Detalhes do cliente', 'customers');
?>

<header class="mb-4">
    <a class="small" href="/admin/customers.php">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar aos clientes
    </a>
    <h1 class="page-title">
        <?= adm_h($customer['recipient'] ?: 'Cliente #' . $customer['id']) ?>
    </h1>
    <p class="page-subtitle"><?= adm_h($customer['email']) ?></p>
</header>

<div class="row g-4">
    <div class="col-lg-5">
        <section class="content-card p-4">
            <h2 class="h5">Dados cadastrados</h2>
            <dl class="customer-details">
                <?php
                $details = [
                    'E-mail' => $customer['email'],
                    'Telefone' => $customer['phone'] ?: 'Não informado',
                    'Endereço padrão' => $customer['address'] ?: 'Não informado',
                    'Bairro' => $customer['neighborhood'] ?: 'Não informado',
                    'Referência' => $customer['reference_point'] ?: 'Não informada',
                    'Cadastro' => date('d/m/Y H:i', strtotime($customer['created_at'])),
                ];
?>
                <?php foreach ($details as $label => $value): ?>
                    <dt><?= adm_h($label) ?></dt>
                    <dd><?= adm_h($value) ?></dd>
                <?php endforeach; ?>
            </dl>
        </section>
    </div>

    <div class="col-lg-7">
        <section class="content-card">
            <div class="content-heading">
                <h2>Histórico de quentinhas</h2>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Status</th>
                            <th>Quantidade</th>
                            <th>Valor</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= (int) $order['id'] ?></td>
                                <td><?= adm_h(CustomerDirectory::statusLabel($order['status'])) ?></td>
                                <td><?= (int) $order['quantity'] ?></td>
                                <td>R$ <?= number_format($order['total_cents'] / 100, 2, ',', '.') ?></td>
                                <td><?= adm_h(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (!$orders): ?>
                    <div class="empty-state">Nenhum pedido vinculado a esta conta.</div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php adm_end(); ?>
