<?php
declare(strict_types=1);
// Development router for: php -S localhost:8000 -t public public/router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$root = realpath(__DIR__);
$target = realpath(__DIR__ . rawurldecode($path));
if ($target !== false && $root !== false
    && ($target === $root || str_starts_with($target, $root . DIRECTORY_SEPARATOR))) {
    if (is_file($target)) {
        return false;
    }
    if (is_dir($target) && is_file($target . DIRECTORY_SEPARATOR . 'index.php')) {
        require $target . DIRECTORY_SEPARATOR . 'index.php';
        return true;
    }
}
require __DIR__ . '/index.php';
