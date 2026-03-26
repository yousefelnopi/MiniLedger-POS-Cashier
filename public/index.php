<?php
date_default_timezone_set('Africa/Cairo');
/*
|--------------------------------------------------------------------------
| Autoloader
|--------------------------------------------------------------------------
*/
spl_autoload_register(function (string $class): void {
    
    $base = __DIR__ . '/../';

    // App\Database\Migrations\CreateTables → database/migrations/CreateTables.php

    $path = $base . str_replace(
        ['App\\Database\\', 'App\\', '\\'],
        ['database/',       'app/',  '/'],
        $class
    ) . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/
session_start();

/*
|--------------------------------------------------------------------------
| Imports
|--------------------------------------------------------------------------
*/

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\CustomerController;
use App\Controllers\InvoiceController;
use App\Controllers\PosController;
use App\Controllers\SearchController;
use App\Controllers\SyncController;

/*
|--------------------------------------------------------------------------
| Auth Guard — connect to cashier DB if logged in
|--------------------------------------------------------------------------
*/

$publicRoutes = [
    'GET:/auth/login',
    'POST:/auth/login',
    'GET:/',
];

$uri          = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath     = '/MiniLedger/CASHIER/public';
$uri          = '/' . trim(str_replace($basePath, '', $uri), '/');

$currentRoute = $_SERVER['REQUEST_METHOD'] . ':' . $uri;

// If logged in → connect to cashier's DB automatically
if (Auth::check()) {
    $email  = Auth::email();
    $dbName = explode('@', $email)[0];
    $dbPath = Config::get('db_storage') . $dbName . '.sqlite';

    if (file_exists($dbPath)) {
        Database::connect($email);
    } else {
        // DB missing — force logout
        Auth::logout();
    }
}

// If not logged in and not a public route → redirect to login
if (!Auth::check() && !in_array($currentRoute, $publicRoutes)) {
    header('Location: ' . $basePath . '/auth/login');
    exit;
}

// If logged in and trying to access login → redirect to dashboard
if (Auth::check() && in_array($currentRoute, ['GET:/auth/login', 'GET:/'])) {
    header('Location: ' . $basePath . '/pos/dashboard');
    exit;
}

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/
$router = new Router();

// ── Auth ──────────────────────────────────────────────────────────────
$router->get('/auth/login',  [AuthController::class, 'showLogin']);
$router->post('/auth/login',  [AuthController::class, 'login']);
$router->post('/auth/logout', [AuthController::class, 'logout']);

// ── POS ───────────────────────────────────────────────────────────────
$router->get('/pos/dashboard', [PosController::class, 'dashboard']);
$router->get('/pos/products', [PosController::class, 'categoryProducts']);
$router->get('/pos/product', [PosController::class, 'productDetails']);
$router->get('/pos/cart',      [PosController::class, 'cart']);
$router->get('/pos/loading',   [PosController::class, 'loading']); // ← جديد


// ── Cart ──────────────────────────────────────────────────────────────
$router->post('/cart/add',    [PosController::class, 'addToCart']);
$router->post('/cart/update', [PosController::class, 'updateCart']);
$router->post('/cart/remove', [PosController::class, 'removeFromCart']);

// ── Invoice ───────────────────────────────────────────────────────────
$router->post('/invoice/store', [InvoiceController::class, 'store']);
$router->get('/invoice/preview/{id}', [InvoiceController::class, 'preview']);
$router->get('/invoices', [InvoiceController::class, 'index']);

// ── Inventory ─────────────────────────────────────────────────────────


$router->post('/inventory/request-adjust', [SyncController::class, 'requestAdjust']);

// ── Sync ──────────────────────────────────────────────────────────────
$router->post('/sync/run',    [SyncController::class, 'run']);
$router->get('/sync/status', [SyncController::class, 'status']);
$router->post('/sync/pull',    [SyncController::class, 'pull']); // ← جديد


// ── Search ────────────────────────────────────────────────────────────
$router->get('/search/products',           [SearchController::class, 'products']);
$router->get('/search/products/car-model', [SearchController::class, 'byCarModel']);
$router->get('/search/car-models',         [SearchController::class, 'carModels']);

// ── Customers ──────────────────────────────────────────────────────────────
$router->post('/customer/store', [CustomerController::class, 'store']);

// ── Home ──────────────────────────────────────────────────────────────
$router->get('/', [AuthController::class, 'showLogin']);

/*
|--------------------------------------------------------------------------
| Run
|--------------------------------------------------------------------------
*/
$router->dispatch();

/*
Application Entry Flow (index.php)

Every incoming request follows this sequence:

Request
      ↓
Autoloader
    Automatically loads required classes.
      ↓
session_start()
    Initialize PHP session handling.
      ↓
Authentication Check

If the user is logged in:
    Database::connect(email) → Connect to the user's local SQLite database.

If the database does not exist:
    Force logout and redirect to login page.
      ↓
Route Access Control

If the route is public:
    Allow the request to continue.

If the user is not logged in and the route is not public:
    Redirect to /auth/login.

If the user is logged in and tries to access /login:
    Redirect to /pos/dashboard.
      ↓
Router::dispatch()
    Forward the request to the appropriate controller and action.
*/
