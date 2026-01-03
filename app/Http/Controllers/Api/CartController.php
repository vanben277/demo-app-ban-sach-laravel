<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = Cart::where('user_id', auth('api')->id())
            ->with(['book.images'])
            ->get();

        return response()->json(['success' => true, 'data' => $cartItems]);
    }

    public function sync(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.book_id' => 'required|exists:books,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $userId = auth('api')->id();
        $items = $request->input('items');

        foreach ($items as $item) {
            $cartItem = Cart::where('user_id', $userId)
                ->where('book_id', $item['book_id'])
                ->first();

            if ($cartItem) {
                $cartItem->increment('quantity', $item['quantity']);
            } else {
                Cart::create([
                    'user_id' => $userId,
                    'book_id' => $item['book_id'],
                    'quantity' => $item['quantity'],
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Đồng bộ giỏ hàng thành công!']);
    }

    public function store(Request $request)
    {
        $userId = auth('api')->id();

        $cartItem = Cart::updateOrCreate(
            ['user_id' => $userId, 'book_id' => $request->book_id],
            ['quantity' => DB::raw("quantity + " . ($request->quantity ?? 1))]
        );

        return response()->json(['success' => true, 'message' => 'Đã cập nhật giỏ hàng']);
    }

    public function destroy($id)
    {
        Cart::where('user_id', auth('api')->id())->where('id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa khỏi giỏ hàng']);
    }
}
