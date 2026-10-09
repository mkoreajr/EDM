<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Services\Password;

final class AccountController
{
    public function showPassword(): void
    {
        $required = Auth::mustChangePassword() || input('required', '') !== '';

        view('account/password', [
            'pageTitle' => 'Change Password',
            'active'    => 'settings',
            'required'  => $required,
            'bodyClass' => $required ? 'password-required-mode' : '',
            'error'     => flash('error'),
            'success'   => flash('success'),
        ]);
    }

    public function updatePassword(): void
    {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        $back = Auth::mustChangePassword() ? '/account/password?required=1' : '/account/password';

        $hash = (string)DB::value('SELECT password FROM users WHERE id = ?', [Auth::id()]);

        $error = null;
        if ($hash === '' || !Password::verify($current, $hash)) {
            $error = 'Current password is incorrect.';
        } elseif (!Password::isStrong($new)) {
            $error = 'New ' . lcfirst(Password::RULES);
        } elseif ($new !== $confirm) {
            $error = 'New password and confirmation do not match.';
        } elseif (Password::verify($new, $hash)) {
            $error = 'New password must be different from the current password.';
        }

        if ($error !== null) {
            flash('error', $error);
            redirect($back);
        }

        DB::execute(
            'UPDATE users SET password = ?, must_change_password = FALSE WHERE id = ?',
            [Password::hash($new), Auth::id()]
        );
        session_regenerate_id(true);
        flash('success', 'Password changed successfully. You can now enter the system.');
        redirect('/account/password');
    }

    public function showName(): void
    {
        view('account/name', [
            'pageTitle' => 'Change Name',
            'active'    => 'settings',
            'error'     => flash('error'),
            'success'   => flash('success'),
        ]);
    }

    public function updateName(): void
    {
        $name = (string)input('name');
        if ($name === '') {
            flash('error', 'Name is required.');
        } elseif (mb_strlen($name) > 100) {
            flash('error', 'Name is too long.');
        } else {
            DB::execute('UPDATE users SET name = ? WHERE id = ?', [$name, Auth::id()]);
            flash('success', 'Administrator name updated successfully. The Admin role remains unchanged.');
        }
        redirect('/account/name');
    }
}
