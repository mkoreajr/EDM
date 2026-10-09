<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Services\LoginThrottle;
use App\Services\Password;
use Throwable;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }

        try {
            $slides = DB::all('SELECT id, filename FROM login_slides ORDER BY sort_order ASC, id ASC');
        } catch (Throwable $e) {
            $slides = [];
        }

        view('auth/login', [
            'slides'   => $slides,
            'error'    => flash('error') ?? (input('timeout', '') !== '' ? 'You were signed out after a period of inactivity. Please log in again.' : null),
            'username' => flash('username') ?? '',
        ], null);
    }

    public function login(): void
    {
        $username = mb_substr((string)input('username'), 0, 50);
        $password = (string)($_POST['password'] ?? '');
        $ip = client_ip();

        flash('username', $username);

        if (LoginThrottle::tooManyAttempts($username, $ip)) {
            flash('error', 'Too many failed login attempts. Please wait 15 minutes and try again.');
            redirect('/');
        }

        $user = DB::one('SELECT id, password, must_change_password FROM users WHERE username = ? LIMIT 1', [$username]);

        if ($user === null || $password === '' || !Password::verify($password, (string)$user['password'])) {
            LoginThrottle::recordFailure($username, $ip);
            flash('error', 'Invalid username or password.');
            redirect('/');
        }

        LoginThrottle::clear($username);
        flash('username'); // consume the "remember what was typed" value
        if (Password::needsRehash((string)$user['password'])) {
            DB::execute('UPDATE users SET password = ? WHERE id = ?', [Password::hash($password), $user['id']]);
        }

        Auth::login((int)$user['id']);
        redirect(!empty($user['must_change_password']) ? '/account/password?required=1' : '/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect(input('timeout', '') !== '' ? '/?timeout=1' : '/');
    }

    /** Keep-alive from app.js while the user is active on a page; the router records the activity. */
    public function ping(): void
    {
        http_response_code(204);
    }
}
