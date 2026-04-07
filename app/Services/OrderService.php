<?php

namespace App\Services;

use App\Models\Order;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User; // Added User model
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlacedMail;
use App\Mail\SellerNewOrderMail; // Import Seller Mail
use App\Mail\OrderShippedMail;   // Import Shipped Mail
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder(int $userId, array $items): Order
    {
        return DB::transaction(function () use ($userId, $items) {
            $user = auth()->user();
            $total = 0;
            $orderTenantId = null;
            $validatedItems = [];

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                if ($product->quantity < $item['quantity']) {
                    abort(400, $product->name . " is out of stock");
                }

                if ($orderTenantId === null) $orderTenantId = $product->tenant_id;

                $total += ($product->price * $item['quantity']);
                $validatedItems[] = ['product' => $product, 'quantity' => $item['quantity']];
            }

            $order = new Order();
            $order->user_id = $userId;
            $order->tenant_id = $orderTenantId;
            $order->total_amount = $total;
            $order->order_status = 'placed';
            $order->payment_status = 'pending';
            $order->shipment_status = 'pending';
            $order->save();

            foreach ($validatedItems as $data) {
                $product = $data['product'];
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $data['quantity'],
                    'price' => $product->price,
                ]);
                $product->decrement('quantity', $data['quantity']);
            }

            // --- EMAILS ---
            $order->load(['items.product', 'user']);
            
            // 1. Notify Customer
            Mail::to($user->email)->send(new OrderPlacedMail($order));

            // 2. Notify Seller (The owner of the tenant)
            $seller = User::where('tenant_id', $orderTenantId)->where('role', 'seller')->first();
            if ($seller) {
                // Mail::to($seller->email)->send(new SellerNewOrderMail($order));
                // Use load() to ensure products and user data are attached to the order object
                Mail::to($seller->email)->send(new SellerNewOrderMail($order->load(['items.product', 'user'])));
            }

            return $order;
        });
    }

    /**
     * Update Shipping Status & Notify User
     */
    public function updateShippingStatus(int $orderId, string $status)
    {
        $order = Order::with('user')->findOrFail($orderId);
        $oldStatus = $order->shipment_status;
        $order->shipment_status = $status;
        $order->save();

        // 3. Send Email only when status moves to 'shipped'
        if ($oldStatus !== 'shipped' && $status === 'shipped') {
            Mail::to($order->user->email)->send(new OrderShippedMail($order));
        }

        return $order;
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
                $cartItem = CartItem::with('product')->where('id', $item['cart_item_id'])->first();
                if (!$cartItem) abort(400, 'Invalid cart item');

                $product = $cartItem->product;
                $product->decrement('quantity', $item['quantity']);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);

                $total += $product->price * $item['quantity'];
                $cartItem->delete();
            }

            $order->update(['total_amount' => $total]);

            // ✅ TRIGGER EMAIL HERE
            Mail::to($user->email)->send(new OrderPlacedMail($order->load('user')));

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