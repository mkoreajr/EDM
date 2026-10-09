<?php

namespace App\Controllers;

use App\Core\CustomerAuth;
use App\Core\DB;
use App\Services\LoginThrottle;
use App\Services\Password;
use PDOException;
use Throwable;

/**
 * Customer portal sign in, registration and sign out (/shop).
 */
final class CustomerAuthController
{
    public function login(): void
    {
        $username = mb_substr((string)input('username'), 0, 80);
        $password = (string)($_POST['password'] ?? '');
        // Customer and staff usernames are separate, so throttle them separately too.
        $throttleKey = 'shop:' . $username;
        $ip = client_ip();

        flash('username', $username);

        if ($username === '' || $password === '') {
            flash('error', 'Please enter your username and password.');
            redirect('/shop');
        }

        if (LoginThrottle::tooManyAttempts($throttleKey, $ip)) {
            flash('error', 'Too many failed sign-in attempts. Please wait 15 minutes and try again.');
            redirect('/shop');
        }

        $account = DB::one('SELECT id, customer_id, password FROM customer_accounts WHERE username = ? LIMIT 1', [$username]);
        if ($account === null || !Password::verify($password, (string)$account['password'])) {
            LoginThrottle::recordFailure($throttleKey, $ip);
            flash('error', 'Invalid username or password. Please check your details and try again.');
            redirect('/shop');
        }

        LoginThrottle::clear($throttleKey);
        flash('username');
        $hash = Password::needsRehash((string)$account['password']) ? Password::hash($password) : $account['password'];
        DB::execute('UPDATE customer_accounts SET password = ?, last_login_at = CURRENT_TIMESTAMP WHERE id = ?', [$hash, $account['id']]);

        CustomerAuth::login((int)$account['customer_id']);
        redirect('/shop');
    }

    public function showRegister(): void
    {
        if (CustomerAuth::check()) {
            redirect('/shop');
        }
        view('shop/register', [
            'error' => flash('error'),
            'old'   => $_SESSION['register_old'] ?? [],
        ], null);
        unset($_SESSION['register_old']);
    }

    public function register(): void
    {
        $data = [
            'name'     => (string)input('name'),
            'phone'    => (string)input('phone'),
            'address'  => (string)input('address'),
            'username' => (string)input('username'),
        ];
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm'] ?? '');

        $error = null;
        if (in_array('', $data, true) || $password === '' || $confirm === '') {
            $error = 'Please fill in all required fields.';
        } elseif (mb_strlen($data['name']) > 100 || mb_strlen($data['phone']) > 30 || mb_strlen($data['address']) > 255) {
            $error = 'Name (max 100), phone (max 30) or address (max 255) is too long.';
        } elseif (!preg_match('/^[A-Za-z0-9._-]{4,80}$/', $data['username'])) {
            $error = 'Username must be 4-80 characters and use letters, numbers, dot, underscore or hyphen.';
        } elseif (!Password::isStrong($password)) {
            $error = Password::RULES;
        } elseif ($password !== $confirm) {
            $error = 'Password and Confirm Password do not match.';
        } elseif (DB::value('SELECT 1 FROM customer_accounts WHERE username = ?', [$data['username']])) {
            $error = 'That username is already in use. Please choose another one.';
        }

        if ($error === null) {
            try {
                $customerId = DB::transaction(static function () use ($data, $password): int {
                    $id = (int)DB::value(
                        'INSERT INTO customers (name, phone, address) VALUES (?, ?, ?) RETURNING id',
                        [$data['name'], $data['phone'], $data['address']]
                    );
                    DB::execute(
                        'INSERT INTO customer_accounts (customer_id, username, password) VALUES (?, ?, ?)',
                        [$id, $data['username'], Password::hash($password)]
                    );
                    return $id;
                });
                CustomerAuth::login($customerId);
                redirect('/shop');
            } catch (PDOException $e) {
                // 23505 = unique violation: someone took the username a moment ago.
                $error = $e->getCode() === '23505'
                    ? 'That username is already in use. Please choose another one.'
                    : 'Could not create your account. Please try again.';
                if ($e->getCode() !== '23505') {
                    error_log((string)$e);
                }
            } catch (Throwable $e) {
                error_log((string)$e);
                $error = 'Could not create your account. Please try again.';
            }
        }

        flash('error', $error);
        $_SESSION['register_old'] = $data;
        redirect('/shop/register');
    }

    public function logout(): void
    {
        CustomerAuth::logout();
        redirect('/shop');
    }
}
