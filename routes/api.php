<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

// books
Route::get('books', [BookController::class, 'index']);
Route::get('books/{id}', [BookController::class, 'show']);

// categories
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{id}', [CategoryController::class, 'show']);

Route::middleware('auth.jwt')->group(function () {
    // auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
    Route::put('update-profile', [AuthController::class, 'updateProfile']);
    Route::post('change-password', [AuthController::class, 'changePassword']);

    // cart 
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart/sync', [CartController::class, 'sync']);
    Route::post('cart/add', [CartController::class, 'store']);
    Route::delete('cart/{id}', [CartController::class, 'destroy']);

    // orders
    Route::post('checkout', [OrderController::class, 'checkout']);
    Route::get('my-orders', [OrderController::class, 'index']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::post('orders/{id}/cancel', [OrderController::class, 'cancel']);

    Route::middleware('admin')->group(function () {
        // books
        Route::post('books', [BookController::class, 'store']);
        Route::put('books/{id}', [BookController::class, 'update']);
        Route::delete('books/{id}', [BookController::class, 'destroy']);

        // categories
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{id}', [CategoryController::class, 'update']);
        Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

        Route::get('admin/users', [AuthController::class, 'users']);

        // orders
        Route::get('admin/orders', [OrderController::class, 'adminIndex']);
        Route::put('admin/orders/{id}/status', [OrderController::class, 'updateStatus']);
    });
});
