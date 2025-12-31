<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\BookImage;
use App\Http\Resources\BookResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BookController extends Controller
{
    public function store(StoreBookRequest $request)
    {
        $book = Book::create($request->validated());

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $validator = Validator::make(
                    ['image' => $image],
                    ['image' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048']
                );
                
                if ($validator->fails()) {
                    Log::error('Validation failed:', $validator->errors()->toArray());
                    continue;
                }
                
                $path = $image->store('books', 'public');
                Log::info('Saved image:', ['path' => $path]);
                
                BookImage::create([
                    'book_id' => $book->id,
                    'image_path' => $path,
                    'is_main' => ($index === 0),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Thêm sách và bộ sưu tập ảnh thành công!',
            'data' => new BookResource($book->fresh()->load('images'))
        ], 201);
    }

    public function index(Request $request)
    {
        $query = Book::with(['category', 'images']);

        $query->when($request->category_id, function ($q, $categoryId) {
            return $q->where('category_id', $categoryId);
        });

        $query->when($request->search, function ($q, $search) {
            return $q->where(function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
            });
        });

        $query->when($request->min_price, function ($q, $minPrice) {
            return $q->where('price', '>=', $minPrice);
        });

        $query->when($request->max_price, function ($q, $maxPrice) {
            return $q->where('price', '<=', $maxPrice);
        });

        $sortOrder = $request->get('order', 'desc');
        $query->orderBy('created_at', $sortOrder);

        $books = $query->paginate(10);
        return BookResource::collection($books);
    }
}