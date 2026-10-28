<?php

declare(strict_types=1);

namespace App;

use DomainException;

final class Customer
{
    public static function boot(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (headers_sent($file, $line)) {
                throw new \RuntimeException('A sessão do cliente precisa iniciar antes do HTML: ' . $file . ':' . $line);
            }
            session_name('bia_customer');
            session_set_cookie_params(['httponly' => true,'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','samesite' => 'Lax']);
            session_start();
        }
    }
    public static function id(): int
    {
        self::boot();
        return (int)($_SESSION['bia_customer_id'] ?? 0);
    }
    public static function csrf(): string
    {
        self::boot();
        return $_SESSION['bia_customer_csrf'] ??= bin2hex(random_bytes(24));
    }
    public static function check(): void
    {
        if (!hash_equals(self::csrf(), (string)($_POST['csrf'] ?? ''))) {
            throw new DomainException('Sessão expirada. Atualize e tente novamente.');
        }
    }
    public static function login(string $email, string $password): void
    {
        $q = Db::pdo()->prepare('SELECT id,password_hash FROM customer_accounts WHERE email=?');
        $q->execute([mb_strtolower(trim($email))]);
        $user = $q->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new DomainException('E-mail ou senha inválidos');
        }
        self::boot();
        session_regenerate_id(true);
        $_SESSION['bia_customer_id'] = (int)$user['id'];
        self::claimGuestOrders();
    }
    public static function register(string $email, string $password): void
    {
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            throw new DomainException('Informe um e-mail válido');
        }
        if (strlen($password) < 10 || strlen($password) > 128) {
            throw new DomainException('A senha precisa ter entre 10 e 128 caracteres');
        }
        try {
            $q = Db::pdo()->prepare('INSERT INTO customer_accounts(email,password_hash) VALUES(?,?)');
            $q->execute([$email,password_hash($password, PASSWORD_DEFAULT)]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new DomainException('E-mail já cadastrado. Entre com sua senha.');
            }throw $e;
        }
        self::boot();
        session_regenerate_id(true);
        $_SESSION['bia_customer_id'] = (int)Db::pdo()->lastInsertId();
        self::claimGuestOrders();
    }
    /**
     * Link only orders created within this same customer session.
     * Never claim an order based solely on a public order number.
     */
    public static function rememberGuestOrder(int $orderId): void
    {
        self::boot();
        if (self::id() || $orderId <= 0) {
            return;
        }
        $orders = $_SESSION['bia_guest_orders'] ?? [];
        $orders[] = $orderId;
        $_SESSION['bia_guest_orders'] = array_slice(array_unique(array_map('intval', $orders)), -25);
    }

    private static function claimGuestOrders(): void
    {
        $customerId = self::id();
        $orders = $_SESSION['bia_guest_orders'] ?? [];
        if (!$customerId || !$orders) {
            return;
        }
        $query = Db::pdo()->prepare('UPDATE lunch_orders SET customer_id=? WHERE id=? AND customer_id IS NULL');
        foreach ($orders as $orderId) {
            if (filter_var($orderId, FILTER_VALIDATE_INT)) {
                $query->execute([$customerId, (int)$orderId]);
            }
        }
        unset($_SESSION['bia_guest_orders']);
    }

    public static function requireLogin(): int
    {
        $id = self::id();
        if (!$id) {
            header('Location: /cliente/?next=pedido');
            exit;
        }return $id;
    }
    public static function address(int $id): array
    {
        $q = Db::pdo()->prepare('SELECT * FROM customer_addresses WHERE customer_id=?');
        $q->execute([$id]);
        return $q->fetch() ?: [];
    }
    public static function orders(int $id): array
    {
        $q = Db::pdo()->prepare('SELECT id,status,quantity,total_cents,payment_method,payment_status,created_at FROM lunch_orders WHERE customer_id=? ORDER BY id DESC LIMIT 50');
        $q->execute([$id]);
        return $q->fetchAll();
    }
}
