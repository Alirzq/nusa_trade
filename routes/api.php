<?php

use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminProductController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SellerOrderController;
use App\Http\Controllers\Api\SellerProductController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

// Semua route di file ini otomatis berawalan /api
// Hanya ada dua jenis akun: admin dan seller. Pembeli tidak punya akun.

// ---------- Autentikasi (admin & seller) ----------
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1'); // hanya membuat seller
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// ---------- Publik (tanpa login) ----------
Route::get('categories', [CategoryController::class, 'index']);
Route::get('units', [CategoryController::class, 'units']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);

// ---------- Pembeli (tanpa akun, memakai token pesanan di header X-Order-Token) ----------
Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:20,1');
Route::middleware('throttle:60,1')->group(function () {
    Route::get('orders/{code}', [OrderController::class, 'show']);
    Route::post('orders/{code}/pay', [OrderController::class, 'pay']);
    Route::post('orders/{code}/cancel', [OrderController::class, 'cancel']);
    Route::post('orders/{code}/complete', [OrderController::class, 'complete']);
});

// ---------- Wajib login ----------
Route::middleware('auth:sanctum')->group(function () {

    // Seller
    Route::middleware(EnsureRole::class.':seller')->prefix('seller')->group(function () {
        Route::get('products', [SellerProductController::class, 'index']);
        Route::post('products', [SellerProductController::class, 'store']);
        Route::match(['put', 'patch'], 'products/{product}', [SellerProductController::class, 'update']);
        Route::delete('products/{product}', [SellerProductController::class, 'destroy']);

        Route::get('orders', [SellerOrderController::class, 'index']);
        Route::patch('orders/{order}/ship', [SellerOrderController::class, 'ship']);
    });

    // Admin
    Route::middleware(EnsureRole::class.':admin')->prefix('admin')->group(function () {
        Route::get('users', [AdminUserController::class, 'index']);
        Route::post('users', [AdminUserController::class, 'store']);

        Route::post('categories', [AdminCategoryController::class, 'store']);
        Route::match(['put', 'patch'], 'categories/{category}', [AdminCategoryController::class, 'update']);
        Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy']);

        Route::get('products', [AdminProductController::class, 'index']);
        Route::delete('products/{product}', [AdminProductController::class, 'destroy']);

        Route::get('orders', [AdminOrderController::class, 'index']);
        Route::get('orders/{order}', [AdminOrderController::class, 'show']);
    });
});
