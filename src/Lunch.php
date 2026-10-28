<?php

declare(strict_types=1);

namespace App;

use DomainException;

final class Lunch
{
    public static function settings(): array
    {
        $s = Db::pdo()->query('SELECT price_cents,max_choices,limit_mode,category_limits,pix_enabled,card_enabled,orders_enabled FROM lunch_settings WHERE id=1')->fetch();
        if (!$s) {
            throw new DomainException('Execute a migração das quentinhas');
        }
        return $s;
    }
    public static function items(): array
    {
        return Db::pdo()->query('SELECT id,name,category FROM daily_menu_items WHERE active=1 AND by_weight=0 ORDER BY sort_order,id')->fetchAll();
    }
    public static function menu(): array
    {
        return Db::pdo()->query('SELECT id,name,category,by_weight,sort_order FROM daily_menu_items WHERE active=1 ORDER BY sort_order,id')->fetchAll();
    }
    public static function menuDate(): string
    {
        $v = Db::pdo()->query('SELECT updated_at FROM lunch_menu_meta WHERE id=1')->fetchColumn();
        return $v ? (new \DateTimeImmutable((string)$v))->format('d/m/Y') : '';
    }
    public static function touchMenu(): void
    {
        Db::pdo()->exec('INSERT INTO lunch_menu_meta(id,updated_at) VALUES(1,NOW()) ON DUPLICATE KEY UPDATE updated_at=NOW()');
    }
    public static function create(array $data): array
    {
        $s = self::settings();
        if (!(int)$s['orders_enabled']) {
            throw new DomainException('Pedidos temporariamente indisponíveis');
        }
        $method = (string)($data['payment_method'] ?? '');
        if (!in_array($method, ['pix','card'], true) || !(int)$s[$method.'_enabled']) {
            throw new DomainException('Forma de pagamento indisponível');
        }
        $name = trim((string)($data['customer_name'] ?? ''));
        $phone = preg_replace('/\D/', '', (string)($data['customer_phone'] ?? ''));
        $address = trim((string)($data['address'] ?? ''));
        $neighborhood = trim((string)($data['neighborhood'] ?? ''));
        $reference = trim((string)($data['reference_point'] ?? ''));
        $boxes = $data['boxes'] ?? [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 150 || strlen($phone) < 10 || strlen($phone) > 13 || mb_strlen($address) < 8 || mb_strlen($address) > 350 || mb_strlen($neighborhood) < 2 || mb_strlen($neighborhood) > 120 || mb_strlen($reference) < 2 || mb_strlen($reference) > 350 || !is_array($boxes) || count($boxes) < 1 || count($boxes) > 20 || !array_is_list($boxes)) {
            throw new DomainException('Confira nome, telefone, endereço, referência e quantidade de quentinhas');
        }
        $db = Db::pdo();
        $customerId = \App\Customer::id() ?: null;
        $available = [];
        foreach (self::items() as $item) {
            $available[(int)$item['id']] = $item['name'];
        }
        $mode = $s['limit_mode'] ?? 'total';
        $limits = json_decode((string)($s['category_limits'] ?? '{}'), true) ?: [];
        $categoryById = [];
        foreach (self::items() as $item) {
            $categoryById[(int)$item['id']] = $item['category'];
        }
        $normalized = [];
        $boxNames = [];
        foreach ($boxes as $box) {
            if (!is_array($box)) {
                throw new DomainException('Quentinha inválida');
            }
            $boxName = trim((string)($box['name'] ?? ''));
            if (!mb_check_encoding($boxName, 'UTF-8') || mb_strlen($boxName) < 2 || mb_strlen($boxName) > 80) {
                throw new DomainException('Informe um nome de 2 a 80 caracteres para cada quentinha');
            }
            $boxNames[] = $boxName;
            $chosen = $box['items'] ?? [];
            if (!is_array($chosen) || !array_is_list($chosen) || count($chosen) < 1 || ($mode === 'total' && count($chosen) > (int)$s['max_choices'])) {
                throw new DomainException('Seleções da quentinha inválidas');
            }
            $ids = [];
            foreach ($chosen as $id) {
                $id = filter_var($id, FILTER_VALIDATE_INT);
                if (!$id || !isset($available[$id]) || isset($ids[$id])) {
                    throw new DomainException('Item repetido ou indisponível no cardápio');
                }$ids[$id] = $available[$id];
            }
            if ($mode === 'category') {
                $counts = [];
                foreach (array_keys($ids) as $id) {
                    $cat = $categoryById[$id];
                    $counts[$cat] = ($counts[$cat] ?? 0) + 1;
                }foreach ($counts as $cat => $count) {
                    if ($count > (int)($limits[$cat] ?? 0)) {
                        throw new DomainException('Limite da categoria '.$cat.' excedido');
                    }
                }
            }
            $normalized[] = $ids;
        }
        $unit = (int)$s['price_cents'];
        $total = $unit * count($normalized);
        $db->beginTransaction();
        try {
            $q = $db->prepare('INSERT INTO lunch_orders(customer_id,customer_name,customer_phone,address,neighborhood,reference_point,payment_method,quantity,unit_price_cents,total_cents) VALUES(?,?,?,?,?,?,?,?,?,?)');
            $q->execute([$customerId,$name,$phone,$address,$neighborhood,$reference,$method,count($normalized),$unit,$total]);
            $orderId = (int)$db->lastInsertId();
            $boxInsert = $db->prepare('INSERT INTO lunch_order_boxes(order_id,box_number,box_name) VALUES(?,?,?)');
            $itemInsert = $db->prepare('INSERT INTO lunch_order_choices(box_id,menu_item_id,item_name) VALUES(?,?,?)');
            foreach ($normalized as $index => $items) {
                $boxInsert->execute([$orderId,$index + 1,$boxNames[$index]]);
                $boxId = (int)$db->lastInsertId();
                foreach ($items as $id => $item) {
                    $itemInsert->execute([$boxId,$id,$item]);
                }
            }
            \App\AdminNotification::create('order', $orderId, 'Novo pedido recebido', 'Pedido #'.$orderId.' enviado por '.$name, '/admin/lunch-orders.php');
            $db->commit();
            if (!$customerId) {
                Customer::rememberGuestOrder($orderId);
            }
            return ['order_id' => $orderId,'total_cents' => $total,'quantity' => count($normalized),'status' => 'pending','payment_status' => 'pending'];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }throw $e;
        }
    }
}
