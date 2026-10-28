<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Db
{
    public static function pdo(): PDO
    {
        static $db = null;
        if ($db) {
            return $db;
        }
        $dsn = 'mysql:host='.($_ENV['DB_HOST'] ?? '127.0.0.1').';port='.($_ENV['DB_PORT'] ?? '3306').';dbname='.($_ENV['DB_NAME'] ?? 'template_rest').';charset=utf8mb4';
        return $db = new PDO($dsn, $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES => false]);
    }
}
