<?php

declare(strict_types=1);
require dirname(__DIR__, 3).'/src/bootstrap.php';
header('Content-Type: text/html; charset=UTF-8');
require __DIR__.'/layout.php';
session_name('raiz_admin');
session_set_cookie_params(['httponly' => true,'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','samesite' => 'Lax']);
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/');
    exit;
}
$staff = \App\Staff::current();
if (!$staff) {
    $_SESSION = [];
    session_destroy();
    header('Location: /admin/');
    exit;
}
if (!\App\Staff::allowed(basename($_SERVER['SCRIPT_NAME'] ?? ''), $staff['role'])) {
    http_response_code(403);
    exit('Você não tem permissão para acessar esta página.');
}
$csrf = $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
$flash = '';
$flashType = 'success';
function cms_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('CSRF inválido');
    }
}
function cms_notice(string $msg, string $type = 'success'): void
{
    $GLOBALS['flash'] = $msg;
    $GLOBALS['flashType'] = $type;
}
function cms_feedback(): void
{
    if ($GLOBALS['flash'] ?? '') {
        echo '<div class="alert alert-'.adm_h($GLOBALS['flashType']).'" role="status">'.adm_h($GLOBALS['flash']).'</div>';
    }
}
