<?php

namespace App\Services;

use App\Core\DB;
use Throwable;

/**
 * Key/value shop settings stored in the app_settings table, with defaults.
 * Loaded once per request.
 */
final class Settings
{
    public const DEFAULTS = [
        'shop_name'              => 'MSINDA — FOOD SHOP',
        'shop_phone'             => '0712 345 678',
        'shop_address'           => 'Mwanza, Tanzania',
        'shop_email'             => '',
        'receipt_show_shop_name' => '1',
        'receipt_show_address'   => '1',
        'receipt_show_phone'     => '1',
        'receipt_show_number'    => '1',
        'receipt_show_datetime'  => '1',
        'receipt_show_cashier'   => '1',
        'receipt_show_thanks'    => '1',
        'receipt_footer'         => 'Thank you for shopping with us!',
        'currency'               => 'TZS',
        'date_format'            => 'DD MMM YYYY',
        'time_format'            => '24 Hours',
        'low_stock_alert'        => '1',
        'default_payment'        => 'Cash',
    ];

    public const PAYMENT_METHODS = ['Cash', 'Mobile Money', 'Bank'];

    private static ?array $cache = null;

    /** @return array<string,string> */
    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = self::DEFAULTS;
            try {
                foreach (DB::all('SELECT setting_key, setting_value FROM app_settings') as $row) {
                    self::$cache[$row['setting_key']] = (string)$row['setting_value'];
                }
            } catch (Throwable $e) {
                error_log('Settings could not be loaded: ' . $e->getMessage());
            }
        }
        return self::$cache;
    }

    public static function get(string $key): string
    {
        return self::all()[$key] ?? (self::DEFAULTS[$key] ?? '');
    }

    public static function set(string $key, string $value): void
    {
        DB::execute(
            'INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
             ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value',
            [$key, $value]
        );
        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }
}
