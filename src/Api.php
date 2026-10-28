<?php

declare(strict_types=1);

namespace App;

final class Api
{
    public static function send(array $v, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($v, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
    public static function input(): array
    {
        if (($_SERVER['CONTENT_TYPE'] ?? '') && !str_starts_with(strtolower($_SERVER['CONTENT_TYPE']), 'application/json')) {
            throw new \DomainException('Use application/json');
        }$data = json_decode(file_get_contents('php://input') ?: '{}', true);
        if (!is_array($data)) {
            throw new \DomainException('JSON inválido');
        }return $data;
    }
}
