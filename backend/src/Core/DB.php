<?php

namespace App\Core;

use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

/**
 * Thin PDO wrapper for PostgreSQL. One lazy connection per request.
 *
 * Connection settings come from DATABASE_URL (Render / Docker), or the
 * individual DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASSWORD variables.
 */
final class DB
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $url = env('DATABASE_URL');
        if ($url === null) {
            $url = sprintf(
                'pgsql://%s:%s@%s:%s/%s',
                rawurlencode(env('DB_USER', 'postgres')),
                rawurlencode(env('DB_PASSWORD', '')),
                env('DB_HOST', 'localhost'),
                env('DB_PORT', '5432'),
                env('DB_NAME', 'edm')
            );
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            throw new RuntimeException('DATABASE_URL is not a valid connection string.');
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $parts['host'],
            (int)($parts['port'] ?? 5432),
            ltrim(rawurldecode($parts['path'] ?? ''), '/')
        );
        parse_str($parts['query'] ?? '', $query);
        if (!empty($query['sslmode']) && is_string($query['sslmode'])) {
            $dsn .= ';sslmode=' . $query['sslmode'];
        }

        $pdo = new PDO(
            $dsn,
            isset($parts['user']) ? rawurldecode($parts['user']) : null,
            isset($parts['pass']) ? rawurldecode($parts['pass']) : null,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        // Keep CURRENT_DATE / CURRENT_TIMESTAMP in the shop's local time.
        $pdo->exec('SET TIME ZONE ' . $pdo->quote(date_default_timezone_get()));

        return self::$pdo = $pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    /** @return list<array<string,mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** First column of the first row (or null). */
    public static function value(string $sql, array $params = [])
    {
        $value = self::query($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    /** Run a statement and return the number of affected rows. */
    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Run $callback inside a transaction; commits on success, rolls back on any error.
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public static function transaction(callable $callback)
    {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** True when the exception is a PostgreSQL foreign-key violation (SQLSTATE 23503). */
    public static function isForeignKeyViolation(Throwable $e): bool
    {
        return $e instanceof \PDOException && $e->getCode() === '23503';
    }
}
