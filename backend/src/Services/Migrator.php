<?php

namespace App\Services;

use App\Core\DB;
use RuntimeException;
use Throwable;

/**
 * Applies versioned SQL files from database/migrations exactly once each,
 * in filename order, recording them in the schema_migrations table.
 *
 * A PostgreSQL advisory lock makes concurrent container starts safe.
 */
final class Migrator
{
    private const LOCK_ID = 7_340_211;

    public function __construct(private string $directory)
    {
    }

    /** @return list<string> names of migrations applied by this run */
    public function run(): array
    {
        $pdo = DB::connection();
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
            migration  VARCHAR(255) PRIMARY KEY,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('SELECT pg_advisory_lock(' . self::LOCK_ID . ')');
        try {
            $done = array_flip(DB::query('SELECT migration FROM schema_migrations')->fetchAll(\PDO::FETCH_COLUMN));
            $applied = [];

            foreach ($this->files() as $file) {
                $name = basename($file);
                if (isset($done[$name])) {
                    continue;
                }
                $sql = file_get_contents($file);
                if ($sql === false) {
                    throw new RuntimeException("Cannot read migration {$name}");
                }

                $pdo->beginTransaction();
                try {
                    $pdo->exec($sql);
                    DB::execute('INSERT INTO schema_migrations (migration) VALUES (?)', [$name]);
                    $pdo->commit();
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    throw new RuntimeException("Migration {$name} failed: " . $e->getMessage(), 0, $e);
                }
                $applied[] = $name;
            }
            return $applied;
        } finally {
            $pdo->exec('SELECT pg_advisory_unlock(' . self::LOCK_ID . ')');
        }
    }

    /** @return list<string> */
    private function files(): array
    {
        $files = glob(rtrim($this->directory, '/\\') . '/*.sql') ?: [];
        sort($files, SORT_STRING);
        return $files;
    }
}
