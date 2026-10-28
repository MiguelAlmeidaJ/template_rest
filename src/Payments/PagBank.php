<?php

declare(strict_types=1);

namespace App\Payments;

use DomainException;

final class PagBank implements Gateway
{
    public function createCheckout(int $orderId, int $totalCents): array
    {
        throw new DomainException('PagBank: integração real ainda não implementada');
    }
    public function verifyWebhook(string $payload, array $headers): array
    {
        throw new DomainException('Webhook PagBank ainda não implementado');
    }
}
