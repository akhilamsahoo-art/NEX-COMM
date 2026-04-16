<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return ApiResponse::error('Unauthorized', 401);
        }

        $carts = Cart::with(['items.product' => function ($query) {
            $query->select('id', 'name', 'price', 'image');
        }])
            ->where('user_id', $user->id) // ✅ FIXED
            ->where('is_checked_out', false)
            ->get();

        if ($carts->isEmpty()) {
            return ApiResponse::success([], 'Cart is empty');
        }

        $grandTotal = $carts->sum(function ($cart) {
            return $cart->total;
        });

        return ApiResponse::success([
            'carts' => $carts->map(function ($cart) {
                return [
                    'id' => $cart->id,
                    'total' => $cart->total,
                    'items' => $cart->items
                ];
            }),
            'grand_total' => $grandTotal
        ], 'Cart fetched successfully');
    }

    public function update(Request $request, $id)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return ApiResponse::error('Unauthorized', 401);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $item = CartItem::where('id', $id)
            ->whereHas('cart', function ($q) use ($user) {
                $q->where('user_id', $user->id) // ✅ FIXED
                  ->where('is_checked_out', false);
            })
            ->first();

        if (!$item) {
            return ApiResponse::error('Cart item not found', 404);
        }

        if ($request->quantity > $item->product->quantity) {
            return ApiResponse::error('Not enough stock');
        }

        $item->update([
            'quantity' => $request->quantity
        ]);

        // $item->cart->touch();
        $item->cart->update([
    'updated_at' => now()
]);

        return ApiResponse::success($item->load('product'), 'Quantity updated');
    }

    public function add(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return ApiResponse::error('Unauthorized', 401);
        }

        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->quantity <= 0) {
            return ApiResponse::error('Product is out of stock');
        }

        if ($request->quantity > $product->quantity) {
            return ApiResponse::error('Not enough stock available');
        }

        $cart = Cart::where('user_id', $user->id) // ✅ FIXED
            ->where('tenant_id', $product->tenant_id)
            ->first();

        if ($cart && $cart->is_checked_out) {
            return ApiResponse::error('Cart already checked out');
        }

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id, // ✅ FIXED
                'tenant_id' => $product->tenant_id,
                'is_checked_out' => false
            ]);
        }

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        $newQuantity = $item
            ? $item->quantity + $request->quantity
            : $request->quantity;

        if ($newQuantity > $product->quantity) {
            return ApiResponse::error('Stock exceeded for this product');
        }

        if ($item) {
            $item->increment('quantity', $request->quantity);
            $cart->update([
    'updated_at' => now()
]);
        } else {
            $item = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->price
            ]);
        }

        $item->load('product');

        DB::transaction(function () use ($user, $cart, $product, $item) {

            $order = Order::firstOrCreate(
                [
                    'user_id' => $user->id, // ✅ FIXED
                    'tenant_id' => $product->tenant_id,
                    'order_status' => 'in_cart',
                ],
                [
                    'total_amount' => 0,
                    'payment_status' => 'pending',
                    'shipment_status' => 'pending',
                ]
            );

            OrderItem::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => $item->quantity,
                    'price' => $product->price
                ]
            );

            $order->total_amount = $order->items()
                ->select(DB::raw('SUM(quantity * price) as total'))
                ->value('total') ?? 0;

            $order->save();
        });

        return ApiResponse::success($item, 'Item added to cart');
    }

    public function count(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return ApiResponse::error('Unauthorized', 401);
        }

        $count = CartItem::whereHas('cart', function ($q) use ($user) {
            $q->where('user_id', $user->id) // ✅ FIXED
              ->where('is_checked_out', false);
        })->sum('quantity');

        return ApiResponse::success(['count' => $count], 'Cart count');
    }

    public function remove(Request $request, $id)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return ApiResponse::error('Unauthorized', 401);
        }

        $item = CartItem::where('id', $id)
            ->whereHas('cart', function ($q) use ($user) {
                $q->where('user_id', $user->id) // ✅ FIXED
                  ->where('is_checked_out', false);
            })
            ->first();

        if (!$item) {
            return ApiResponse::error('Cart item not found', 404);
        }

        $product = $item->product;
        $tenantId = $item->cart->tenant_id;

        $order = Order::where('user_id', $user->id) // ✅ FIXED
            ->where('tenant_id', $tenantId)
            ->where('order_status', 'in_cart')
            ->first();

        if ($order) {
            $orderItem = $order->items()->where('product_id', $product->id)->first();
            if ($orderItem) {
                $orderItem->delete();
            }

            if ($order->items()->count() === 0) {
                $order->delete();
            } else {
                $order->total_amount = $order->items()->sum(DB::raw('quantity * price'));
                $order->save();
            }
        }

        $item->delete();

        return ApiResponse::success(null, 'Item removed from cart');
    }

    public function clear(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return ApiResponse::error('Unauthorized', 401);
        }

        $carts = Cart::where('user_id', $user->id) // ✅ FIXED
            ->where('is_checked_out', false)
            ->get();

        if ($carts->isEmpty()) {
            return ApiResponse::success(null, 'Cart already empty');
        }

        foreach ($carts as $cart) {
            $tenantId = $cart->tenant_id;

            $order = Order::where('user_id', $user->id) // ✅ FIXED
                ->where('tenant_id', $tenantId)
                ->where('order_status', 'in_cart')
                ->first();

            if ($order) {
                $order->items()->delete();
                $order->delete();
            }

            CartItem::where('cart_id', $cart->id)->delete();
        }

        return ApiResponse::success(null, 'Cart cleared successfully');
    }
}