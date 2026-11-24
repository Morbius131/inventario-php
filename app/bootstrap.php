<?php

declare(strict_types=1);

define('APP_DEBUG', getenv('APP_DEBUG') === '1');
define('BASE_PATH', dirname(__DIR__));

ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(E_ALL);
date_default_timezone_set('America/Bogota');

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/helpers.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('inventario_sid');
session_start();

set_exception_handler(static function (Throwable $e): void {
    error_log((string) $e);
    http_response_code(500);
    echo APP_DEBUG
        ? '<pre>' . e((string) $e) . '</pre>'
        : '<h1>Error interno</h1><p>Algo salió mal. Intenta de nuevo más tarde.</p>';
});
