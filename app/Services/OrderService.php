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
    /**
     * ✅ Create Order from direct API items
     * Modified to accept and save address_id
     */
    public function createOrder(int $userId, array $items, int $addressId): Order
    {
        $order = DB::transaction(function () use ($userId, $items, $addressId) {
            $total = 0;
            $orderTenantId = null;
            $validatedItems = [];

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                
                if ($product->quantity < $item['quantity']) {
                    abort(400, $product->name . " is out of stock");
                }

                // 🚀 Ensures the order is owned by the product's tenant (The Seller)
                if ($orderTenantId === null) {
                    $orderTenantId = $product->tenant_id;
                }

                $total += ($product->price * $item['quantity']);
                $validatedItems[] = ['product' => $product, 'quantity' => $item['quantity']];
            }

            // Creating the order with address_id included
            $order = Order::create([
                'user_id'         => $userId,
                'address_id'      => $addressId, // 🆕 Added address_id
                'tenant_id'       => $orderTenantId,
                'total_amount'    => $total,
                'order_status'    => 'placed', 
                'payment_status'  => 'pending',
                'shipment_status' => 'pending',
            ]);

            foreach ($validatedItems as $data) {
                $product = $data['product'];
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $data['quantity'],
                    'price'      => $product->price,
                ]);
                $product->decrement('quantity', $data['quantity']);
            }

            return $order;
        });

        $this->sendOrderNotifications($order);

        return $order;
    }

    /**
     * ✅ Checkout from Cart logic
     * Modified to accept and save address_id
     */
    public function checkoutFromCart($user, $items, int $addressId)
    {
        $order = DB::transaction(function () use ($user, $items, $addressId) {
            $total = 0;
            $orderTenantId = null;

            // Determine the store/tenant from the first item
            $firstItem = CartItem::with('product')->where('id', $items[0]['cart_item_id'])->first();
            $orderTenantId = $firstItem ? $firstItem->product->tenant_id : null;

            $order = Order::create([
                'user_id'         => $user->id,
                'address_id'      => $addressId, // 🆕 Added address_id
                'tenant_id'       => $orderTenantId,
                'total_amount'    => 0,
                'order_status'    => 'placed',
                'payment_status'  => 'pending',
                'shipment_status' => 'processing',
            ]);

            foreach ($items as $item) {
                $cartItem = CartItem::with('product')->where('id', $item['cart_item_id'])->first();
                if (!$cartItem) abort(400, 'Invalid cart item');

                $product = $cartItem->product;
                $product->decrement('quantity', $item['quantity']);

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                ]);

                $total += $product->price * $item['quantity'];
                $cartItem->delete();
            }

            $order->update(['total_amount' => $total]);
            return $order;
        });

        $this->sendOrderNotifications($order);

        return $order->load('items.product');
    }

    /**
     * ✅ Fetches orders based on role hierarchy
     */
    public function getAllOrders()
    {
        $user = auth()->user();
        $query = Order::with(['user', 'items.product'])->latest();

        if ($user->role === 'super_admin') {
            return $query->get();
        }

        // Managers and Sellers only see orders for their tenant
        return $query->where('tenant_id', $user->tenant_id)->get();
    }

    /**
     * ✅ Customer order history
     */
    public function getUserOrders(int $userId)
    {
        return Order::with(['items.product'])
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * ✅ Internal Notification Logic
     */
    private function sendOrderNotifications(Order $order)
    {
        $order->load(['items.product', 'user']);
        $currentUser = auth()->user();

        // Notify Customer
        if ($currentUser && !empty($currentUser->email)) {
            try {
                Mail::to($currentUser->email)->send(new OrderPlacedMail($order));
            } catch (\Exception $e) {
                Log::error("Failed to send customer email: " . $e->getMessage());
            }
        }

        // Notify Seller
        $seller = User::where('tenant_id', $order->tenant_id)
                      ->where('role', 'seller')
                      ->first();

        if ($seller && !empty($seller->email)) {
            try {
                Mail::to($seller->email)->send((new SellerNewOrderMail($order))->afterCommit());
            } catch (\Exception $e) {
                Log::error("Failed to send seller email: " . $e->getMessage());
            }
        } else {
            Log::warning("No seller found for Tenant ID: " . $order->tenant_id);
        }
    }

    /**
     * ✅ Update Shipping Status & Notify
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
}