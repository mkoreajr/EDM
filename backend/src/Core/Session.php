<?php

namespace App\Core;

/**
 * Hardened PHP session start (HttpOnly, SameSite, Secure behind HTTPS).
 *
 * The staff system and the customer portal (/shop) use separate session
 * cookies, so signing in or out of one never affects the other.
 */
final class Session
{
    public const AREA_ADMIN = 'admin';
    public const AREA_SHOP = 'shop';

    private const COOKIES = [
        self::AREA_ADMIN => ['name' => 'edm_session', 'path' => '/'],
        self::AREA_SHOP  => ['name' => 'agizapoa_shop', 'path' => '/shop'],
    ];

    public static function start(string $area = self::AREA_ADMIN): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $cookie = self::COOKIES[$area] ?? self::COOKIES[self::AREA_ADMIN];
        session_name($cookie['name']);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $cookie['path'],
            'secure'   => request_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $params['path'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
        session_destroy();
    }
}
