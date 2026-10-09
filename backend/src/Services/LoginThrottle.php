<?php

namespace App\Services;

use App\Core\DB;

/**
 * Brute-force protection: blocks a username after 5 failed attempts,
 * and an IP address after 20, within a 15 minute window.
 */
final class LoginThrottle
{
    private const WINDOW = "INTERVAL '15 minutes'";
    private const MAX_PER_USERNAME = 5;
    private const MAX_PER_IP = 20;

    public static function tooManyAttempts(string $username, string $ip): bool
    {
        $row = DB::one(
            'SELECT
                COUNT(*) FILTER (WHERE LOWER(username) = LOWER(?)) AS by_user,
                COUNT(*) FILTER (WHERE ip_address = ?)             AS by_ip
             FROM login_attempts
             WHERE attempted_at > LOCALTIMESTAMP - ' . self::WINDOW,
            [self::clip($username), $ip]
        );
        return (int)$row['by_user'] >= self::MAX_PER_USERNAME || (int)$row['by_ip'] >= self::MAX_PER_IP;
    }

    public static function recordFailure(string $username, string $ip): void
    {
        DB::execute(
            'INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)',
            [self::clip($username), substr($ip, 0, 45)]
        );
        if (random_int(1, 50) === 1) {
            DB::execute("DELETE FROM login_attempts WHERE attempted_at < LOCALTIMESTAMP - INTERVAL '1 day'");
        }
    }

    public static function clear(string $username): void
    {
        DB::execute('DELETE FROM login_attempts WHERE LOWER(username) = LOWER(?)', [self::clip($username)]);
    }

    private static function clip(string $username): string
    {
        return mb_substr($username, 0, 50);
    }
}
