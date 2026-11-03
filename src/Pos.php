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
            "SELECT o.id,o.customer_id,o.customer_name,o.customer_phone,o.address,o.neighborhood,o.reference_point,
                    o.quantity,o.total_cents,o.payment_method,o.payment_status,o.status,o.created_at,
                    (SELECT COUNT(*) FROM admin_notifications n WHERE n.type='chat' AND n.reference_id=o.id AND n.is_read=0) AS unread_messages
             FROM lunch_orders o
             WHERE status IN ('pending','accepted','preparing','out_for_delivery')
             ORDER BY o.created_at ASC,o.id ASC LIMIT 150"
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

    /**
     * One counter sale can contain multiple differently priced products.
     * The sale header and line items are committed as a single transaction.
     */
    public static function sale(int $operatorId, array $data): int
    {
        $payment = (string) ($data['payment_method'] ?? '');
        if (!in_array($payment, ['cash', 'pix', 'card'], true)) {
            throw new DomainException('Escolha como a venda foi paga.');
        }

        $raw = json_decode((string) ($data['items'] ?? ''), true);
        if (!is_array($raw) || !array_is_list($raw) || count($raw) < 1 || count($raw) > 50) {
            throw new DomainException('Adicione de 1 a 50 itens à venda.');
        }

        $items = [];
        $total = 0;
        $count = 0;
        foreach ($raw as $index => $item) {
            if (!is_array($item)) {
                throw new DomainException('Confira os itens da venda.');
            }
            $name = trim((string) ($item['description'] ?? ''));
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            $price = filter_var($item['unit_price_cents'] ?? null, FILTER_VALIDATE_INT);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 180) {
                throw new DomainException('Informe o nome do item '.($index + 1).'.');
            }
            if ($quantity === false || $quantity < 1 || $quantity > 1000) {
                throw new DomainException('Quantidade inválida no item '.($index + 1).'.');
            }
            if ($price === false || $price < 1 || $price > 10000000) {
                throw new DomainException('Preço inválido no item '.($index + 1).'.');
            }
            $subtotal = $quantity * $price;
            $total += $subtotal;
            $count += $quantity;
            if ($total > 4294967295 || $count > 4294967295) {
                throw new DomainException('O valor da venda excede o limite permitido.');
            }
            $items[] = [$name, $quantity, $price, $subtotal];
        }

        $summary = $items[0][0].(count($items) > 1 ? ' + '.(count($items) - 1).' item(ns)' : '');
        $description = mb_substr($summary, 0, 180);
        $db = Db::pdo();
        $db->beginTransaction();
        try {
            // Existing columns remain populated for older views and exports.
            $header = $db->prepare(
                'INSERT INTO pos_sales(operator_id,description,quantity,unit_price_cents,total_cents,payment_method)
                 VALUES(?,?,?,?,?,?)'
            );
            $header->execute([$operatorId, $description, $count, $items[0][2], $total, $payment]);
            $saleId = (int) $db->lastInsertId();

            $line = $db->prepare(
                'INSERT INTO pos_sale_items(sale_id,description,quantity,unit_price_cents,total_cents)
                 VALUES(?,?,?,?,?)'
            );
            foreach ($items as [$name, $quantity, $price, $subtotal]) {
                $line->execute([$saleId, $name, $quantity, $price, $subtotal]);
            }
            $db->commit();
            return $saleId;
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public static function financial(?string $day = null): array
    {
        $date = $day ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || !($parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date))
            || $parsed->format('Y-m-d') !== $date) {
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
             FROM lunch_orders WHERE paid_at>=? AND paid_at<DATE_ADD(?,INTERVAL 1 DAY)
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
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ids = array_column($sales, 'id');
        $lineItems = [];
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $query = $db->prepare(
                "SELECT sale_id,description,quantity,unit_price_cents,total_cents
                 FROM pos_sale_items WHERE sale_id IN ($placeholders) ORDER BY id ASC"
            );
            $query->execute($ids);
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $line) {
                $lineItems[(int) $line['sale_id']][] = $line;
            }
        }
        foreach ($sales as &$sale) {
            $sale['items'] = $lineItems[(int) $sale['id']] ?? [];
        }
        unset($sale);

        return [
            'date' => $date,
            'local' => $local,
            'online' => $online,
            'grand_total_cents' => (int) $local['total_cents'] + (int) $online['received_cents'],
            'sales' => $sales,
        ];
    }
}
