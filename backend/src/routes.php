<?php
/**
 * Route table.
 *
 * Options: 'auth' => 'public' | 'user' (default) | 'admin' | 'customer'
 *          'area' => 'shop'                 (customer portal session cookie)
 *          'allow_password_change' => true  (reachable while a password change is pending)
 *          'session' => false               (no PHP session for this endpoint)
 *
 * @var App\Core\Router $router
 */

use App\Controllers\AccountController;
use App\Controllers\AdminRecoveryController;
use App\Controllers\AuthController;
use App\Controllers\CustomerAuthController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\ExpenseController;
use App\Controllers\HealthController;
use App\Controllers\InventoryController;
use App\Controllers\NotificationController;
use App\Controllers\OnlineOrderController;
use App\Controllers\ProductController;
use App\Controllers\PurchaseController;
use App\Controllers\ReceiptController;
use App\Controllers\ReportController;
use App\Controllers\SalesController;
use App\Controllers\SettingsController;
use App\Controllers\ShopController;
use App\Controllers\SlideController;
use App\Controllers\SupplierController;

$public = ['auth' => 'public'];
$admin = ['auth' => 'admin'];

// --- Public -----------------------------------------------------------------
$router->get('/',                [AuthController::class, 'showLogin'], $public);
$router->post('/login',          [AuthController::class, 'login'], $public);
$router->get('/logout',          [AuthController::class, 'logout'], $public);
$router->get('/admin-recovery',  [AdminRecoveryController::class, 'show'], $public);
$router->post('/admin-recovery', [AdminRecoveryController::class, 'reset'], $public);
$router->get('/slides/image',    [SlideController::class, 'image'], ['auth' => 'public', 'session' => false]);
$router->get('/health',          [HealthController::class, 'check'], ['auth' => 'public', 'session' => false]);

// --- Customer portal (own session cookie) -----------------------------------
$shopPublic = ['auth' => 'public', 'area' => 'shop'];
$customer = ['auth' => 'customer', 'area' => 'shop'];
$router->get('/shop',           [ShopController::class, 'home'], $shopPublic);
$router->post('/shop/login',    [CustomerAuthController::class, 'login'], $shopPublic);
$router->get('/shop/register',  [CustomerAuthController::class, 'showRegister'], $shopPublic);
$router->post('/shop/register', [CustomerAuthController::class, 'register'], $shopPublic);
$router->get('/shop/logout',    [CustomerAuthController::class, 'logout'], $shopPublic);
$router->get('/shop/cart',      [ShopController::class, 'cart'], $customer);
$router->post('/shop/cart',     [ShopController::class, 'updateCart'], $customer);
$router->get('/shop/checkout',  [ShopController::class, 'checkout'], $customer);
$router->post('/shop/checkout', [ShopController::class, 'placeOrder'], $customer);
$router->get('/shop/orders',    [ShopController::class, 'orders'], $customer);
$router->get('/shop/order',     [ShopController::class, 'order'], $customer);

// --- Any signed-in user (Admin + Cashier) -----------------------------------
$router->get('/admin',                 [DashboardController::class, 'entry']);
$router->get('/session/ping',          [AuthController::class, 'ping'], ['allow_password_change' => true]);
$router->get('/dashboard',             [DashboardController::class, 'index']);
$router->get('/sales',                 [SalesController::class, 'index']);
$router->post('/sales',                [SalesController::class, 'store']);
$router->get('/receipt',               [ReceiptController::class, 'show']);
$router->get('/customers',             [CustomerController::class, 'index']);
$router->post('/customers',            [CustomerController::class, 'save']);
$router->post('/customers/delete',     [CustomerController::class, 'delete']);
$router->get('/reports',               [ReportController::class, 'index']);
$router->get('/notifications',         [NotificationController::class, 'index']);
$router->get('/notifications/history', [NotificationController::class, 'history']);
$router->post('/notifications/clear',  [NotificationController::class, 'clear']);
$router->get('/account/password',      [AccountController::class, 'showPassword'], ['allow_password_change' => true]);
$router->post('/account/password',     [AccountController::class, 'updatePassword'], ['allow_password_change' => true]);

// --- Admin only -------------------------------------------------------------
$router->get('/online-orders',         [OnlineOrderController::class, 'index'], $admin);
$router->post('/online-orders/status', [OnlineOrderController::class, 'updateStatus'], $admin);
$router->get('/products',          [ProductController::class, 'index'], $admin);
$router->post('/products',         [ProductController::class, 'save'], $admin);
$router->post('/products/delete',  [ProductController::class, 'delete'], $admin);
$router->get('/inventory',         [InventoryController::class, 'index'], $admin);
$router->get('/purchases',         [PurchaseController::class, 'index'], $admin);
$router->post('/purchases',        [PurchaseController::class, 'store'], $admin);
$router->get('/suppliers',         [SupplierController::class, 'index'], $admin);
$router->post('/suppliers',        [SupplierController::class, 'save'], $admin);
$router->post('/suppliers/delete', [SupplierController::class, 'delete'], $admin);
$router->get('/expenses',          [ExpenseController::class, 'index'], $admin);
$router->post('/expenses',         [ExpenseController::class, 'store'], $admin);
$router->post('/expenses/delete',  [ExpenseController::class, 'delete'], $admin);
$router->get('/reports/export',    [ReportController::class, 'export'], $admin);
$router->get('/settings',          [SettingsController::class, 'index'], $admin);
$router->post('/settings',         [SettingsController::class, 'handle'], $admin);
$router->get('/account/name',      [AccountController::class, 'showName'], $admin);
$router->post('/account/name',     [AccountController::class, 'updateName'], $admin);

// --- Old v1 URLs keep working (301 redirect) --------------------------------
$router->legacy([
    '/index.php'                 => '/',
    '/login.php'                 => '/',
    '/logout.php'                => '/logout',
    '/admin-recovery.php'        => '/admin-recovery',
    '/login_slide_image.php'     => '/slides/image',
    '/dashboard.php'             => '/dashboard',
    '/sales.php'                 => '/sales',
    '/receipt.php'               => '/receipt',
    '/customers.php'             => '/customers',
    '/reports.php'               => '/reports',
    '/report_export.php'         => '/reports/export',
    '/notifications.php'         => '/notifications',
    '/notifications_history.php' => '/notifications/history',
    '/change_password.php'       => '/account/password',
    '/change_name.php'           => '/account/name',
    '/products.php'              => '/products',
    '/inventory.php'             => '/inventory',
    '/purchases.php'             => '/purchases',
    '/suppliers.php'             => '/suppliers',
    '/expenses.php'              => '/expenses',
    '/settings.php'              => '/settings',
    '/online_orders.php'         => '/online-orders',
    '/online_orders_v5.php'      => '/online-orders',
    '/admin_entry'               => '/admin',
    '/admin_entry/index.php'     => '/admin',
    // v1 customer portal URLs
    '/portal'                    => '/shop',
    '/portal/index.php'          => '/shop',
    '/customer_portal'           => '/shop',
    '/customer_portal/index.php' => '/shop',
    '/shop/index.php'            => '/shop',
    '/shop/shop.php'             => '/shop',
    '/shop/register.php'         => '/shop/register',
    '/shop/cart.php'             => '/shop/cart',
    '/shop/checkout.php'         => '/shop/checkout',
    '/shop/orders.php'           => '/shop/orders',
    '/shop/order.php'            => '/shop/order',
    '/shop/logout.php'           => '/shop/logout',
]);
