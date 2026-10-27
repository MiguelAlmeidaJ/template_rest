<?php
declare(strict_types=1);
namespace App\Payments;
use DomainException;
final class MercadoPago implements Gateway{
 public function createCheckout(int $orderId,int $totalCents):array{throw new DomainException('Mercado Pago: integração real ainda não implementada');}
 public function verifyWebhook(string $payload,array $headers):array{throw new DomainException('Webhook Mercado Pago ainda não implementado');}
}
