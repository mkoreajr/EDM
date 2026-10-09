<?php
/**
 * Application bootstrap: autoloading, helpers, timezone and error handling.
 * Shared by the web front controller and CLI scripts (bin/migrate.php).
 */

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = BASE_PATH . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/src/helpers.php';

define('APP_VERSION', env('APP_VERSION', '2.2.0'));

date_default_timezone_set(env('APP_TIMEZONE', 'Africa/Dar_es_Salaam'));

error_reporting(E_ALL);
ini_set('display_errors', app_debug() ? '1' : '0');
ini_set('log_errors', '1');

set_exception_handler(static function (Throwable $e): void {
    error_log((string)$e);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
    }
    App\Core\View::render('errors/error', [
        'code'    => 500,
        'title'   => 'Something went wrong',
        'message' => app_debug() ? (string)$e : 'An unexpected error occurred. Please try again or contact support.',
    ], null);
});
