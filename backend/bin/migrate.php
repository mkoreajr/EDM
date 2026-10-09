<?php
/**
 * Apply pending database migrations.
 *
 *   php bin/migrate.php [--wait=60]
 *
 * --wait retries the database connection for up to N seconds (useful while
 * the PostgreSQL container is still starting).
 */

use App\Core\DB;
use App\Services\Migrator;

require dirname(__DIR__) . '/src/bootstrap.php';

$wait = 0;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--wait=')) {
        $wait = max(0, (int)substr($arg, 7));
    }
}

$deadline = time() + $wait;
while (true) {
    try {
        DB::connection();
        break;
    } catch (PDOException $e) {
        if (time() >= $deadline) {
            fwrite(STDERR, 'Database is not reachable: ' . $e->getMessage() . PHP_EOL);
            exit(1);
        }
        fwrite(STDOUT, "Waiting for database...\n");
        sleep(2);
    }
}

// Repo layout: <root>/backend (BASE_PATH) and <root>/database/migrations.
$directory = env('MIGRATIONS_PATH', dirname(BASE_PATH) . '/database/migrations');
$applied = (new Migrator($directory))->run();

echo $applied
    ? 'Applied migrations: ' . implode(', ', $applied) . PHP_EOL
    : 'Database schema is up to date.' . PHP_EOL;
