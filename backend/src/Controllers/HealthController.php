<?php

namespace App\Controllers;

use App\Core\DB;
use Throwable;

/**
 * Liveness/readiness probe used by Docker and Render.
 */
final class HealthController
{
    public function check(): void
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        try {
            DB::value('SELECT 1');
            echo json_encode(['status' => 'ok', 'version' => APP_VERSION]);
        } catch (Throwable $e) {
            http_response_code(503);
            echo json_encode(['status' => 'error', 'database' => 'unreachable']);
        }
    }
}
