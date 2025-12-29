<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/hello', function () {
    return "Chào bạn! Đây là trang đầu tiên của tớ trên Laravel.";
});
