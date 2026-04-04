<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;

class CartService
{
    public function addToCart($productId)
    {
        $cart = Cart::firstOrCreate([
            'user_id' => auth()->id(),
            'is_checked_out' => false
        ]);

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if ($item) {
            $item->increment('quantity'); // ✅ safer
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'quantity' => 1
            ]);
        }

        return $cart;
    }

    public function checkout($cart)
    {
        $cart->update([
            'is_checked_out' => true
        ]);
    }
}