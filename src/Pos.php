<?php

declare(strict_types=1);

namespace App;

use DomainException;
use PDO;

final class Pos
{
    public const STATUS = [
        'pending' => 'Novo pedido',
        'accepted' => 'Novo pedido',
        'preparing' => 'Em preparação',
        'out_for_delivery' => 'Saiu para entrega',
        'delivered' => 'Entregue',
        'cancelled' => 'Cancelado',
    ];

    public const NEXT = [
        'pending' => 'preparing',
        'accepted' => 'preparing',
        'preparing' => 'out_for_delivery',
        'out_for_delivery' => 'delivered',
    ];

    public static function board(): array
    {
        $db = Db::pdo();
        $orders = $db->query(
            "SELECT id,customer_name,customer_phone,address,neighborhood,reference_point,
                    quantity,total_cents,payment_method,payment_status,status,created_at
             FROM lunch_orders
             WHERE status IN ('pending','accepted','preparing','out_for_delivery')
             ORDER BY created_at ASC,id ASC LIMIT 150"
        )->fetchAll(PDO::FETCH_ASSOC);

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $orders);
        $boxes = [];
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare(
                "SELECT b.order_id,b.box_number,b.box_name,c.item_name
                 FROM lunch_order_boxes b
                 LEFT JOIN lunch_order_choices c ON c.box_id=b.id
                 WHERE b.order_id IN ($placeholders)
                 ORDER BY b.order_id,b.box_number,c.id"
            );
            $stmt->execute($ids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
                $orderId = (int) $item['order_id'];
                $num = (int) $item['box_number'];
                $boxes[$orderId][$num]['name'] = (string) ($item['box_name'] ?? '');
                if ($item['item_name'] !== null) {
                    $boxes[$orderId][$num]['items'][] = $item['item_name'];
                }
            }
        }
        foreach ($orders as &$order) {
            $order['boxes'] = array_values($boxes[(int) $order['id']] ?? []);
        }
        unset($order);
        return $orders;
    }

    public static function move(int $orderId, string $next): void
    {
        $db = Db::pdo();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT status FROM lunch_orders WHERE id=? FOR UPDATE');
            $stmt->execute([$orderId]);
            $current = $stmt->fetchColumn();
            if (!is_string($current)) {
                throw new DomainException('Pedido não encontrado.');
            }
            if ((self::NEXT[$current] ?? null) !== $next) {
                throw new DomainException('O pedido já mudou de etapa. Atualize o painel.');
            }
            $update = $db->prepare('UPDATE lunch_orders SET status=? WHERE id=?');
            $update->execute([$next, $orderId]);
            $db->commit();
            AdminNotification::markReferenceRead('order', $orderId);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function sale(int $operatorId, array $data): int
    {
        $name = trim((string) ($data['description'] ?? ''));
        $quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT);
        $unit = filter_var($data['unit_price_cents'] ?? null, FILTER_VALIDATE_INT);
        $payment = (string) ($data['payment_method'] ?? '');

        if (mb_strlen($name) < 2 || mb_strlen($name) > 180) {
            throw new DomainException('Informe o produto ou descrição da venda.');
        }
        if ($quantity === false || $quantity < 1 || $quantity > 1000) {
            throw new DomainException('Quantidade inválida.');
        }
        if ($unit === false || $unit < 1 || $unit > 10000000) {
            throw new DomainException('Informe um preço válido.');
        }
        if (!in_array($payment, ['cash','pix','card'], true)) {
            throw new DomainException('Escolha a forma de pagamento.');
        }

        $total = $quantity * $unit;
        $stmt = Db::pdo()->prepare(
            'INSERT INTO pos_sales(operator_id,description,quantity,unit_price_cents,total_cents,payment_method)
             VALUES(?,?,?,?,?,?)'
        );
        $stmt->execute([$operatorId, $name, $quantity, $unit, $total, $payment]);
        return (int) Db::pdo()->lastInsertId();
    }

    public static function financial(?string $day = null): array
    {
        $date = $day ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || \DateTimeImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') !== $date) {
            throw new DomainException('Data inválida.');
        }
        $db = Db::pdo();
        $stmt = $db->prepare(
            "SELECT COUNT(*) sales_count,
                    COALESCE(SUM(total_cents),0) total_cents,
                    COALESCE(SUM(CASE WHEN payment_method='cash' THEN total_cents ELSE 0 END),0) cash_cents,
                    COALESCE(SUM(CASE WHEN payment_method='pix' THEN total_cents ELSE 0 END),0) pix_cents,
                    COALESCE(SUM(CASE WHEN payment_method='card' THEN total_cents ELSE 0 END),0) card_cents
             FROM pos_sales WHERE created_at>=? AND created_at<DATE_ADD(?,INTERVAL 1 DAY)"
        );
        $stmt->execute([$date,$date]);
        $local = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $db->prepare(
            "SELECT COUNT(*) orders_count,COALESCE(SUM(total_cents),0) received_cents
             FROM lunch_orders WHERE created_at>=? AND created_at<DATE_ADD(?,INTERVAL 1 DAY)
             AND payment_status='paid' AND status<>'cancelled'"
        );
        $stmt->execute([$date,$date]);
        $online = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $db->prepare(
            'SELECT id,description,quantity,total_cents,payment_method,created_at
             FROM pos_sales WHERE created_at>=? AND created_at<DATE_ADD(?,INTERVAL 1 DAY)
             ORDER BY id DESC LIMIT 60'
        );
        $stmt->execute([$date,$date]);
        return [
            'date' => $date,
            'local' => $local,
            'online' => $online,
            'grand_total_cents' => (int) $local['total_cents'] + (int) $online['received_cents'],
            'sales' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }
}
