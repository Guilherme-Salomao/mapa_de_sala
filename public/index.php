<?php
ob_start();

ini_set('default_charset', 'UTF-8');

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

if (! headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$logDir = __DIR__ . '/../app/logs';

if (! is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

$logFile = $logDir . '/php_errors.log';

ini_set('log_errors', '1');
ini_set('error_log', $logFile);
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $exception) use ($logFile): void {
    error_log(sprintf(
        "[%s] Uncaught %s: %s in %s:%d\n%s",
        date('Y-m-d H:i:s'),
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    ), 3, $logFile);

    http_response_code(500);
    echo 'Erro interno do sistema. Verifique o arquivo app/logs/php_errors.log.';
});

register_shutdown_function(function () use ($logFile): void {
    $error = error_get_last();

    if ($error === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

    if (! in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    error_log(sprintf(
        "[%s] Fatal error: %s in %s:%d\n",
        date('Y-m-d H:i:s'),
        $error['message'] ?? '',
        $error['file'] ?? '',
        $error['line'] ?? 0
    ), 3, $logFile);
});

require_once __DIR__ . '/../routes/web.php';
