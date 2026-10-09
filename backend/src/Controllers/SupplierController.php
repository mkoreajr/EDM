<?php

namespace App\Controllers;

use App\Core\DB;

final class SupplierController
{
    public function index(): void
    {
        $edit = null;
        if (input('edit', '') !== '') {
            $edit = DB::one('SELECT * FROM suppliers WHERE id = ?', [(int)input('edit')]);
        }

        view('suppliers/index', [
            'pageTitle' => 'Suppliers',
            'active'    => 'suppliers',
            'edit'      => $edit,
            'suppliers' => DB::all('SELECT * FROM suppliers ORDER BY name'),
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
            flash('error', 'Please enter a supplier name (max 100 characters), phone (max 30) and address (max 255).');
            redirect(url('/suppliers', ['edit' => $id ?: null]));
        }

        if ($id > 0) {
            DB::execute('UPDATE suppliers SET name = ?, phone = ?, address = ? WHERE id = ?', [$name, $phone, $address, $id]);
        } else {
            DB::execute('INSERT INTO suppliers (name, phone, address) VALUES (?, ?, ?)', [$name, $phone, $address]);
        }

        flash('success', 'Supplier saved successfully.');
        redirect('/suppliers');
    }

    public function delete(): void
    {
        DB::execute('DELETE FROM suppliers WHERE id = ?', [(int)input('id', 0)]);
        flash('success', 'Supplier deleted.');
        redirect('/suppliers');
    }
}
