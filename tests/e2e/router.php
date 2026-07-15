<?php

// Router dla wbudowanego serwera PHP uzywanego tylko w e2e (php -S ... -t public tests/e2e/router.php).
// Statyczne pliki z public/ serwuje sam serwer, "/" oddaje SPA (index.html), reszta idzie do Slim.

declare(strict_types=1);

$root = dirname(__DIR__, 2) . '/public';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/') {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($root . '/index.html');
    return true;
}

if (is_file($root . $path)) {
    return false;
}

require $root . '/index.php';
