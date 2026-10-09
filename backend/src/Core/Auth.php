<?php

namespace App\Core;

/**
 * Current-user access. The user row is loaded once per request, so role
 * changes, renames and deletions take effect immediately.
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    /** @return array{id:int,name:string,username:string,role:string,must_change_password:bool}|null */
    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = (int)($_SESSION['user_id'] ?? 0);
            if ($id > 0) {
                self::$user = DB::one(
                    'SELECT id, name, username, role, must_change_password FROM users WHERE id = ?',
                    [$id]
                );
                if (self::$user === null) {
                    unset($_SESSION['user_id']); // account was deleted
                }
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): int
    {
        return (int)(self::user()['id'] ?? 0);
    }

    public static function name(): string
    {
        return (string)(self::user()['name'] ?? 'Administrator');
    }

    public static function role(): string
    {
        return (string)(self::user()['role'] ?? '');
    }

    public static function isAdmin(): bool
    {
        return strtolower(self::role()) === 'admin';
    }

    /** Seconds of inactivity before staff are signed out (ADMIN_IDLE_TIMEOUT, 0 = never). */
    public static function idleTimeout(): int
    {
        return max(0, (int)env('ADMIN_IDLE_TIMEOUT', '120'));
    }

    /** True when the user has been idle too long; also records this request as activity. */
    public static function expired(): bool
    {
        $limit = self::idleTimeout();
        $last = (int)($_SESSION['last_activity'] ?? 0);
        $now = time();
        if ($limit > 0 && $last > 0 && $now - $last >= $limit) {
            return true;
        }
        $_SESSION['last_activity'] = $now;
        return false;
    }

    public static function mustChangePassword(): bool
    {
        return !empty(self::user()['must_change_password']);
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity'] = time();
        self::refresh();
    }

    public static function logout(): void
    {
        Session::destroy();
        self::refresh();
    }

    /** Forget the cached row so the next call re-reads the database. */
    public static function refresh(): void
    {
        self::$user = null;
        self::$loaded = false;
    }
}
