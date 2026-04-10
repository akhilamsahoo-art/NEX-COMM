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
     * ✅ Modified: Ensures Order inherits Product Tenant ID
     */
    public function createOrder(int $userId, array $items): Order
    {
        $order = DB::transaction(function () use ($userId, $items) {
            $total = 0;
            $orderTenantId = null;
            $validatedItems = [];

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                
                if ($product->quantity < $item['quantity']) {
                    abort(400, $product->name . " is out of stock");
                }

                // 🚀 FIX: Grab Tenant ID from Product, not the logged-in Customer
                if ($orderTenantId === null) {
                    $orderTenantId = $product->tenant_id;
                }

                $total += ($product->price * $item['quantity']);
                $validatedItems[] = ['product' => $product, 'quantity' => $item['quantity']];
            }

            $order = new Order();
            $order->user_id = $userId;
            $order->tenant_id = $orderTenantId; // Linked to the store owner
            $order->total_amount = $total;
            $order->order_status = 'placed'; // Visible to Sellers
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

        $this->sendOrderNotifications($order);

        return $order;
    }

    /**
     * ✅ Modified: Fixed tenant inheritance from Cart Items
     */
    public function checkoutFromCart($user, $items)
    {
        $order = DB::transaction(function () use ($user, $items) {
            $total = 0;
            $orderTenantId = null;

            // Pre-fetch first item to determine the store/tenant
            $firstItem = CartItem::with('product')->where('id', $items[0]['cart_item_id'])->first();
            $orderTenantId = $firstItem ? $firstItem->product->tenant_id : $user->tenant_id;

            $order = Order::create([
                'user_id' => $user->id,
                'tenant_id' => $orderTenantId, // 🚀 FIX: Use product/store tenant
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

        $this->sendOrderNotifications($order);

        return $order->load('items.product');
    }

    /**
     * ✅ Modified: Differentiates between Super Admin and Tenant Scoping
     */
    public function getAllOrders()
    {
        $user = auth()->user();
        $query = Order::with(['user', 'items.product'])->latest();

        // 🚀 FIX: Super Admin sees all, Managers/Sellers see only theirs
        if ($user->role === 'super_admin') {
            return $query->get();
        }

        return $query->where('tenant_id', $user->tenant_id)->get();
    }

    /**
     * ✅ Modified: Fixed Customer order history (Removes tenant restriction)
     */
    public function getUserOrders(int $userId)
    {
        // 🚀 FIX: Customers don't have tenant_id. Fetch solely by user_id.
        return Order::with(['items.product'])
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * ✅ Helper: Keeps notification logic central and clean
     */
    private function sendOrderNotifications(Order $order)
    {
        $order->load(['items.product', 'user']);
        $currentUser = auth()->user();

        if ($currentUser && !empty($currentUser->email)) {
            Mail::to($currentUser->email)->send(new OrderPlacedMail($order));
        }

        $seller = User::where('tenant_id', $order->tenant_id)
                      ->where('role', 'seller')
                      ->first();

        if ($seller && !empty($seller->email)) {
            Mail::to($seller->email)->send((new SellerNewOrderMail($order))->afterCommit());
        } else {
            Log::warning("SellerNewOrderMail skipped: No seller found for Tenant ID: " . $order->tenant_id);
        }
    }

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