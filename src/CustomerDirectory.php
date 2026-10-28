<?php

declare(strict_types=1);

namespace App;

final class CustomerDirectory
{
    public const PAGE_SIZE = 25;

    public const FILTERS = [
        'all' => 'Todos os clientes',
        'with_orders' => 'Com pedidos',
        'no_orders' => 'Sem pedidos',
    ];

    public static function searchTerm(mixed $value): string
    {
        return mb_substr(trim((string) $value), 0, 100);
    }

    public static function filter(mixed $value): string
    {
        $filter = (string) $value;

        return array_key_exists($filter, self::FILTERS) ? $filter : 'all';
    }

    /**
     * @return array{customers: array, total: int, registered: int, page: int, pages: int}
     */
    public static function paginate(string $search, string $filter, int $requestedPage): array
    {
        $db = Db::pdo();
        $conditions = [];
        $parameters = [];

        if ($search !== '') {
            $conditions[] = '(c.email LIKE ? OR a.recipient LIKE ? OR a.phone LIKE ?)';
            $term = '%' . $search . '%';
            $parameters = [$term, $term, $term];
        }

        if ($filter === 'with_orders') {
            $conditions[] = 'COALESCE(o.orders_count, 0) > 0';
        } elseif ($filter === 'no_orders') {
            $conditions[] = 'COALESCE(o.orders_count, 0) = 0';
        }

        $from = <<<'SQL'
            FROM customer_accounts c
            LEFT JOIN customer_addresses a ON a.customer_id = c.id
            LEFT JOIN (
                SELECT
                    customer_id,
                    COUNT(*) AS orders_count,
                    MAX(created_at) AS last_order
                FROM lunch_orders
                WHERE customer_id IS NOT NULL
                GROUP BY customer_id
            ) o ON o.customer_id = c.id
            SQL;

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        $count = $db->prepare('SELECT COUNT(*) ' . $from . $where);
        $count->execute($parameters);

        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $page = min(max(1, $requestedPage), $pages);
        $offset = ($page - 1) * self::PAGE_SIZE;

        $query = $db->prepare(
            'SELECT
                c.id, c.email, c.created_at,
                a.recipient, a.phone, a.neighborhood,
                COALESCE(o.orders_count, 0) AS orders_count,
                o.last_order '
            . $from . $where
            . ' ORDER BY c.created_at DESC, c.id DESC'
            . ' LIMIT ' . self::PAGE_SIZE . ' OFFSET ' . $offset
        );
        $query->execute($parameters);

        return [
            'customers' => $query->fetchAll(),
            'total' => $total,
            'registered' => (int) $db->query('SELECT COUNT(*) FROM customer_accounts')->fetchColumn(),
            'page' => $page,
            'pages' => $pages,
        ];
    }

    public static function find(int $customerId): ?array
    {
        $query = Db::pdo()->prepare(<<<'SQL'
            SELECT
                c.id, c.email, c.created_at,
                a.recipient, a.phone, a.address,
                a.neighborhood, a.reference_point
            FROM customer_accounts c
            LEFT JOIN customer_addresses a ON a.customer_id = c.id
            WHERE c.id = ?
            SQL);

        $query->execute([$customerId]);

        return $query->fetch() ?: null;
    }

    public static function orders(int $customerId): array
    {
        $query = Db::pdo()->prepare(<<<'SQL'
            SELECT id, status, quantity, total_cents, payment_status, created_at
            FROM lunch_orders
            WHERE customer_id = ?
            ORDER BY id DESC
            LIMIT 50
            SQL);

        $query->execute([$customerId]);

        return $query->fetchAll();
    }

    public static function statusLabel(string $status): string
    {
        return [
            'pending' => 'Novo pedido',
            'accepted' => 'Aceito',
            'preparing' => 'Em preparação',
            'out_for_delivery' => 'Saindo para entrega',
            'delivered' => 'Entregue',
            'cancelled' => 'Cancelado',
        ][$status] ?? $status;
    }
}
