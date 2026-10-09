<?php

namespace App\Core;

/**
 * Signed-in customer for the /shop portal. Lives in the shop session only,
 * so a customer account can never reach the staff pages.
 */
final class CustomerAuth
{
    /** Customers are signed out after 30 minutes without activity. */
    public const IDLE_TIMEOUT = 1800;

    private static ?array $customer = null;
    private static bool $loaded = false;

    /** @return array{id:int,name:string,phone:?string,address:?string,username:string}|null */
    public static function customer(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = (int)($_SESSION['customer_id'] ?? 0);
            if ($id > 0) {
                self::$customer = DB::one(
                    'SELECT c.id, c.name, c.phone, c.address, ca.username
                     FROM customer_accounts ca JOIN customers c ON c.id = ca.customer_id
                     WHERE ca.customer_id = ?',
                    [$id]
                );
                if (self::$customer === null) {
                    unset($_SESSION['customer_id']); // account was deleted
                }
            }
        }
        return self::$customer;
    }

    public static function check(): bool
    {
        return self::customer() !== null;
    }

    public static function id(): int
    {
        return (int)(self::customer()['id'] ?? 0);
    }

    /** True when the customer has been idle too long; also records this request as activity. */
    public static function expired(): bool
    {
        $last = (int)($_SESSION['customer_last_activity'] ?? 0);
        $now = time();
        if ($last > 0 && $now - $last >= self::IDLE_TIMEOUT) {
            return true;
        }
        $_SESSION['customer_last_activity'] = $now;
        return false;
    }

    public static function login(int $customerId): void
    {
        session_regenerate_id(true);
        $_SESSION['customer_id'] = $customerId;
        $_SESSION['customer_last_activity'] = time();
        self::refresh();
    }

    public static function logout(): void
    {
        Session::destroy();
        self::refresh();
    }

    public static function refresh(): void
    {
        self::$customer = null;
        self::$loaded = false;
    }
}
