<?php

namespace App\Controllers;

use App\Core\DB;
use Throwable;

final class CustomerController
{
    private const PER_PAGE = 10;

    public function index(): void
    {
        $edit = null;
        if (input('edit', '') !== '') {
            $edit = DB::one('SELECT * FROM customers WHERE id = ?', [(int)input('edit')]);
        }

        $pager = paginate((int)DB::value('SELECT COUNT(*) FROM customers'), self::PER_PAGE, (int)input('page', 1));
        $customers = DB::all('SELECT * FROM customers ORDER BY name LIMIT ? OFFSET ?', [$pager['perPage'], $pager['offset']]);

        view('customers/index', [
            'pageTitle' => 'Customers',
            'active'    => 'customers',
            'edit'      => $edit,
            'customers' => $customers,
            'pager'     => $pager,
            'error'     => flash('error'),
            'success'   => flash('success'),
        ]);
    }

    public function save(): void
    {
        $id = (int)input('id', 0);
        $name = (string)input('name');
        $phone = (string)input('phone');
        $address = (string)input('address');

        if ($name === '' || mb_strlen($name) > 100 || mb_strlen($phone) > 30 || mb_strlen($address) > 255) {
            flash('error', 'Please enter a customer name (max 100 characters), phone (max 30) and address (max 255).');
            redirect(url('/customers', ['edit' => $id ?: null]));
        }

        if ($id > 0) {
            DB::execute('UPDATE customers SET name = ?, phone = ?, address = ? WHERE id = ?', [$name, $phone, $address, $id]);
        } else {
            DB::execute('INSERT INTO customers (name, phone, address) VALUES (?, ?, ?)', [$name, $phone, $address]);
        }

        flash('success', 'Customer saved successfully.');
        redirect('/customers');
    }

    public function delete(): void
    {
        try {
            DB::execute('DELETE FROM customers WHERE id = ?', [(int)input('id', 0)]);
            flash('success', 'Customer deleted.');
        } catch (Throwable $e) {
            if (!DB::isForeignKeyViolation($e)) {
                throw $e;
            }
            flash('error', 'This customer has online orders and cannot be deleted.');
        }
        redirect(url('/customers', ['page' => (int)input('page', 0) ?: null]));
    }
}
