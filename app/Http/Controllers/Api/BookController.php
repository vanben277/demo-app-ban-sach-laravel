<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Http\Requests\StoreBookRequest;

class BookController extends Controller
{
    public function store(StoreBookRequest $request) 
    {   
        $book = Book::create($request->validated());

        return response()->json([
            'message' => 'Thêm sách thành công!',
            'data'    => $book
        ], 201);
    }
}
