<?php

declare(strict_types=1);

require __DIR__ . '/includes/cms-boot.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método não permitido.');
}

cms_csrf();

$_SESSION = [];
session_destroy();

header('Location: /admin/');
exit;
