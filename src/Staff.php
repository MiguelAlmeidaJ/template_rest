<?php

declare(strict_types=1);

namespace App;

use DomainException;

final class Staff
{
    public static function current(): ?array
    {
        if (empty($_SESSION['admin_id'])) {
            return null;
        }
        $q = Db::pdo()->prepare('SELECT id,email,role,customer_id FROM admins WHERE id=?');
        $q->execute([(int)$_SESSION['admin_id']]);
        return $q->fetch() ?: null;
    }
    public static function role(): string
    {
        return self::current()['role'] ?? '';
    }
    public static function allowed(string $script, string $role): bool
    {
        if ($role === 'admin') {
            return true;
        }
        $restricted = ['pages.php','users.php'];
        if ($role === 'manager') {
            return !in_array($script, $restricted, true);
        }
        if ($role === 'employee') {
            return in_array($script, ['index.php','lunch.php','lunch-orders.php','lunch-chat.php','chats.php','notifications.php','alerts-status.php'], true);
        }
        return false;
    }
    public static function requireAdmin(): void
    {
        if (self::role() !== 'admin') {
            http_response_code(403);
            exit('Acesso permitido somente ao administrador.');
        }
    }
    public static function requireManagement(): void
    {
        if (!in_array(self::role(), ['admin','manager'], true)) {
            http_response_code(403);
            exit('Acesso permitido somente à gestão.');
        }
    }
    public static function manage(int $actorId, int $id, string $email, string $role, string $password = ''): void
    {
        if (!in_array($role, ['customer','employee','manager','admin'], true)) {
            throw new DomainException('Perfil inválido');
        }
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            throw new DomainException('E-mail inválido');
        }
        if ($password !== '' && (strlen($password) < 10 || strlen($password) > 128)) {
            throw new DomainException('Senha: mínimo 10 caracteres');
        }
        $db = Db::pdo();
        $db->beginTransaction();
        try {
            $old = null;
            if ($id) {
                $q = $db->prepare('SELECT c.id,c.email,c.password_hash,a.id staff_id,a.role staff_role FROM customer_accounts c LEFT JOIN admins a ON a.customer_id=c.id WHERE c.id=? FOR UPDATE');
                $q->execute([$id]);
                $old = $q->fetch();
                if (!$old) {
                    throw new DomainException('Usuário não encontrado');
                }
            }
            if ($old && $old['staff_role'] === 'admin' && $role !== 'admin') {
                $n = (int)$db->query("SELECT COUNT(*) FROM admins WHERE role='admin'")->fetchColumn();
                if ($n <= 1) {
                    throw new DomainException('Não é permitido remover o último administrador');
                }
                if ((int)$old['staff_id'] === $actorId) {
                    throw new DomainException('Não altere seu próprio perfil administrativo');
                }
            }
            $existing = $db->prepare('SELECT id FROM customer_accounts WHERE email=? AND id<>?');
            $existing->execute([$email,$id]);
            if ($existing->fetch()) {
                throw new DomainException('E-mail já cadastrado');
            }
            if (!$id) {
                if ($password === '') {
                    throw new DomainException('Informe uma senha para criar usuário');
                }$q = $db->prepare('INSERT INTO customer_accounts(email,password_hash) VALUES(?,?)');
                $q->execute([$email,password_hash($password, PASSWORD_DEFAULT)]);
                $id = (int)$db->lastInsertId();
            } else {
                $q = $db->prepare('UPDATE customer_accounts SET email=?'.($password !== '' ? ',password_hash=?' : '').' WHERE id=?');
                $q->execute($password !== '' ? [$email,password_hash($password, PASSWORD_DEFAULT),$id] : [$email,$id]);
            }
            $q = $db->prepare('SELECT id FROM admins WHERE customer_id=?');
            $q->execute([$id]);
            $staffId = $q->fetchColumn();
            if ($role === 'customer') {
                if ($staffId) {
                    $db->prepare('DELETE FROM admins WHERE id=?')->execute([$staffId]);
                }
            } elseif ($staffId) {
                $db->prepare('UPDATE admins SET email=?,role=?,password_hash=(SELECT password_hash FROM customer_accounts WHERE id=?) WHERE id=?')->execute([$email,$role,$id,$staffId]);
            } else {
                $q = $db->prepare('INSERT INTO admins(email,password_hash,role,customer_id) SELECT email,password_hash,?,id FROM customer_accounts WHERE id=?');
                $q->execute([$role,$id]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
    public static function remove(int $actorId, int $id): void
    {
        $db = Db::pdo();
        $db->beginTransaction();
        try {
            $q = $db->prepare('SELECT a.id staff_id,a.role FROM customer_accounts c LEFT JOIN admins a ON a.customer_id=c.id WHERE c.id=? FOR UPDATE');
            $q->execute([$id]);
            $row = $q->fetch();
            if (!$row) {
                throw new DomainException('Usuário não encontrado');
            }
            if ($row['staff_id'] && (int)$row['staff_id'] === $actorId) {
                throw new DomainException('Você não pode excluir seu próprio acesso');
            }
            if ($row['role'] === 'admin' && (int)$db->query("SELECT COUNT(*) FROM admins WHERE role='admin'")->fetchColumn() <= 1) {
                throw new DomainException('Não é permitido excluir o último administrador');
            }
            if ($row['staff_id']) {
                $db->prepare('DELETE FROM admins WHERE id=?')->execute([$row['staff_id']]);
            }
            $db->prepare('DELETE FROM customer_accounts WHERE id=?')->execute([$id]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
