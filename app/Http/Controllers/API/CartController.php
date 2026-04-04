<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

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

    // ✅ ADD TO CART (SELLER BASED CART)
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
                'tenant_id' => $product->tenant_id, // ✅ FIXED
                'is_checked_out' => false
            ]);
        }

        // ✅ Check if item exists
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
            CartItem::where('cart_id', $cart->id)->delete();
        }

        return ApiResponse::success(null, 'Cart cleared successfully');
    }
}