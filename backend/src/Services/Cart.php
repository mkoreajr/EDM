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
     * Cart lines with current product data. Quantities are brought in line with
     * the stock available right now: items that ran out are removed and larger
     * quantities are lowered, and the cart in the session is updated to match.
     *
     * @return array{items: list<array<string,mixed>>, total: float, adjusted: list<string>}
     *         adjusted = names of products whose quantity was changed or removed
     */
    public static function lines(): array
    {
        $cart = array_filter(self::raw(), static fn($q) => (int)$q > 0);
        if (!$cart) {
            return ['items' => [], 'total' => 0.0, 'adjusted' => []];
        }
        $ids = array_map('intval', array_keys($cart));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $products = DB::all("SELECT * FROM products WHERE id IN ($placeholders) ORDER BY category, name, package_size_kg", $ids);

        $items = [];
        $adjusted = [];
        $kept = [];
        $total = 0.0;
        foreach ($products as $p) {
            $wanted = (int)$cart[(int)$p['id']];
            $available = (int)floor((float)$p['stock_quantity']);
            $quantity = min($wanted, $available);
            if ($quantity < $wanted) {
                $adjusted[] = (string)$p['name'];
            }
            if ($quantity <= 0) {
                continue;
            }
            $kept[(int)$p['id']] = $quantity;
            $p['cart_qty'] = $quantity;
            $p['line_total'] = $quantity * (float)$p['selling_price'];
            $total += $p['line_total'];
            $items[] = $p;
        }

        // Products deleted since they were added simply drop out.
        if ($kept != $cart) {
            $_SESSION[self::KEY] = $kept;
        }
        return ['items' => $items, 'total' => $total, 'adjusted' => $adjusted];
    }

    /** @param list<string> $names */
    public static function adjustmentMessage(array $names): string
    {
        return 'Stock changed for ' . implode(', ', array_unique($names))
            . '. Your cart now shows the quantity still available — please check it before you continue.';
    }
}
