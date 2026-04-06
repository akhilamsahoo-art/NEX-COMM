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
    // ✅ VIEW CART (MULTIPLE CARTS - PER SELLER)
    public function index(Request $request)
    {
        $carts = Cart::with('items.product')
            ->where('user_id', $request->user()->id)
            ->where('is_checked_out', false)
            ->get();

        if ($carts->isEmpty()) {
            return ApiResponse::success([], 'Cart is empty');
        }

        return ApiResponse::success($carts, 'Cart fetched successfully');
    }

    // ✅ ADD TO CART (SELLER BASED CART + CREATE/UPDATE "IN_CART" ORDER)
    public function add(Request $request)
    {
        // 🔥 Validation
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        // ✅ Get product
        $product = Product::findOrFail($request->product_id);

        // 🔥 IMPORTANT: Cart belongs to PRODUCT OWNER (seller)
        $cart = Cart::where('user_id', $request->user()->id)
            ->where('tenant_id', $product->tenant_id)
            ->where('is_checked_out', false)
            ->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $request->user()->id,
                'tenant_id' => $product->tenant_id, 
                'is_checked_out' => false
            ]);
        }

        // ✅ Check if item exists in cart
        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            $item->increment('quantity', $request->quantity);
        } else {
            $item = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity
            ]);
        }

        $item->load('product');

        // 🔹 CREATE OR UPDATE IN_CART ORDER
        DB::transaction(function () use ($request, $cart, $product, $item) {
            $order = Order::firstOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'tenant_id' => $product->tenant_id,
                    'order_status' => 'in_cart',
                ],
                [
                    'total_amount' => 0,
                    'payment_status' => 'pending',
                    'shipment_status' => 'pending',
                ]
            );

            $orderItem = OrderItem::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => $item->quantity,
                    'price' => $product->price
                ]
            );

            // ✅ Update order total
            $order->total_amount = $order->items()->sum(DB::raw('quantity * price'));
            $order->save();
        });

        return ApiResponse::success($item, 'Item added to cart');
    }

    // ✅ REMOVE ITEM (NO TENANT FILTER BUG)
    public function remove(Request $request, $id)
    {
        $item = CartItem::where('id', $id)
            ->whereHas('cart', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                  ->where('is_checked_out', false);
            })
            ->first();

        if (!$item) {
            return ApiResponse::error('Cart item not found', 404);
        }

        $product = $item->product;
        $tenantId = $item->cart->tenant_id;

        // 🔹 Remove item from "in_cart" order
        $order = Order::where('user_id', $request->user()->id)
            ->where('tenant_id', $tenantId)
            ->where('order_status', 'in_cart')
            ->first();

        if ($order) {
            $orderItem = $order->items()->where('product_id', $product->id)->first();
            if ($orderItem) {
                $orderItem->delete();
            }

            // If order has no items left, delete it
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

    // ✅ CLEAR ALL CARTS (MULTI-SELLER SAFE)
    public function clear(Request $request)
    {
        $carts = Cart::where('user_id', $request->user()->id)
            ->where('is_checked_out', false)
            ->get();

        if ($carts->isEmpty()) {
            return ApiResponse::success(null, 'Cart already empty');
        }

        foreach ($carts as $cart) {
            $tenantId = $cart->tenant_id;

            // 🔹 Remove all items from "in_cart" order
            $order = Order::where('user_id', $request->user()->id)
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