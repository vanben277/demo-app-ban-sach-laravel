<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Review, Order, Book};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5',
        ]);

        $userId = auth('api')->id();

        $hasPurchased = Order::where('id', $request->order_id)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereHas('items', function ($query) use ($request) {
                $query->where('book_id', $request->book_id);
            })->exists();

        if (!$hasPurchased) {
            return response()->json(['message' => 'Bạn không thể đánh giá sản phẩm chưa mua hoặc chưa hoàn thành thanh toán.'], 403);
        }

        return DB::transaction(function () use ($request, $userId) {
            $review = Review::create([
                'user_id' => $userId,
                'book_id' => $request->book_id,
                'order_id' => $request->order_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            $book = Book::find($request->book_id);
            $stats = Review::where('book_id', $book->id)
                ->selectRaw('AVG(rating) as avg, COUNT(*) as count')
                ->first();

            $book->update([
                'rating_avg' => $stats->avg,
                'review_count' => $stats->count
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đánh giá sản phẩm thành công!',
                'data' => $review
            ], 201);
        });
    }

    public function getByBook($bookId)
    {
        $reviews = Review::with('user:id,name')
            ->where('book_id', $bookId)
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }
}
