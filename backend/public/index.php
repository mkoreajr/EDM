<?php
/**
 * Front controller — every non-static request is routed through here by Nginx.
 */

use App\Core\Router;

require dirname(__DIR__) . '/src/bootstrap.php';

send_security_headers();

$router = new Router();
require BASE_PATH . '/src/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
