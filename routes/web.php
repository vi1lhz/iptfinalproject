<?php

/**
 * Route definitions. Every URL of the application is listed here.
 *
 * @var Core\Router $router
 */

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\GameController;
use App\Controllers\HomeController;
use App\Controllers\ItemController;
use App\Controllers\OrderController;
use App\Controllers\SellerController;

// Short names for middleware, used below.
$router->aliasMiddleware('log',   App\Middleware\LoggerMiddleware::class);
$router->aliasMiddleware('share', App\Middleware\ShareViewData::class);
$router->aliasMiddleware('csrf',  App\Middleware\CsrfMiddleware::class);
$router->aliasMiddleware('auth',  App\Middleware\AuthMiddleware::class);
$router->aliasMiddleware('guest', App\Middleware\GuestMiddleware::class);
$router->aliasMiddleware('admin', App\Middleware\AdminMiddleware::class);

// Every web route: log the request, share layout data, check CSRF on forms.
$router->group(['middleware' => ['log', 'share', 'csrf']], function ($router) {

    // ---------- Public pages
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/browse', [ItemController::class, 'index']);
    $router->get('/category/{slug}', [ItemController::class, 'category']);
    $router->get('/items/{slug}', [ItemController::class, 'show']);
    $router->get('/sellers/{username}', [SellerController::class, 'profile']);
    $router->get('/games', [GameController::class, 'index']);
    $router->get('/games/{id}', [GameController::class, 'show']);

    // ---------- Authentication
    $router->get('/login', [AuthController::class, 'showLogin'])->middleware('guest');
    $router->post('/login', [AuthController::class, 'login'])->middleware('guest');
    $router->get('/register', [AuthController::class, 'showRegister'])->middleware('guest');
    $router->post('/register', [AuthController::class, 'register'])->middleware('guest');
    $router->post('/logout', [AuthController::class, 'logout'])->middleware('auth');

    // ---------- Logged-in buyers
    $router->group(['middleware' => ['auth']], function ($router) {
        $router->get('/cart', [CartController::class, 'index']);
        $router->post('/cart/add/{id}', [CartController::class, 'add']);
        $router->post('/cart/update/{id}', [CartController::class, 'update']);
        $router->post('/cart/remove/{id}', [CartController::class, 'remove']);

        $router->get('/checkout', [CheckoutController::class, 'show']);
        $router->post('/checkout', [CheckoutController::class, 'place']);

        $router->get('/orders', [OrderController::class, 'index']);
        $router->get('/orders/{number}', [OrderController::class, 'show']);
        $router->post('/orders/items/{id}/confirm', [OrderController::class, 'confirm']);
        $router->post('/orders/items/{id}/review', [OrderController::class, 'review']);
        $router->get('/orders/items/{id}/download', [OrderController::class, 'download']);

        // ---------- Selling (any logged-in user can sell)
        $router->get('/sell', [SellerController::class, 'dashboard']);
        $router->get('/sell/new', [SellerController::class, 'create']);
        $router->post('/sell', [SellerController::class, 'store']);
        $router->get('/sell/{id}/edit', [SellerController::class, 'edit']);
        $router->post('/sell/{id}', [SellerController::class, 'update']);
        $router->post('/sell/{id}/delete', [SellerController::class, 'destroy']);
    });

    // ---------- Admin panel
    $router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function ($router) {
        $router->get('', [AdminController::class, 'dashboard']);
        $router->get('/users', [AdminController::class, 'users']);
        $router->post('/users/{id}/ban', [AdminController::class, 'toggleBan']);
        $router->post('/users/{id}/verify', [AdminController::class, 'toggleVerify']);
        $router->get('/items', [AdminController::class, 'items']);
        $router->post('/items/{id}/status', [AdminController::class, 'itemStatus']);
        $router->get('/categories', [AdminController::class, 'categories']);
        $router->post('/categories', [AdminController::class, 'storeCategory']);
        $router->post('/categories/{id}/delete', [AdminController::class, 'deleteCategory']);
        $router->get('/orders', [AdminController::class, 'orders']);
        $router->post('/games/sync', [AdminController::class, 'syncGames']);
    });
});

// ---------- JSON API (no session/CSRF needed – read-only)
$router->group(['prefix' => '/api', 'middleware' => ['log']], function ($router) {
    $router->get('/games', [GameController::class, 'api']);
    $router->get('/items', [ItemController::class, 'apiIndex']);
});
