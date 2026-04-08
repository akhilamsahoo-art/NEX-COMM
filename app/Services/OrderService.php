<?php

namespace App\Services;

use App\Models\Order;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User; 
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlacedMail;
use App\Mail\SellerNewOrderMail; 
use App\Mail\OrderShippedMail;   
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function createOrder(int $userId, array $items): Order
    {
        // 1. Database logic inside the transaction
        $order = DB::transaction(function () use ($userId, $items) {
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

            return $order;
        });

        // 2. Email logic OUTSIDE the transaction
        $order->load(['items.product', 'user']);
        $currentUser = auth()->user();

        // Notify Customer (Always check if email exists)
        if ($currentUser && !empty($currentUser->email)) {
            Mail::to($currentUser->email)->send(new OrderPlacedMail($order));
        }

        // Notify Seller
        $seller = User::where('tenant_id', $order->tenant_id)
                      ->where('role', 'seller')
                      ->first();

        // --- CRITICAL FIX: Check if seller exists AND has a valid email ---
        if ($seller && !empty($seller->email)) {
            Mail::to($seller->email)->send((new SellerNewOrderMail($order))->afterCommit());
        } else {
            // Log this so you know which tenant is missing a seller email
            Log::warning("SellerNewOrderMail skipped: No seller or email found for Tenant ID: " . $order->tenant_id);
        }

        return $order;
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

        if ($oldStatus !== 'shipped' && $status === 'shipped') {
            if ($order->user && !empty($order->user->email)) {
                Mail::to($order->user->email)->send(new OrderShippedMail($order));
            }
        }

        return $order;
    }

    /**
     * ✅ Checkout from Cart
     */
    public function checkoutFromCart($user, $items)
    {
        $order = DB::transaction(function () use ($user, $items) {
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
            return $order;
        });

        // Safety check for user email
        if ($user && !empty($user->email)) {
            Mail::to($user->email)->send(new OrderPlacedMail($order->load('user')));
        }
        $seller = User::where('tenant_id', $order->tenant_id)
                      ->where('role', 'seller')
                      ->first();

        if ($seller && !empty($seller->email)) {
            Mail::to($seller->email)->send((new SellerNewOrderMail($order))->afterCommit());
        } else {
            Log::warning("SellerNewOrderMail skipped in checkoutFromCart: No seller for Tenant ID: " . $order->tenant_id);
        }

        return $order->load('items.product');
    }

    /**
     * Get all orders (admin)
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
     * Get orders for a specific user
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