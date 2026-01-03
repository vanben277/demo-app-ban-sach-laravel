<?php

namespace App\Http\Controllers\Api;

use App\Models\Book;
use App\Models\BookImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Http\Requests\Books\StoreBookRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Books\UpdateBookRequest;
use Illuminate\Support\Facades\Validator;

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

    public function show($id)
    {
        $book = Book::with(['category', 'images'])->find($id);

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm!'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new BookResource($book)
        ]);
    }

    public function update(UpdateBookRequest $request, $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy sản phẩm!'], 404);
        }

        $book->update($request->validated());

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('books', 'public');

                $book->images()->create([
                    'image_path' => $path,
                    'is_main' => false,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công!',
            'data' => new BookResource($book->load('images'))
        ]);
    }

    public function destroy($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy sản phẩm!'], 404);
        }

        foreach ($book->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $book->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa sản phẩm thành công!'
        ]);
    }
}
