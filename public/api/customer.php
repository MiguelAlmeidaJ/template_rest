<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/src/bootstrap.php';

use App\Customer;
use App\CustomerPreferences;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

try {
    Customer::boot();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(405, ['ok' => false,'error' => 'Método não permitido.']);
    }
    $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data)) {
        throw new DomainException('Dados inválidos.');
    }
    if (!hash_equals(Customer::csrf(), (string)($data['csrf'] ?? ''))) {
        respond(403, ['ok' => false,'error' => 'Sessão expirada. Atualize a página.']);
    }
    $action = (string)($data['action'] ?? '');
    if ($action === 'register') {
        Customer::register((string)($data['email'] ?? ''), (string)($data['password'] ?? ''));
    } elseif ($action === 'login') {
        Customer::login((string)($data['email'] ?? ''), (string)($data['password'] ?? ''));
    } elseif ($action === 'save_address') {
        if (!Customer::id()) {
            respond(401, ['ok' => false,'error' => 'Entre na sua conta.']);
        }
        CustomerPreferences::saveAddress(Customer::id(), $data);
    } elseif ($action === 'delete_address') {
        if (!Customer::id()) {
            respond(401, ['ok' => false,'error' => 'Entre na sua conta.']);
        }
        CustomerPreferences::deleteAddress(Customer::id(), (int)($data['id'] ?? 0));
    } elseif ($action === 'save_favorite') {
        if (!Customer::id()) {
            respond(401, ['ok' => false,'error' => 'Entre na sua conta.']);
        }
        CustomerPreferences::saveFavorite(Customer::id(), (string)($data['name'] ?? ''), (array)($data['boxes'] ?? []));
    } elseif ($action === 'delete_favorite') {
        if (!Customer::id()) {
            respond(401, ['ok' => false,'error' => 'Entre na sua conta.']);
        }
        CustomerPreferences::deleteFavorite(Customer::id(), (int)($data['id'] ?? 0));
    } else {
        throw new DomainException('Operação inválida.');
    }
    respond(200, [
        'ok' => true,
        'customer_id' => Customer::id(),
        'addresses' => CustomerPreferences::addresses(Customer::id()),
        'favorites' => CustomerPreferences::favorites(Customer::id()),
    ]);
} catch (DomainException $e) {
    respond(422, ['ok' => false,'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log((string)$e);
    respond(500,['ok' => false,'error' => 'Não foi possível concluir a solicitação.']);
}
