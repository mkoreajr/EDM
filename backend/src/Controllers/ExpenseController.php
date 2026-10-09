<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;

final class ExpenseController
{
    public function index(): void
    {
        view('expenses/index', [
            'pageTitle' => 'Expenses',
            'active'    => 'expenses',
            'expenses'  => DB::all('SELECT * FROM expenses ORDER BY expense_date DESC, id DESC LIMIT 500'),
            'error'     => flash('error'),
            'success'   => flash('success'),
        ]);
    }

    public function store(): void
    {
        $name = (string)input('expense_name');
        $amount = (float)input('amount', 0);
        $date = (string)input('expense_date');
        $description = (string)input('description');

        if ($name === '' || mb_strlen($name) > 100 || $amount < 0 || !valid_date($date)) {
            flash('error', 'Please enter an expense name, a valid amount and a valid date.');
            redirect('/expenses');
        }

        DB::execute(
            'INSERT INTO expenses (expense_name, amount, expense_date, description, created_by) VALUES (?, ?, ?, ?, ?)',
            [$name, $amount, $date, $description, Auth::id()]
        );
        flash('success', 'Expense saved successfully.');
        redirect('/expenses');
    }

    public function delete(): void
    {
        DB::execute('DELETE FROM expenses WHERE id = ?', [(int)input('id', 0)]);
        flash('success', 'Expense deleted.');
        redirect('/expenses');
    }
}
