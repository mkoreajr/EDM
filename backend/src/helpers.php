<?php
/**
 * Global helper functions available to controllers and views.
 */

use App\Core\Csrf;
use App\Core\View;

/* ---------------------------------------------------------------------------
 | Environment
 * ------------------------------------------------------------------------- */

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }
    return ($value === null || $value === '') ? $default : (string)$value;
}

function is_production(): bool
{
    return env('APP_ENV', 'production') === 'production';
}

function app_debug(): bool
{
    return !is_production() && env('APP_DEBUG', '0') === '1';
}

/* ---------------------------------------------------------------------------
 | Output / formatting
 * ------------------------------------------------------------------------- */

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($value): string
{
    return number_format((float)$value, 2);
}

/** Whole-unit quantity formatting, e.g. "1,250". */
function qty($value): string
{
    return number_format((float)$value, 0);
}

/** "1 tray" / "3 trays" / "2 bags" depending on the product category. */
function unit_label(?string $category, $quantity): string
{
    $word = ($category === 'Eggs') ? 'tray' : 'bag';
    $n = (float)$quantity;
    return qty($n) . ' ' . $word . (abs($n - 1.0) < 0.00001 ? '' : 's');
}

/** "Tray" for eggs, "5 Kg Bag" for rice/flour. */
function package_label(array $product, string $bagSuffix = ' Kg Bag'): string
{
    if (($product['category'] ?? 'Eggs') === 'Eggs') {
        return 'Tray';
    }
    return qty($product['package_size_kg'] ?? 0) . $bagSuffix;
}

/* ---------------------------------------------------------------------------
 | URLs, assets, redirects
 * ------------------------------------------------------------------------- */

function url(string $path = '/', array $query = []): string
{
    $path = '/' . ltrim($path, '/');
    $query = array_filter($query, static fn($v) => $v !== null && $v !== '');
    return $query ? $path . '?' . http_build_query($query) : $path;
}

/** Versioned asset URL so browsers can cache static files for a year. */
function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/') . '?v=' . rawurlencode(asset_version());
}

/**
 * Cache-busting token that changes on every deploy:
 * ASSET_VERSION env > Render git commit > Docker build id > APP_VERSION.
 * In development it changes on every request so edits show immediately.
 */
function asset_version(): string
{
    static $version = null;
    if ($version !== null) {
        return $version;
    }
    if (!is_production()) {
        return $version = (string)time();
    }
    $buildFile = BASE_PATH . '/.build-id';
    $version = env('ASSET_VERSION')
        ?? (env('RENDER_GIT_COMMIT') !== null ? substr((string)env('RENDER_GIT_COMMIT'), 0, 10) : null)
        ?? (is_file($buildFile) ? trim((string)file_get_contents($buildFile)) : null)
        ?? APP_VERSION;
    return $version;
}

function redirect(string $to, int $status = 303): void
{
    header('Location: ' . $to, true, $status);
    exit;
}

/** Send a JSON body with the given status code and stop. */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** True for fetch()/XHR calls that expect a JSON answer instead of a redirect. */
function wants_json(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function abort(int $code, string $message = ''): void
{
    $titles = [403 => 'Access denied', 404 => 'Page not found', 405 => 'Method not allowed', 419 => 'Session expired', 429 => 'Too many requests'];
    http_response_code($code);
    View::render('errors/error', [
        'code'    => $code,
        'title'   => $titles[$code] ?? 'Error',
        'message' => $message !== '' ? $message : 'The page you requested could not be shown.',
    ], null);
    exit;
}

/* ---------------------------------------------------------------------------
 | Request helpers
 * ------------------------------------------------------------------------- */

function input(string $key, $default = '')
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

function request_is_https(): bool
{
    return (($_SERVER['HTTPS'] ?? '') === 'on')
        || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function client_ip(): string
{
    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($forwarded !== '') {
        $first = trim(explode(',', $forwarded)[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) {
            return $first;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function valid_date(string $value): bool
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
        return false;
    }
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}

/* ---------------------------------------------------------------------------
 | Session flash messages & CSRF
 * ------------------------------------------------------------------------- */

/** Set a one-time message (two args) or read-and-forget it (one arg). */
function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $stored = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $stored;
}

function csrf_field(): string
{
    return Csrf::field();
}

/* ---------------------------------------------------------------------------
 | Views & pagination
 * ------------------------------------------------------------------------- */

function view(string $template, array $data = [], ?string $layout = 'layouts/app'): void
{
    View::render($template, $data, $layout);
}

function partial(string $template, array $data = []): void
{
    echo View::capture($template, $data);
}

/**
 * Compute pagination numbers for a list.
 * @return array{page:int,perPage:int,total:int,pages:int,offset:int}
 */
function paginate(int $total, int $perPage, int $requestedPage): array
{
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, $requestedPage), $pages);
    return ['page' => $page, 'perPage' => $perPage, 'total' => $total, 'pages' => $pages, 'offset' => ($page - 1) * $perPage];
}

/* ---------------------------------------------------------------------------
 | HTTP security headers
 * ------------------------------------------------------------------------- */

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com; "
        . "style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; "
        . "frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none'");
    if (request_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
