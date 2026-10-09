<?php

namespace App\Controllers;

use App\Core\DB;
use Throwable;

final class ProductController
{
    private const PER_PAGE = 10;
    private const CATEGORIES = ['Eggs', 'Rice', 'Flour'];

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
        $name = (string)input('name');
        $price = (float)input('selling_price', 0);
        $stock = (float)input('stock_quantity', 0);
        $isEgg = $category === 'Eggs';
        $package = $isEgg ? null : (float)input('package_size_kg', 0);

        $valid = in_array($category, self::CATEGORIES, true)
            && in_array($name, self::CATEGORIES, true)
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
