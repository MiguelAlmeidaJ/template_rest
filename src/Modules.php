<?php

declare(strict_types=1);

namespace App;

use App\Payments\Settings;
use DateTimeImmutable;
use DomainException;

final class Modules
{
    public static function products(): array
    {
        return Db::pdo()->query('SELECT p.id,p.name,p.description,p.price_cents,c.name category FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 ORDER BY c.id,p.name')->fetchAll();
    }
    public static function delivery(array $d): array
    {
        $subtotal = filter_var($d['subtotal_cents'] ?? null, FILTER_VALIDATE_INT);
        if (!$subtotal || $subtotal < 1) {
            throw new DomainException('Subtotal inválido');
        }
        $q = Db::pdo()->prepare('SELECT id,name,fee_cents,min_order_cents FROM delivery_zones WHERE LOWER(name)=LOWER(?) AND active=1');
        $q->execute([trim((string)($d['neighborhood'] ?? ''))]);
        $zone = $q->fetch();
        if (!$zone) {
            throw new DomainException('Área não atendida');
        }if ($subtotal < (int)$zone['min_order_cents']) {
            throw new DomainException('Pedido abaixo do mínimo');
        }
        return ['zone_id' => (int)$zone['id'],'fee_cents' => (int)$zone['fee_cents'],'total_cents' => $subtotal + (int)$zone['fee_cents']];
    }
    public static function paymentOptions(): array
    {
        $s = Settings::get();
        return ['mode' => $s['online_enabled'] ? 'online' : 'manual','provider' => $s['online_enabled'] ? $s['provider'] : null,'manual_methods' => ['card','pix','cash']];
    }
    public static function order(array $d): array
    {
        $name = trim((string)($d['customer_name'] ?? ''));
        $phone = preg_replace('/\D+/', '', (string)($d['customer_phone'] ?? ''));
        $mode = $d['fulfillment'] ?? 'pickup';
        $items = $d['items'] ?? [];
        if (mb_strlen($name) < 2 || strlen($phone) < 10 || !in_array($mode, ['pickup','delivery'], true) || !is_array($items) || !count($items) || count($items) > 50) {
            throw new DomainException('Pedido inválido');
        }
        $ids = [];
        foreach ($items as $item) {
            $id = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT);
            $qty = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || !$qty || $qty > 30 || isset($ids[$id])) {
                throw new DomainException('Itens inválidos ou duplicados');
            }$ids[$id] = $qty;
        }
        $settings = Settings::get();
        $paymentMode = $settings['online_enabled'] ? 'online' : 'manual';
        $manualMethod = (string)($d['manual_payment_method'] ?? '');
        if ($paymentMode === 'manual' && !in_array($manualMethod, ['card','pix','cash'], true)) {
            throw new DomainException('Selecione a forma de pagamento manual');
        }
        $db = Db::pdo();
        $db->beginTransaction();
        try {
            $subtotal = 0;
            $lines = [];
            $q = $db->prepare('SELECT price_cents FROM products WHERE id=? AND active=1');
            foreach ($ids as $id => $qty) {
                $q->execute([$id]);
                $product = $q->fetch();
                if (!$product) {
                    throw new DomainException('Produto indisponível');
                }$price = (int)$product['price_cents'];
                $subtotal += $price * $qty;
                $lines[] = [$id,$qty,$price];
            }
            $fee = 0;
            $zone = null;
            $address = null;
            if ($mode === 'delivery') {
                $address = trim((string)($d['address'] ?? ''));
                if (mb_strlen($address) < 8) {
                    throw new DomainException('Endereço inválido');
                }$quote = self::delivery(['neighborhood' => $d['neighborhood'] ?? '','subtotal_cents' => $subtotal]);
                $fee = $quote['fee_cents'];
                $zone = $quote['zone_id'];
            }
            $q = $db->prepare('INSERT INTO orders(customer_name,customer_phone,fulfillment,address,zone_id,subtotal_cents,delivery_fee_cents,total_cents,payment_mode,manual_payment_method) VALUES(?,?,?,?,?,?,?,?,?,?)');
            $q->execute([$name,$phone,$mode,$address,$zone,$subtotal,$fee,$subtotal + $fee,$paymentMode,$paymentMode === 'manual' ? $manualMethod : null]);
            $id = (int)$db->lastInsertId();
            $q = $db->prepare('INSERT INTO order_items(order_id,product_id,quantity,unit_price_cents) VALUES(?,?,?,?)');
            foreach ($lines as $line) {
                $q->execute([$id,...$line]);
            }
            AdminNotification::create('order', $id, 'Novo pedido recebido', 'Pedido #'.$id.' enviado por '.$name, '/admin/');
            $db->commit();
            return ['order_id' => $id,'total_cents' => $subtotal + $fee,'payment_status' => 'pending','status' => 'pending','payment_mode' => $paymentMode];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }throw $e;
        }
    }
    public static function slots(string $date): array
    {
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$dt || $dt->format('Y-m-d') !== $date) {
            throw new DomainException('Data inválida');
        }
        $q = Db::pdo()->prepare("SELECT s.id,s.time,s.capacity-COALESCE(SUM(IF(r.status IN ('pending','confirmed'),r.guests,0)),0) available FROM reservation_slots s LEFT JOIN reservations r ON r.slot_id=s.id WHERE s.date=? AND s.enabled=1 GROUP BY s.id,s.time,s.capacity ORDER BY s.time");
        $q->execute([$date]);
        return $q->fetchAll();
    }
    public static function reservation(array $d): array
    {
        $name = trim((string)($d['customer_name'] ?? ''));
        $phone = preg_replace('/\D+/', '', (string)($d['customer_phone'] ?? ''));
        $date = (string)($d['date'] ?? '');
        $time = (string)($d['time'] ?? '');
        $guests = filter_var($d['guests'] ?? null, FILTER_VALIDATE_INT);
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$date $time");
        if (mb_strlen($name) < 2 || strlen($phone) < 10 || !$guests || $guests > 12 || !$dt || $dt->format('Y-m-d H:i') !== "$date $time" || $dt <= new DateTimeImmutable()) {
            throw new DomainException('Reserva inválida');
        }
        $db = Db::pdo();
        $db->beginTransaction();
        try {
            $q = $db->prepare('SELECT id,capacity FROM reservation_slots WHERE date=? AND time=? AND enabled=1 FOR UPDATE');
            $q->execute([$date,$time]);
            $slot = $q->fetch();
            if (!$slot) {
                throw new DomainException('Horário indisponível');
            }
            $q = $db->prepare("SELECT COALESCE(SUM(guests),0) FROM reservations WHERE slot_id=? AND status IN ('pending','confirmed')");
            $q->execute([$slot['id']]);
            if ((int)$q->fetchColumn() + $guests > (int)$slot['capacity']) {
                throw new DomainException('Sem vagas');
            }
            $q = $db->prepare('INSERT INTO reservations(slot_id,customer_name,customer_phone,guests) VALUES(?,?,?,?)');
            $q->execute([$slot['id'],$name,$phone,$guests]);
            $id = (int)$db->lastInsertId();
            AdminNotification::create('reservation', $id, 'Nova reserva recebida', 'Reserva #'.$id.' solicitada por '.$name, '/admin/#reservas');
            $db->commit();
            return ['reservation_id' => $id,'status' => 'pending'];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }throw $e;
        }
    }
    public static function checkout(array $d): array
    {
        $id = filter_var($d['order_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            throw new DomainException('Pedido inválido');
        }
        $q = Db::pdo()->prepare('SELECT total_cents,payment_mode,payment_status FROM orders WHERE id=?');
        $q->execute([$id]);
        $o = $q->fetch();
        if (!$o) {
            throw new DomainException('Pedido não encontrado');
        }
        if ($o['payment_mode'] !== 'online') {
            throw new DomainException('O pagamento deste pedido é manual');
        }
        if ($o['payment_status'] !== 'pending') {
            throw new DomainException('Pedido não está pendente');
        }
        $settings = Settings::get();
        if (!$settings['online_enabled']) {
            throw new DomainException('Gateway online desativado');
        }
        $gateway = $settings['provider'] === 'mercadopago' ? new \App\Payments\MercadoPago() : new \App\Payments\PagBank();
        return $gateway->createCheckout($id, (int)$o['total_cents']);
    }
}
