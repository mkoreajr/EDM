<?php

namespace App\Services;

use App\Core\DB;

/**
 * Customer shopping cart, kept in the shop session as productId => quantity.
 * Prices are always read fresh from the products table.
 */
final class Cart
{
    private const KEY = 'cart';

    /** @return array<int,int> */
    public static function raw(): array
    {
        $cart = $_SESSION[self::KEY] ?? [];
        return is_array($cart) ? $cart : [];
    }

    public static function count(): int
    {
        return array_sum(array_map('intval', self::raw()));
    }

    /** Add (or, with $replace, set) a quantity — capped at the available stock. */
    public static function put(int $productId, int $quantity, bool $replace = false): void
    {
        $cart = self::raw();
        $stock = DB::value('SELECT stock_quantity FROM products WHERE id = ?', [$productId]);
        if ($stock === null) {
            return;
        }
        $wanted = $replace ? $quantity : ($cart[$productId] ?? 0) + max(1, $quantity);
        $wanted = min((int)floor((float)$stock), $wanted);
        if ($wanted > 0) {
            $cart[$productId] = $wanted;
        } else {
            unset($cart[$productId]);
        }
        $_SESSION[self::KEY] = $cart;
    }

    public static function remove(int $productId): void
    {
        $cart = self::raw();
        unset($cart[$productId]);
        $_SESSION[self::KEY] = $cart;
    }

    public static function clear(): void
    {
        unset($_SESSION[self::KEY]);
    }

    /**
     * Cart lines with current product data.
     * @return array{items: list<array<string,mixed>>, total: float}
     */
    public static function lines(): array
    {
        $cart = array_filter(self::raw(), static fn($q) => (int)$q > 0);
        if (!$cart) {
            return ['items' => [], 'total' => 0.0];
        }
        $ids = array_map('intval', array_keys($cart));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $products = DB::all("SELECT * FROM products WHERE id IN ($placeholders) ORDER BY category, name, package_size_kg", $ids);

        $items = [];
        $total = 0.0;
        foreach ($products as $p) {
            $p['cart_qty'] = (int)$cart[(int)$p['id']];
            $p['line_total'] = $p['cart_qty'] * (float)$p['selling_price'];
            $total += $p['line_total'];
            $items[] = $p;
        }
        return ['items' => $items, 'total' => $total];
    }
}
