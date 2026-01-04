<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Order, OrderItem, Cart, Book};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'address' => 'required',
            'payment_method' => 'required|in:cod,bank_transfer',
        ]);
        $user = auth('api')->user();

        $cartItems = Cart::where('user_id', $user->id)->with('book')->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Giỏ hàng rỗng!'], 400);
        }

        return DB::transaction(function () use ($request, $user, $cartItems) {

            $totalAmount = 0;

            foreach ($cartItems as $item) {
                if ($item->book->stock < $item->quantity) {
                    throw new \Exception("Sách '{$item->book->title}' không đủ hàng trong kho!");
                }
                $totalAmount += $item->book->price * $item->quantity;
            }

            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'phone' => $request->phone,
                'address' => $request->address,
                'payment_method' => $request->payment_method,
                'status' => 'pending'
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $item->book_id,
                    'quantity' => $item->quantity,
                    'price' => $item->book->price,
                ]);

                $item->book->decrement('stock', $item->quantity);
            }

            Cart::where('user_id', $user->id)->delete();

            $qrUrl = null;
            if ($request->payment_method === 'bank_transfer') {
                $bankId    = config('services.vietqr.bank_id');
                $accountNo = config('services.vietqr.account_no');
                $template  = config('services.vietqr.template');

                $amount      = $totalAmount;
                $description = "DH" . $order->id . " " . $request->phone;

                $qrUrl = "https://img.vietqr.io/image/{$bankId}-{$accountNo}-{$template}.png?amount={$amount}&addInfo={$description}";
            }

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công!',
                'order_id' => $order->id,
                'payment_method' => $order->payment_method,
                'qr_url' => $qrUrl
            ], 201);
        });
    }

    public function index(Request $request)
    {
        $query = Order::where('user_id', auth('api')->id());

        $query->when($request->status, function ($q, $status) {
            return $q->where('status', $status);
        });

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);
        return OrderResource::collection($orders);
    }

    public function cancel($id)
    {
        $order = Order::where('user_id', auth('api')->id())->find($id);

        if (!$order) return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Chỉ có thể hủy đơn hàng đang chờ xử lý'], 400);
        }

        return DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);

            foreach ($order->items as $item) {
                $item->book->increment('stock', $item->quantity);
            }

            return response()->json(['message' => 'Đã hủy đơn hàng và hoàn lại kho']);
        });
    }

    public function adminIndex(Request $request)
    {
        if (auth('api')->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = Order::with(['items.book']);

        $query->when($request->user_id, fn($q, $id) => $q->where('user_id', $id));

        $query->when($request->status, fn($q, $status) => $q->where('status', $status));

        $query->when($request->date, fn($q, $date) => $q->whereDate('created_at', $date));

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);
        return OrderResource::collection($orders);
    }

    public function show($id)
    {
        $order = Order::with(['items.book'])->find($id);

        if (!$order) return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);

        if (auth('api')->user()->role !== 'admin' && $order->user_id !== auth('api')->id()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return new OrderResource($order);
    }

    // (Pending -> Processing -> Shipping -> Completed)
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:pending,processing,shipping,completed,cancelled']);

        $order = Order::find($id);
        if (!$order) return response()->json(['message' => 'Không tìm thấy'], 404);

        $order->update(['status' => $request->status]);

        return response()->json(['message' => 'Cập nhật trạng thái thành công', 'status' => $order->status]);
    }
}
