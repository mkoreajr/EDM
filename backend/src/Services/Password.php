<?php

namespace App\Services;

/**
 * Password hashing with bcrypt/argon (password_hash).
 *
 * Accounts created by the old system store an unsalted SHA-256 hex digest.
 * Those still verify, and are transparently upgraded on the next login.
 */
final class Password
{
    public const RULES = 'Password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.';

    /** Same rule as the HTML pattern attribute used in the forms. */
    public const HTML_PATTERN = '(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}';

    public static function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public static function verify(string $plain, string $hash): bool
    {
        if (self::isLegacy($hash)) {
            return hash_equals(strtolower($hash), hash('sha256', $plain));
        }
        return password_verify($plain, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return self::isLegacy($hash) || password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    public static function isStrong(string $plain): bool
    {
        return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $plain);
    }

    /** Random temporary password that always satisfies isStrong(). */
    public static function temporary(int $length = 12): string
    {
        $sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789', '!@#$%'];
        $all = implode('', $sets);
        $chars = [];
        foreach ($sets as $set) {
            $chars[] = $set[random_int(0, strlen($set) - 1)];
        }
        while (count($chars) < $length) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }
        return implode('', $chars);
    }

    private static function isLegacy(string $hash): bool
    {
        return (bool)preg_match('/^[a-f0-9]{64}$/i', $hash);
    }
}
