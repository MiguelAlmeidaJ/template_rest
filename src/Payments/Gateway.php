<?php

declare(strict_types=1);

namespace App\Payments;

interface Gateway
{
    public function createCheckout(int $orderId, int $totalCents): array;
    public function verifyWebhook(string $payload, array $headers): array;
}
