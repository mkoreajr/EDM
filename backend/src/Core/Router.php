<?php

namespace App\Core;

/**
 * Exact-path router with built-in auth, role and CSRF middleware.
 *
 * Route options:
 *   auth    'user' (default) | 'admin' | 'public' | 'customer'
 *   area    'admin' (default) | 'shop' — which session cookie the route uses
 *   session false to skip starting a PHP session (static-like endpoints)
 *   allow_password_change  true for routes reachable while a password change is pending
 */
final class Router
{
    /** @var array<string, array<string, array{handler: array{0:class-string,1:string}, options: array}>> */
    private array $routes = [];

    /** @var array<string,string> old .php URL => new clean URL */
    private array $legacy = [];

    public function get(string $path, array $handler, array $options = []): void
    {
        $this->add('GET', $path, $handler, $options);
    }

    public function post(string $path, array $handler, array $options = []): void
    {
        $this->add('POST', $path, $handler, $options);
    }

    /** Permanently redirect bookmarked legacy URLs (e.g. /sales.php) to their new routes. */
    public function legacy(array $map): void
    {
        $this->legacy = $map + $this->legacy;
    }

    private function add(string $method, string $path, array $handler, array $options): void
    {
        $this->routes[$path][$method] = ['handler' => $handler, 'options' => $options];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = rawurldecode((string)(parse_url($uri, PHP_URL_PATH) ?: '/'));
        $path = rtrim($path, '/') ?: '/';

        if (isset($this->legacy[$path]) && in_array($method, ['GET', 'HEAD'], true)) {
            $query = (string)(parse_url($uri, PHP_URL_QUERY) ?? '');
            redirect($this->legacy[$path] . ($query !== '' ? '?' . $query : ''), 301);
        }

        $methods = $this->routes[$path] ?? null;
        if ($methods === null) {
            Session::start();
            abort(404);
        }

        $route = $methods[$method] ?? ($method === 'HEAD' ? ($methods['GET'] ?? null) : null);
        if ($route === null) {
            header('Allow: ' . implode(', ', array_keys($methods)));
            abort(405);
        }

        $options = $route['options'];
        if (($options['session'] ?? true) !== false) {
            Session::start($options['area'] ?? Session::AREA_ADMIN);
        }

        if ($method === 'POST' && !Csrf::isValid($_POST['_token'] ?? null)) {
            $this->rejectInvalidToken($path);
        }

        $this->authorize($options);

        [$class, $action] = $route['handler'];
        (new $class())->{$action}();
    }

    private function authorize(array $options): void
    {
        $level = $options['auth'] ?? 'user';
        if ($level === 'public') {
            return;
        }

        if ($level === 'customer') {
            $this->authorizeCustomer();
            return;
        }

        if (!Auth::check()) {
            $this->deny('Your session has expired. Please log in again.', '/');
        }

        if (Auth::expired()) {
            Auth::logout();
            $this->deny('Your session has expired. Please log in again.', '/?timeout=1');
        }

        if (Auth::mustChangePassword() && empty($options['allow_password_change'])) {
            $this->deny('Please change your password first.', '/account/password?required=1', 403);
        }

        if ($level === 'admin' && !Auth::isAdmin()) {
            $this->deny('Administrator access is required.', '/dashboard', 403);
        }
    }

    private function authorizeCustomer(): void
    {
        if (!CustomerAuth::check()) {
            flash('error', 'Please sign in to access your customer account.');
            redirect('/shop');
        }
        if (CustomerAuth::expired()) {
            CustomerAuth::logout();
            redirect('/shop?timeout=1');
        }
    }

    /** Redirect a page request, or answer a fetch() call with a JSON error. */
    private function deny(string $message, string $to, int $status = 401): void
    {
        if (wants_json()) {
            json_response(['ok' => false, 'message' => $message], $status);
        }
        redirect($to);
    }

    private function rejectInvalidToken(string $path): void
    {
        if (wants_json()) {
            json_response(['ok' => false, 'message' => 'Your session expired. Please reload the page and try again.'], 419);
        }
        $tooLarge = empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
        flash('error', $tooLarge
            ? 'The uploaded file is too large. Please choose a smaller file.'
            : 'Your session expired. Please try again.');
        redirect($path);
    }
}
