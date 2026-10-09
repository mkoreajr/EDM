<?php

namespace App\Controllers;

use App\Core\DB;
use Throwable;

final class ProductController
{
    private const PER_PAGE = 10;
    private const CATEGORIES = ['Eggs', 'Rice', 'Flour'];
    private const NAME_MAX = 100;

    public function index(): void
    {
        $edit = null;
        if (input('edit', '') !== '') {
            $edit = DB::one('SELECT * FROM products WHERE id = ?', [(int)input('edit')]);
        }

        $pager = paginate((int)DB::value('SELECT COUNT(*) FROM products'), self::PER_PAGE, (int)input('page', 1));
        $products = DB::all('SELECT * FROM products ORDER BY id DESC LIMIT ? OFFSET ?', [$pager['perPage'], $pager['offset']]);

        view('products/index', [
            'pageTitle'  => 'Products',
            'active'     => 'products',
            'edit'       => $edit,
            'products'   => $products,
            'pager'      => $pager,
            'categories' => self::CATEGORIES,
            'error'      => flash('error'),
            'success'    => flash('success'),
        ]);
    }

    public function save(): void
    {
        $id = (int)input('id', 0);
        $category = (string)input('category', 'Eggs');
        $name = self::cleanName((string)input('name'));
        $price = (float)input('selling_price', 0);
        $stock = (float)input('stock_quantity', 0);
        $isEgg = $category === 'Eggs';
        $package = $isEgg ? null : (float)input('package_size_kg', 0);

        if ($name === '') {
            flash('error', 'Please enter a product name (up to ' . self::NAME_MAX . ' characters).');
            redirect(url('/products', ['edit' => $id ?: null]));
        }

        $valid = in_array($category, self::CATEGORIES, true)
            && $price >= 0 && $stock >= 0
            && ($isEgg || ($package >= 1 && $package <= 20));

        if (!$valid) {
            flash('error', 'Please enter valid product details. Rice and Flour package size must be between 1 and 20 Kg.');
            redirect(url('/products', ['edit' => $id ?: null]));
        }

        $params = [$category, $name, $isEgg ? 'Tray' : 'Bag', $package, $price, $stock];
        if ($id > 0) {
            DB::execute(
                'UPDATE products SET category = ?, name = ?, unit = ?, package_size_kg = ?, selling_price = ?, stock_quantity = ? WHERE id = ?',
                [...$params, $id]
            );
        } else {
            DB::execute(
                'INSERT INTO products (category, name, unit, package_size_kg, selling_price, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)',
                $params
            );
        }

        flash('success', 'Product saved successfully.');
        redirect('/products');
    }

    /**
     * Change only a product's name (e.g. when the rice source changes).
     * The customer shop reads names live; past sales, orders and purchases
     * keep the name stored on their own lines.
     */
    public function rename(): void
    {
        $id = (int)input('id', 0);
        $name = self::cleanName((string)input('name'));
        $back = url('/products', ['page' => (int)input('page', 0) ?: null]);

        $product = $id > 0 ? DB::one('SELECT name FROM products WHERE id = ?', [$id]) : null;
        if ($product === null) {
            flash('error', 'Product not found.');
            redirect($back);
        }
        if ($name === '') {
            flash('error', 'Please enter the new product name (up to ' . self::NAME_MAX . ' characters).');
            redirect($back);
        }
        if ($name === $product['name']) {
            flash('success', 'The product name is unchanged.');
            redirect($back);
        }

        DB::execute('UPDATE products SET name = ? WHERE id = ?', [$name, $id]);
        flash('success', "Product renamed from \u{201C}{$product['name']}\u{201D} to \u{201C}{$name}\u{201D}. The customer shop now shows the new name.");
        redirect($back);
    }

    /** Collapse whitespace; '' when empty or longer than the column allows. */
    private static function cleanName(string $name): string
    {
        $name = trim((string)preg_replace('/\s+/u', ' ', $name));
        return mb_strlen($name) <= self::NAME_MAX ? $name : '';
    }

    public function delete(): void
    {
        try {
            DB::execute('DELETE FROM products WHERE id = ?', [(int)input('id', 0)]);
            flash('success', 'Product deleted.');
        } catch (Throwable $e) {
            if (!DB::isForeignKeyViolation($e)) {
                throw $e;
            }
            flash('error', 'This product has sales, purchase or online-order history and cannot be deleted. Set its stock to 0 instead.');
        }
        redirect(url('/products', ['page' => (int)input('page', 0) ?: null]));
    }
}
