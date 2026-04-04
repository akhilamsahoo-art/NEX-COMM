<?php

namespace App\Services;

use App\Models\Order;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create a new order with order items inside a transaction
     */
    public function createOrder(int $userId, array $items): Order
    {
        return DB::transaction(function () use ($userId, $items) {

            $user = auth()->user();
            $tenantId = $user->tenant_id;

            $total = 0;

            // ✅ Validate stock + calculate total
            foreach ($items as $item) {
                $product = Product::where('id', $item['product_id'])
                    ->where('tenant_id', $tenantId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->quantity < $item['quantity']) {
                    abort(400, "{$product->name} is out of stock");
                }

                $total += $product->price * $item['quantity'];
            }

            // ✅ Create order
            $order = Order::create([
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'total_amount' => $total,
                'order_status' => 'placed',
                'payment_status' => 'pending',
                'shipment_status' => 'pending',
            ]);

            // ✅ Create order items + reduce stock
            foreach ($items as $item) {
                $product = Product::where('id', $item['product_id'])
                    ->where('tenant_id', $tenantId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $product->decrement('quantity', $item['quantity']);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $product->price
                ]);
            }

            return $order->load('items.product');
        });
    }

    /**
     * ✅ NEW: Checkout from Cart
     */
    public function checkoutFromCart($user, $items)
{
    return DB::transaction(function () use ($user, $items) {

        $total = 0;

        $order = Order::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'total_amount' => 0,
            'order_status' => 'placed',
            'payment_status' => 'pending',
            'shipment_status' => 'processing',
        ]);

        foreach ($items as $item) {

            // ✅ Fetch cart item WITH RELATION
            $cartItem = CartItem::with('product', 'cart')
            ->where('id', $item['cart_item_id'])
            ->whereHas('cart', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('is_checked_out', false);
            })
            ->first();
            // 🔴 VALIDATION
            if (!$cartItem) {
                abort(400, 'Invalid cart item');
            }

            $product = $cartItem->product;

            // 🔴 STOCK CHECK
            if ($product->quantity < $item['quantity']) {
                abort(400, "{$product->name} is out of stock");
            }

            // ✅ Reduce stock
            $product->decrement('quantity', $item['quantity']);

            // ✅ Add to order
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
            ]);

            $total += $product->price * $item['quantity'];

            // ✅ Remove from cart
            $cartItem->delete();
        }

        // ✅ Update total
        $order->update([
            'total_amount' => $total
        ]);

        return $order->load('items.product');
    });
}

    /**
     * Get all orders (admin) — tenant safe
     */
    public function getAllOrders()
    {
        $user = auth()->user();

        return Order::with(['user', 'items.product'])
            ->where('tenant_id', $user->tenant_id)
            ->latest()
            ->get();
    }

    /**
     * Get orders for a specific user — tenant safe
     */
    public function getUserOrders(int $userId)
    {
        $user = auth()->user();

        return Order::with(['items.product'])
            ->where('user_id', $userId)
            ->where('tenant_id', $user->tenant_id)
            ->latest()
            ->get();
    }
}