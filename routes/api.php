<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CategoryController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// books
Route::post('/books', [BookController::class, 'index']);
Route::get('/books', [BookController::class, 'store']);
Route::get('/books/{id}', [BookController::class, 'show']);
Route::delete('/books/{id}', [BookController::class, 'destroy']);
Route::put('/books/{id}', [BookController::class, 'update']);

// categories
Route::apiResource('categories', CategoryController::class);
